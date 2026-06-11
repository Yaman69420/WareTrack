<?php

namespace App\Livewire\Deliveries;

use App\Enums\DeliveryStatus;
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

    public string $filterStatus = '';

    public function updatedFilterStatus(): void
    {
        // Terug naar pagina 1: na filteren kan de huidige pagina buiten bereik vallen
        // en zou de lijst onterecht leeg lijken.
        $this->resetPage();
    }

    #[Computed]
    public function deliveries()
    {
        return Delivery::query()
            // Eager loading: de tabel toont per rij leverancier, aanmaker en itemtelling,
            // zonder with() zou dat drie extra queries per levering kosten (N+1).
            ->with(['supplier', 'user', 'items'])
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function statuses(): array
    {
        return DeliveryStatus::cases();
    }

    public function render()
    {
        return view('livewire.deliveries.index');
    }
}
