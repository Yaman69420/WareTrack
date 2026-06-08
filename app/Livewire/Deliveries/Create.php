<?php

namespace App\Livewire\Deliveries;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public ?int $supplierId = null;

    public string $reference = '';

    public string $notes = '';

    public array $items = [];

    public function updatedSupplierId(): void
    {
        // Reset items when supplier changes so stale product selections are cleared
        $this->items = [];
        $this->addItem();
    }

    public function mount(): void
    {
        // Items are added once the supplier is selected (see updatedSupplierId)
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::orderBy('name')->get();
    }

    #[Computed]
    public function products()
    {
        if ($this->supplierId) {
            $supplier = Supplier::find($this->supplierId);
            $products = $supplier?->products()->orderBy('name')->get() ?? collect();

            // Fall back to all products if supplier has none linked yet
            return $products->isNotEmpty() ? $products : Product::orderBy('name')->get();
        }

        return collect();
    }

    #[Computed]
    public function locations()
    {
        return Location::with('warehouse')->orderBy('code')->get();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => null,
            'location_id' => null,
            'quantity_ordered' => 1,
        ];
    }

    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
    }

    public function save(): void
    {
        $this->validate([
            'supplierId' => 'required|exists:suppliers,id',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.location_id' => 'required|exists:locations,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
        ]);

        $delivery = Delivery::create([
            'supplier_id' => $this->supplierId,
            'user_id' => auth()->id(),
            'status' => DeliveryStatus::Pending,
            'reference' => $this->reference ?: null,
            'notes' => $this->notes ?: null,
        ]);

        foreach ($this->items as $item) {
            $delivery->items()->create([
                'product_id' => $item['product_id'],
                'location_id' => $item['location_id'],
                'quantity_ordered' => $item['quantity_ordered'],
                'quantity_received' => 0,
            ]);
        }

        activity()->causedBy(auth()->user())->performedOn($delivery)->log('created');

        $this->redirect(route('deliveries.show', $delivery), navigate: true);
    }

    public function render()
    {
        return view('livewire.deliveries.create');
    }
}
