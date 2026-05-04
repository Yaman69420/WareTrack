<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
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

    public ?int $filterCategory = null;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public ?int $categoryId = null;

    public string $description = '';

    public ?int $minStock = 0;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with('category')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%"))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->latest()
            ->paginate(10);
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'sku', 'categoryId', 'description', 'minStock', 'editingId']);
        $this->minStock = 0;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(Product $product): void
    {
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->categoryId = $product->category_id;
        $this->description = $product->description ?? '';
        $this->minStock = $product->min_stock;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $skuRule = $this->editingId
            ? "required|string|max:50|unique:products,sku,{$this->editingId}"
            : 'required|string|max:50|unique:products,sku';

        $this->validate([
            'name' => 'required|string|max:150',
            'sku' => $skuRule,
            'categoryId' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'minStock' => 'nullable|integer|min:0',
        ]);

        $data = [
            'name' => $this->name,
            'sku' => strtoupper($this->sku),
            'category_id' => $this->categoryId,
            'description' => $this->description ?: null,
            'min_stock' => $this->minStock ?? 0,
        ];

        if ($this->editingId) {
            $product = Product::findOrFail($this->editingId);
            $product->update($data);
            activity()->causedBy(auth()->user())->performedOn($product)->log('updated');
            Flux::toast(__('Product updated.'), variant: 'success');
        } else {
            $product = Product::create($data);
            activity()->causedBy(auth()->user())->performedOn($product)->log('created');
            Flux::toast(__('Product created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['name', 'sku', 'categoryId', 'description', 'minStock', 'editingId']);
        unset($this->products);
    }

    public function delete(Product $product): void
    {
        $product->delete();
        activity()->causedBy(auth()->user())->performedOn($product)->log('deleted');
        Flux::toast(__('Product deleted.'), variant: 'success');
        unset($this->products);
    }

    public function render()
    {
        return view('livewire.products.index');
    }
}
