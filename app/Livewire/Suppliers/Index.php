<?php

namespace App\Livewire\Suppliers;

use App\Livewire\Concerns\WithSorting;
use App\Models\Product;
use App\Models\Supplier;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Volledig leveranciersbeheer in één component: lijst, zoeken, en CRUD via modal.
 *
 * Aparte Create/Edit-pagina's zouden voor dit kleine formulier overkill zijn;
 * één modal met een nullable editingId dekt beide gevallen. Hier worden ook de
 * productkoppelingen (supplier_product-pivot) beheerd waarop Deliveries\Create
 * zijn productdropdown filtert.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;
    use WithSorting;

    /** Kolommen waarop gesorteerd mag worden (whitelist voor orderBy). */
    protected array $sortable = ['name', 'email', 'created_at'];

    public string $search = '';

    // Eén modal voor create én edit: editingId null = create, gevuld = edit
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $notes = '';

    public array $selectedProductIds = [];

    /**
     * Livewire-hook die afgaat telkens de zoekterm wijzigt.
     */
    public function updatedSearch(): void
    {
        // Terug naar pagina 1 bij elke zoekterm, anders kan de gebruiker op een
        // pagina staan die na het filteren niet meer bestaat.
        $this->resetPage();
    }

    /**
     * Alle producten (enkel id, naam en sku) voor de koppelcheckboxen in de modal.
     */
    #[Computed]
    public function allProducts()
    {
        return Product::orderBy('name')->get(['id', 'name', 'sku']);
    }

    /**
     * Gepagineerde leverancierslijst, nieuwste eerst, doorzoekbaar op naam of e-mail.
     */
    #[Computed]
    public function suppliers()
    {
        return Supplier::query()
            // OR-zoekvoorwaarden gegroepeerd zodat een latere AND-filter niet
            // door de OR omzeild kan worden (AND/OR-precedentie).
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            // Klikbare kolomkoppen; zonder keuze blijft nieuwste-eerst de default.
            ->tap(fn ($q) => $this->applySort($q))
            ->paginate(10);
    }

    /**
     * Opent de modal in create-modus: alle velden leeg en oude validatiefouten gewist,
     * zodat er geen restanten van een eerdere edit-sessie blijven staan.
     */
    public function openCreate(): void
    {
        $this->reset(['name', 'email', 'phone', 'address', 'notes', 'editingId', 'selectedProductIds']);
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Opent de modal in edit-modus, voorgevuld met de gegevens en
     * productkoppelingen van de gekozen leverancier.
     */
    public function openEdit(Supplier $supplier): void
    {
        $this->editingId = $supplier->id;
        $this->name = $supplier->name;
        $this->email = $supplier->email ?? '';
        $this->phone = $supplier->phone ?? '';
        $this->address = $supplier->address ?? '';
        $this->notes = $supplier->notes ?? '';
        // 'products.id' expliciet geprefixt: dit is een belongsToMany-query die joint met
        // de pivot supplier_product, dus 'id' alleen zou dubbelzinnig zijn — products.id maakt
        // ondubbelzinnig dat we de id uit de products-tabel willen.
        // Cast naar string omdat checkbox-bindings strings opleveren — anders matcht
        // de bestaande selectie niet en lijken alle vinkjes uit te staan.
        $this->selectedProductIds = $supplier->products()->pluck('products.id')->map(fn ($id) => (string) $id)->toArray();
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Slaat een nieuwe of bestaande leverancier op, inclusief productkoppelingen.
     * sync() vervangt de volledige pivot-set door de huidige selectie — koppelingen
     * uitvinken verwijdert ze dus ook, zonder aparte detach-logica.
     */
    public function save(): void
    {
        // De route is gedeeld met workers — de policy is de echte poort,
        // niet de in de UI verborgen knoppen.
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

        // Lege strings worden NULL, zodat optionele velden eenduidig leeg zijn in de databank
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
        // #[Computed] cachet per request: unset gooit de cache weg zodat de lijst
        // nog in dezelfde render de wijziging toont.
        unset($this->suppliers);
    }

    /**
     * Verwijdert een leverancier na policy-check; de unset leegt de computed-cache
     * zodat de lijst meteen zonder de verwijderde leverancier rendert.
     */
    public function delete(Supplier $supplier): void
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();
        activity()->causedBy(auth()->user())->performedOn($supplier)->log('deleted');
        Flux::toast(__('Supplier deleted.'), variant: 'success');
        unset($this->suppliers);
    }

    /**
     * Rendert de leverancierspagina met lijst en modal.
     */
    public function render()
    {
        return view('livewire.suppliers.index');
    }
}
