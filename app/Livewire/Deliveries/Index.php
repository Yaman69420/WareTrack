<?php

namespace App\Livewire\Deliveries;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function deliveries()
    {
        return Delivery::query()
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
