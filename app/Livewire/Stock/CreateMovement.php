<?php

namespace App\Livewire\Stock;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
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

    // Warehouse step (non-transfer)
    public ?int $warehouseId = null;

    public ?int $locationId = null;

    // Warehouse step (transfer)
    public ?int $fromWarehouseId = null;

    public ?int $fromLocationId = null;

    public ?int $toWarehouseId = null;

    public ?int $toLocationId = null;

    public int $quantity = 1;

    public string $reference = '';

    public string $notes = '';

    // Reset location when warehouse changes
    public function updatedWarehouseId(): void
    {
        $this->locationId = null;
    }

    public function updatedFromWarehouseId(): void
    {
        $this->fromLocationId = null;
    }

    public function updatedToWarehouseId(): void
    {
        $this->toLocationId = null;
    }

    // Reset warehouse + location when type changes
    public function updatedType(): void
    {
        $this->warehouseId = null;
        $this->locationId = null;
        $this->fromWarehouseId = null;
        $this->fromLocationId = null;
        $this->toWarehouseId = null;
        $this->toLocationId = null;
    }

    #[Computed]
    public function products()
    {
        return Product::orderBy('name')->get(['id', 'name', 'sku']);
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function locations()
    {
        return $this->warehouseId
            ? Location::where('warehouse_id', $this->warehouseId)->orderBy('code')->get()
            : collect();
    }

    #[Computed]
    public function fromLocations()
    {
        return $this->fromWarehouseId
            ? Location::where('warehouse_id', $this->fromWarehouseId)->orderBy('code')->get()
            : collect();
    }

    #[Computed]
    public function toLocations()
    {
        return $this->toWarehouseId
            ? Location::where('warehouse_id', $this->toWarehouseId)->orderBy('code')->get()
            : collect();
    }

    #[Computed]
    public function types(): array
    {
        return StockMovementType::cases();
    }

    /**
     * Current stock for the selected product + location (shown as a hint).
     */
    #[Computed]
    public function currentStock(): ?int
    {
        if (! $this->productId || ! $this->locationId) {
            return null;
        }

        return Stock::where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity') ?? 0;
    }

    public function save(StockService $stockService): void
    {
        $this->validate([
            'type' => 'required|in:incoming,outgoing,transfer,correction',
            'productId' => 'required|exists:products,id',
            'quantity' => 'required|integer|' . ($this->type === 'correction' ? 'min:0' : 'min:1'),
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
            $this->addError('quantity', __('Insufficient stock: ').$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.stock.create-movement');
    }
}
