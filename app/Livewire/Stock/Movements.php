<?php

namespace App\Livewire\Stock;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Movements extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterType = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function movements()
    {
        return StockMovement::query()
            ->with(['product', 'location.warehouse', 'fromLocation.warehouse', 'toLocation.warehouse', 'user'])
            ->when($this->search, function ($q) {
                $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%"));
            })
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function types(): array
    {
        return StockMovementType::cases();
    }

    public function render()
    {
        return view('livewire.stock.movements');
    }
}
