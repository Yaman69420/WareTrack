<?php

namespace App\Livewire\Deliveries;

use App\Enums\DeliveryStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Delivery;
use App\Services\StockService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Delivery $delivery;

    // keyed by delivery_item id → quantity to receive now
    public array $receivedQuantities = [];

    public function mount(Delivery $delivery): void
    {
        $this->delivery = $delivery->load(['supplier', 'user', 'items.product', 'items.location.warehouse']);
        $this->initReceivedQuantities();
    }

    private function initReceivedQuantities(): void
    {
        $this->receivedQuantities = $this->delivery->items
            ->mapWithKeys(fn ($item) => [$item->id => max(0, $item->quantity_ordered - $item->quantity_received)])
            ->toArray();
    }

    public function process(StockService $stockService): void
    {
        if ($this->delivery->status === DeliveryStatus::Received) {
            Flux::toast(__('This delivery has already been fully received.'), variant: 'warning');

            return;
        }

        $this->validate([
            'receivedQuantities.*' => 'required|integer|min:0',
        ]);

        try {
            foreach ($this->delivery->items as $item) {
                $qty = (int) ($this->receivedQuantities[$item->id] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                $maxReceivable = $item->quantity_ordered - $item->quantity_received;
                $qty = min($qty, $maxReceivable);

                $stockService->registerIncoming(
                    $item->product,
                    $item->location,
                    $qty,
                    auth()->user(),
                    $this->delivery->reference,
                    "Delivery #{$this->delivery->id}",
                );

                $item->increment('quantity_received', $qty);
            }

            $this->delivery->refresh()->load(['supplier', 'user', 'items.product', 'items.location.warehouse']);

            $allReceived = $this->delivery->items->every(
                fn ($item) => $item->quantity_received >= $item->quantity_ordered
            );

            $this->delivery->update([
                'status' => $allReceived ? DeliveryStatus::Received : DeliveryStatus::Partial,
                'received_at' => $allReceived ? now() : $this->delivery->received_at,
            ]);

            $this->delivery->refresh()->load(['supplier', 'user', 'items.product', 'items.location.warehouse']);

            activity()->causedBy(auth()->user())->performedOn($this->delivery)->log('processed');

            Flux::toast(
                $allReceived ? __('Delivery fully received.') : __('Delivery partially received.'),
                variant: 'success'
            );

            $this->initReceivedQuantities();
        } catch (InsufficientStockException $e) {
            Flux::toast(__('Stock error: ').$e->getMessage(), variant: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.deliveries.show');
    }
}
