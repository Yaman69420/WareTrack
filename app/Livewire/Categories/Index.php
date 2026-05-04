<?php

namespace App\Livewire\Categories;

use App\Models\Category;
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

    #[Rule('nullable|string|max:500')]
    public string $description = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount('products')
            ->latest()
            ->paginate(10);
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'description', 'editingId']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(Category $category): void
    {
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $category = Category::findOrFail($this->editingId);
            $category->update(['name' => $this->name, 'description' => $this->description ?: null]);
            activity()->causedBy(auth()->user())->performedOn($category)->log('updated');
            Flux::toast(__('Category updated.'), variant: 'success');
        } else {
            $category = Category::create(['name' => $this->name, 'description' => $this->description ?: null]);
            activity()->causedBy(auth()->user())->performedOn($category)->log('created');
            Flux::toast(__('Category created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['name', 'description', 'editingId']);
        unset($this->categories);
    }

    public function delete(Category $category): void
    {
        $category->delete();
        activity()->causedBy(auth()->user())->performedOn($category)->log('deleted');
        Flux::toast(__('Category deleted.'), variant: 'success');
        unset($this->categories);
    }

    public function render()
    {
        return view('livewire.categories.index');
    }
}
