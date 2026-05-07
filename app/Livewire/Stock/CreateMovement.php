<?php

namespace App\Livewire\Stock;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Location;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CreateMovement extends Component
{
    public string $type = '';

    public ?int $productId = null;

    public ?int $locationId = null;

    public ?int $fromLocationId = null;

    public ?int $toLocationId = null;

    public int $quantity = 1;

    public string $reference = '';

    public string $notes = '';

    #[Computed]
    public function products()
    {
        return Product::orderBy('name')->get();
    }

    #[Computed]
    public function locations()
    {
        return Location::with('warehouse')->orderBy('code')->get();
    }

    #[Computed]
    public function types(): array
    {
        return StockMovementType::cases();
    }

    public function save(StockService $stockService): void
    {
        $this->validate([
            'type' => 'required|in:incoming,outgoing,transfer,correction',
            'productId' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'locationId' => 'required_unless:type,transfer|nullable|exists:locations,id',
            'fromLocationId' => 'required_if:type,transfer|nullable|exists:locations,id',
            'toLocationId' => 'required_if:type,transfer|nullable|exists:locations,id|different:fromLocationId',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($this->productId);
        $user = auth()->user();

        try {
            match ($this->type) {
                'incoming' => $stockService->registerIncoming(
                    $product,
                    Location::findOrFail($this->locationId),
                    $this->quantity,
                    $user,
                    $this->reference ?: null,
                    $this->notes ?: null,
                ),
                'outgoing' => $stockService->registerOutgoing(
                    $product,
                    Location::findOrFail($this->locationId),
                    $this->quantity,
                    $user,
                    $this->reference ?: null,
                    $this->notes ?: null,
                ),
                'transfer' => $stockService->transfer(
                    $product,
                    Location::findOrFail($this->fromLocationId),
                    Location::findOrFail($this->toLocationId),
                    $this->quantity,
                    $user,
                    $this->notes ?: null,
                ),
                'correction' => $stockService->adjust(
                    $product,
                    Location::findOrFail($this->locationId),
                    $this->quantity,
                    $user,
                    $this->notes ?: null,
                ),
            };

            Flux::toast(__('Stock movement registered.'), variant: 'success');
            $this->redirect(route('stock.movements'), navigate: true);
        } catch (InsufficientStockException $e) {
            $this->addError('quantity', __('Insufficient stock: ') . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.stock.create-movement');
    }
}
