<?php

namespace App\Livewire\Warehouses;

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

    public bool $showModal = false;

    public ?int $editingId = null;

    #[Rule('required|string|max:100')]
    public string $name = '';

    #[Rule('required|string|max:100')]
    public string $location = '';

    #[Rule('nullable|string|max:500')]
    public string $description = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('location', 'like', "%{$this->search}%"))
            ->withCount('locations')
            ->latest()
            ->paginate(10);
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'location', 'description', 'editingId']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(Warehouse $warehouse): void
    {
        $this->editingId = $warehouse->id;
        $this->name = $warehouse->name;
        $this->location = $warehouse->location;
        $this->description = $warehouse->description ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $warehouse = Warehouse::findOrFail($this->editingId);
            $warehouse->update([
                'name' => $this->name,
                'location' => $this->location,
                'description' => $this->description ?: null,
            ]);
            activity()->causedBy(auth()->user())->performedOn($warehouse)->log('updated');
            Flux::toast(__('Warehouse updated.'), variant: 'success');
        } else {
            $warehouse = Warehouse::create([
                'name' => $this->name,
                'location' => $this->location,
                'description' => $this->description ?: null,
            ]);
            activity()->causedBy(auth()->user())->performedOn($warehouse)->log('created');
            Flux::toast(__('Warehouse created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['name', 'location', 'description', 'editingId']);
        unset($this->warehouses);
    }

    public function delete(Warehouse $warehouse): void
    {
        $warehouse->delete();
        activity()->causedBy(auth()->user())->performedOn($warehouse)->log('deleted');
        Flux::toast(__('Warehouse deleted.'), variant: 'success');
        unset($this->warehouses);
    }

    public function render()
    {
        return view('livewire.warehouses.index');
    }
}
