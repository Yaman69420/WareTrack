<?php

namespace App\Livewire\Stock;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Historiek van alle stockbewegingen — het audit-spoor van de voorraad.
 *
 * Puur leescomponent: bewegingen worden nooit aangepast of verwijderd,
 * enkel doorzocht en gefilterd. Samen met de stock-tabel maakt deze lijst
 * elke voorraadstand verklaarbaar.
 */
#[Layout('layouts.app')]
class Movements extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterType = '';

    // Terug naar pagina 1 bij een nieuwe zoekterm of filter: de huidige pagina
    // bestaat mogelijk niet meer binnen de gefilterde resultaten.
    /** Reset de paginering wanneer de zoekterm wijzigt. */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** Reset de paginering wanneer de typefilter wijzigt. */
    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    /**
     * Gepagineerde bewegingslijst, nieuwste eerst, doorzoekbaar op product.
     */
    #[Computed]
    public function movements()
    {
        return StockMovement::query()
            // Alle drie de locatierelaties eager: welke gevuld is hangt af van het
            // type (transfer = from/to, rest = location). Zonder dit N+1 per rij.
            ->with(['product', 'location.warehouse', 'fromLocation.warehouse', 'toLocation.warehouse', 'user'])
            ->when($this->search, function ($q) {
                // Zoeken op naam óf SKU; de OR blijft binnen de whereHas-subquery
                // en kan dus de typefilter hieronder niet omzeilen.
                $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%"));
            })
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->latest()
            ->paginate(25);
    }

    /** Alle bewegingstypes uit de enum voor de filterdropdown. */
    #[Computed]
    public function types(): array
    {
        return StockMovementType::cases();
    }

    /** Tekent de bewegingshistoriek; de pagina-layout komt uit het #[Layout]-attribuut. */
    public function render()
    {
        return view('livewire.stock.movements');
    }
}
