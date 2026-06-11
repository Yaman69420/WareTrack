<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Beheerscherm voor productcategorieën: zoeken plus CRUD via een modal.
 *
 * Bevat de delete-bescherming die voorkomt dat een categorie verdwijnt
 * terwijl er nog producten aan gekoppeld zijn — de integriteit wordt hier
 * in de applicatielaag bewaakt, met een duidelijke melding voor de gebruiker.
 */
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

    /**
     * Terug naar pagina 1 bij een nieuwe zoekterm, anders kan de gebruiker op
     * een lege pagina van het gefilterde resultaat belanden.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Doorzoekbare, gepagineerde categorielijst met het productaantal per rij.
     */
    #[Computed]
    public function categories()
    {
        return Category::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            // withCount levert het productaantal als subquery: één query voor
            // de hele pagina in plaats van een count per rij.
            ->withCount('products')
            ->latest()
            ->paginate(10);
    }

    /**
     * Opent de modal in create-modus met een leeg formulier en zonder oude
     * validatiefouten.
     */
    public function openCreate(): void
    {
        $this->reset(['name', 'description', 'editingId']);
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Opent de modal in edit-modus, voorgevuld met de bestaande gegevens.
     */
    public function openEdit(Category $category): void
    {
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Eén save-methode voor create én update; $editingId bepaalt de modus.
     * De validatieregels staan als #[Rule]-attributen op de properties zelf.
     */
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
        // Computed-cache legen zodat de lijst de wijziging meteen toont.
        unset($this->categories);
    }

    /**
     * Verwijdert een categorie, maar enkel als er geen producten meer aan
     * hangen. De guard zit hier (en niet enkel in de databank) zodat de
     * gebruiker een verstaanbare melding krijgt in plaats van een SQL-fout.
     */
    public function delete(Category $category): void
    {
        $productCount = $category->products()->count();

        if ($productCount > 0) {
            // Danger-toast mét het aantal: de gebruiker weet meteen waarom het
            // niet mag en hoeveel producten eerst verplaatst moeten worden.
            Flux::toast(
                __('Cannot delete: :count product(s) are still linked to this category.', ['count' => $productCount]),
                variant: 'danger'
            );

            return;
        }

        $category->delete();
        activity()->causedBy(auth()->user())->performedOn($category)->log('deleted');
        Flux::toast(__('Category deleted.'), variant: 'success');
        unset($this->categories);
    }

    /**
     * Rendert de categorielijst-view.
     */
    public function render()
    {
        return view('livewire.categories.index');
    }
}
