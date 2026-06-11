<?php

namespace App\Livewire\Stock;

use App\Models\Product;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Voorraadoverzicht: huidige stand per product over alle locaties heen.
 *
 * Leescomponent zonder mutaties — wijzigingen lopen via CreateMovement of
 * BulkCorrection. Het overzicht vertrekt vanuit Product (niet Stock), zodat
 * ook producten zónder voorraad zichtbaar blijven in de lijst.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $filterWarehouse = null;

    // Terug naar pagina 1 bij een nieuwe zoekterm of filter: de huidige pagina
    // bestaat mogelijk niet meer binnen de gefilterde resultaten.
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

    /**
     * Gepagineerde productlijst met per product de stock per locatie.
     *
     * De warehouse-filter werkt met whereHas (heeft het product stock in dat
     * magazijn?) en niet met een join: zo blijft elke rij één product en
     * verschijnen er geen dubbels in de paginering.
     */
    #[Computed]
    public function stockLines()
    {
        return Product::query()
            // Eager loading van de hele keten t.e.m. warehouse: de tabel toont per
            // stocklijn de magazijnnaam, zonder dit wordt dat een N+1 per rij.
            ->with(['category', 'stock.location.warehouse'])
            // Geneste groep rond de OR-zoekvoorwaarden, anders omzeilt een
            // naam-match de magazijnfilter hieronder (AND/OR-precedentie).
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")))
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
