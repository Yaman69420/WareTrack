<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['category', 'stock.location.warehouse']);
    }

    /**
     * Stock breakdown per location, sorted by warehouse then location code.
     */
    #[Computed]
    public function stockLines()
    {
        return $this->product->stock()
            ->with('location.warehouse')
            ->get()
            ->sortBy([
                fn ($a, $b) => strcmp($a->location->warehouse->name ?? '', $b->location->warehouse->name ?? ''),
                fn ($a, $b) => strcmp($a->location->code, $b->location->code),
            ]);
    }

    /**
     * Recent movements for this product (last 25).
     */
    #[Computed]
    public function movements()
    {
        return StockMovement::where('product_id', $this->product->id)
            ->with(['location.warehouse', 'fromLocation.warehouse', 'toLocation.warehouse', 'user'])
            ->latest()
            ->limit(25)
            ->get();
    }

    /**
     * Total stock across all locations.
     */
    #[Computed]
    public function totalStock(): int
    {
        return $this->product->stock->sum('quantity');
    }

    /**
     * Whether the product is below its minimum stock.
     */
    #[Computed]
    public function isBelowMinStock(): bool
    {
        return $this->product->min_stock > 0 && $this->totalStock < $this->product->min_stock;
    }

    public function imageUrl(): ?string
    {
        return $this->product->image_path
            ? Storage::url($this->product->image_path)
            : null;
    }

    public function render()
    {
        return view('livewire.products.show', ['title' => $this->product->name]);
    }
}
