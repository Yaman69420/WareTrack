<?php

namespace App\Livewire\Locations;

use App\Models\Location;
use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $filterWarehouse = null;

    public bool $showModal = false;

    public ?int $editingId = null;

    #[Rule('required|exists:warehouses,id')]
    public ?int $warehouseId = null;

    #[Rule('required|string|max:20')]
    public string $code = '';

    #[Rule('nullable|string|max:100')]
    public string $name = '';

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
    public function locations()
    {
        return Location::query()
            ->with('warehouse')
            ->when($this->search, fn ($q) => $q->where('code', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%"))
            ->when($this->filterWarehouse, fn ($q) => $q->where('warehouse_id', $this->filterWarehouse))
            ->withCount('products')
            ->latest()
            ->paginate(10);
    }

    public function openCreate(): void
    {
        $this->reset(['warehouseId', 'code', 'name', 'editingId']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(Location $location): void
    {
        $this->editingId = $location->id;
        $this->warehouseId = $location->warehouse_id;
        $this->code = $location->code;
        $this->name = $location->name ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'warehouse_id' => $this->warehouseId,
            'code' => strtoupper($this->code),
            'name' => $this->name ?: null,
        ];

        if ($this->editingId) {
            $location = Location::findOrFail($this->editingId);
            $location->update($data);
            activity()->causedBy(auth()->user())->performedOn($location)->log('updated');
            Flux::toast(__('Location updated.'), variant: 'success');
        } else {
            $location = Location::create($data);
            activity()->causedBy(auth()->user())->performedOn($location)->log('created');
            Flux::toast(__('Location created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['warehouseId', 'code', 'name', 'editingId']);
        unset($this->locations);
    }

    public function delete(Location $location): void
    {
        $activeStock = $location->stock()->where('quantity', '>', 0)->count();

        if ($activeStock > 0) {
            Flux::toast(
                __('Cannot delete: :count product(s) still have stock at this location.', ['count' => $activeStock]),
                variant: 'danger'
            );

            return;
        }

        $location->delete();
        activity()->causedBy(auth()->user())->performedOn($location)->log('deleted');
        Flux::toast(__('Location deleted.'), variant: 'success');
        unset($this->locations);
    }

    public function render()
    {
        return view('livewire.locations.index');
    }
}
