<?php

namespace App\Livewire\Suppliers;

use App\Models\Product;
use App\Models\Supplier;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    // Create/Edit modal
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $notes = '';

    public array $selectedProductIds = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function allProducts()
    {
        return Product::orderBy('name')->get(['id', 'name', 'sku']);
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'email', 'phone', 'address', 'notes', 'editingId', 'selectedProductIds']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(Supplier $supplier): void
    {
        $this->editingId = $supplier->id;
        $this->name = $supplier->name;
        $this->email = $supplier->email ?? '';
        $this->phone = $supplier->phone ?? '';
        $this->address = $supplier->address ?? '';
        $this->notes = $supplier->notes ?? '';
        $this->selectedProductIds = $supplier->products()->pluck('products.id')->map(fn ($id) => (string) $id)->toArray();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        // Route is shared with workers — the policy is the real gate, not the hidden UI buttons
        if ($this->editingId) {
            $this->authorize('update', Supplier::findOrFail($this->editingId));
        } else {
            $this->authorize('create', Supplier::class);
        }

        $this->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->editingId) {
            $supplier = Supplier::findOrFail($this->editingId);
            $supplier->update($data);
            $supplier->products()->sync($this->selectedProductIds);
            activity()->causedBy(auth()->user())->performedOn($supplier)->log('updated');
            Flux::toast(__('Supplier updated.'), variant: 'success');
        } else {
            $supplier = Supplier::create($data);
            $supplier->products()->sync($this->selectedProductIds);
            activity()->causedBy(auth()->user())->performedOn($supplier)->log('created');
            Flux::toast(__('Supplier created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['name', 'email', 'phone', 'address', 'notes', 'editingId', 'selectedProductIds']);
        unset($this->suppliers);
    }

    public function delete(Supplier $supplier): void
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();
        activity()->causedBy(auth()->user())->performedOn($supplier)->log('deleted');
        Flux::toast(__('Supplier deleted.'), variant: 'success');
        unset($this->suppliers);
    }

    public function render()
    {
        return view('livewire.suppliers.index');
    }
}
