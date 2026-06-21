<?php

namespace App\Livewire\Deliveries;

use App\Enums\DeliveryStatus;
use App\Livewire\Concerns\WithSorting;
use App\Models\Delivery;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Overzicht van alle leveringen met statusfilter en paginatie.
 *
 * Leesvenster voor zowel admins als workers: vanaf hier navigeert een worker
 * naar Show om een binnenkomende levering te verwerken. Bewust geen guard —
 * lezen mag voor iedereen, de schrijfacties zitten achter policies in
 * Create en Show.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;
    use WithSorting;

    /** Kolommen waarop gesorteerd mag worden (whitelist voor orderBy). */
    protected array $sortable = ['reference', 'status', 'created_at'];

    // Gekozen statusfilter; lege string betekent "alle statussen tonen"
    public string $filterStatus = '';

    /**
     * Livewire-hook die afgaat telkens de statusfilter wijzigt.
     */
    public function updatedFilterStatus(): void
    {
        // Terug naar pagina 1: na filteren kan de huidige pagina buiten bereik vallen
        // en zou de lijst onterecht leeg lijken.
        $this->resetPage();
    }

    /**
     * Gepagineerde leveringen, nieuwste eerst, optioneel beperkt tot één status.
     */
    #[Computed]
    public function deliveries()
    {
        return Delivery::query()
            // Eager loading: de tabel toont per rij leverancier, aanmaker en itemtelling,
            // zonder with() zou dat drie extra queries per levering kosten (N+1).
            ->with(['supplier', 'user', 'items'])
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            // Klikbare kolomkoppen; zonder keuze blijft nieuwste-eerst de default.
            ->tap(fn ($q) => $this->applySort($q, 'created_at', 'desc'))
            ->paginate(15);
    }

    /**
     * Rij-klik in de tabel: navigeer naar de detailpagina van de levering.
     * SPA-navigatie (navigate: true) houdt de ervaring gelijk aan wire:navigate-links.
     */
    public function open(Delivery $delivery): void
    {
        $this->redirectRoute('deliveries.show', $delivery, navigate: true);
    }

    /**
     * Alle mogelijke leveringsstatussen (enum-cases) voor de filterdropdown.
     */
    #[Computed]
    public function statuses(): array
    {
        return DeliveryStatus::cases();
    }

    /**
     * Rendert het leveringenoverzicht.
     */
    public function render()
    {
        return view('livewire.deliveries.index');
    }
}
