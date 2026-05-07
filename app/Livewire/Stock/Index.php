<?php

namespace App\Livewire\Stock;

use App\Models\Product;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $filterWarehouse = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterWarehouse(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get();
    }

    #[Computed]
    public function stockLines()
    {
        return Product::query()
            ->with(['category', 'stock.location.warehouse'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%"))
            ->when($this->filterWarehouse, function ($q) {
                $q->whereHas('stock.location', fn ($lq) => $lq->where('warehouse_id', $this->filterWarehouse));
            })
            ->orderBy('name')
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.stock.index');
    }
}
