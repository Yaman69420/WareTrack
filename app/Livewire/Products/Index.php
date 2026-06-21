<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\WithSorting;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Beheerscherm voor de productcatalogus: zoeken, filteren op categorie en
 * CRUD via modals, inclusief afbeelding-upload en het koppelen van locaties.
 *
 * Dit component beheert enkel stamgegevens. Voorraadhoeveelheden worden hier
 * bewust nooit aangepast — elke stockmutatie loopt via de StockService zodat
 * de transactie- en auditlogica op één plek blijft.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads, WithPagination;
    use WithSorting;

    /** Kolommen waarop gesorteerd mag worden (whitelist voor orderBy). */
    protected array $sortable = ['name', 'sku', 'min_stock', 'created_at'];

    public string $search = '';

    public ?int $filterCategory = null;

    // Create/Edit modal
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public ?int $categoryId = null;

    public string $description = '';

    public ?int $minStock = 0;

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    public ?string $existingImagePath = null;

    // Locations modal
    public bool $showLocationsModal = false;

    public ?int $managingProductId = null;

    public array $selectedLocations = [];

    /**
     * Terug naar pagina 1 zodra de zoekterm wijzigt: wie op pagina 5 stond,
     * zou anders een lege pagina van het nieuwe (kortere) resultaat zien.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Zelfde paginareset bij een wijziging van de categoriefilter.
     */
    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    /**
     * Alle categorieën, alfabetisch, voor de filter-dropdown en het formulier.
     */
    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    /**
     * Doorzoekbare, gefilterde en gepagineerde productlijst, nieuwste eerst.
     * #[Computed] cachet het resultaat per request, hoe vaak de view het ook leest.
     */
    #[Computed]
    public function products()
    {
        return Product::query()
            // Eager loading van de categorie: zonder with() zou elke rij in de
            // tabel een extra query veroorzaken (N+1-probleem).
            ->with('category')
            // De OR-zoekvoorwaarden in een geneste groep: zonder die groep zou
            // 'name LIKE ... OR sku LIKE ... AND category_id = ...' door de
            // AND/OR-precedentie de categoriefilter omzeilen bij een naam-match.
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            // Klikbare kolomkoppen; zonder keuze blijft nieuwste-eerst de default.
            ->tap(fn ($q) => $this->applySort($q))
            ->paginate(10);
    }

    /**
     * Magazijnen met hun locaties, voor de checkboxlijst in de locatie-modal
     * (één groep per magazijn).
     */
    #[Computed]
    public function warehousesWithLocations()
    {
        return Warehouse::with('locations')->orderBy('name')->get();
    }

    /**
     * Het product waarvan de locaties in de modal beheerd worden, of null
     * zolang de locatie-modal gesloten is.
     */
    #[Computed]
    public function managingProduct(): ?Product
    {
        return $this->managingProductId
            ? Product::with('locations')->find($this->managingProductId)
            : null;
    }

    /**
     * Opent de modal in create-modus: alle formuliervelden en oude
     * validatiefouten worden eerst gewist.
     */
    public function openCreate(): void
    {
        $this->reset(['name', 'sku', 'categoryId', 'description', 'minStock', 'editingId', 'image', 'existingImagePath']);
        $this->minStock = 0;
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Opent de modal in edit-modus, voorgevuld met de gegevens van het product.
     */
    public function openEdit(Product $product): void
    {
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->categoryId = $product->category_id;
        $this->description = $product->description ?? '';
        $this->minStock = $product->min_stock;
        $this->image = null;
        $this->existingImagePath = $product->image_path;
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Opent de locatie-modal met de huidige koppelingen alvast aangevinkt.
     */
    public function openLocations(Product $product): void
    {
        $this->managingProductId = $product->id;
        // Cast naar string: Livewire bindt checkbox-waarden als strings, dus
        // integer-id's zouden nooit matchen en geen enkel vinkje zou aanstaan.
        $this->selectedLocations = $product->locations()->pluck('locations.id')->map(fn ($id) => (string) $id)->toArray();
        // Computed-cache legen zodat de modal niet het vorige product toont.
        unset($this->managingProduct);
        $this->showLocationsModal = true;
    }

    /**
     * Slaat de locatie-koppelingen op. sync() vervangt de volledige set in de
     * pivot-tabel: niet-aangevinkte locaties worden dus bewust ontkoppeld.
     */
    public function saveLocations(): void
    {
        $product = Product::findOrFail($this->managingProductId);
        $this->authorize('update', $product);
        $product->locations()->sync($this->selectedLocations);
        activity()->causedBy(auth()->user())->performedOn($product)->log('locations updated');
        Flux::toast(__('Locations updated.'), variant: 'success');
        $this->showLocationsModal = false;
        $this->reset(['managingProductId', 'selectedLocations']);
        unset($this->managingProduct, $this->products);
    }

    /**
     * Eén save-methode voor create én update; $editingId bepaalt de modus.
     * Valideert de afbeelding strikt (type-whitelist + groottelimiet) en ruimt
     * bij vervanging het oude bestand op de disk op.
     */
    public function save(): void
    {
        // Bij bewerken moet de unique-check het eigen record negeren, anders
        // zou elke update falen op de bestaande (eigen) SKU.
        $skuRule = $this->editingId
            ? "required|string|max:50|unique:products,sku,{$this->editingId}"
            : 'required|string|max:50|unique:products,sku';

        $this->validate([
            'name' => 'required|string|max:150',
            'sku' => $skuRule,
            'categoryId' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'minStock' => 'nullable|integer|min:0',
            // Whitelist van afbeeldingstypes plus cap van 2 MB: 'image' checkt de
            // echte inhoud (niet enkel de extensie), de limiet beschermt de server.
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $data = [
            'name' => $this->name,
            // SKU genormaliseerd naar hoofdletters: zo kunnen 'abc-1' en 'ABC-1'
            // nooit als twee verschillende producten bestaan.
            'sku' => strtoupper($this->sku),
            'category_id' => $this->categoryId,
            'description' => $this->description ?: null,
            'min_stock' => $this->minStock ?? 0,
        ];

        if ($this->image) {
            // store() genereert een willekeurige bestandsnaam: gebruikers kunnen
            // zo nooit elkaars bestanden overschrijven via een gekozen naam.
            $data['image_path'] = $this->image->store('products', 'public');
        }

        if ($this->editingId) {
            $product = Product::findOrFail($this->editingId);
            $this->authorize('update', $product);

            // Oude afbeelding pas verwijderen nadat de nieuwe veilig is
            // opgeslagen; zonder deze opruiming blijven verweesde bestanden
            // de publieke disk vullen.
            if ($this->image && $product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->update($data);
            activity()->causedBy(auth()->user())->performedOn($product)->log('updated');
            Flux::toast(__('Product updated.'), variant: 'success');
        } else {
            $this->authorize('create', Product::class);

            $product = Product::create($data);
            activity()->causedBy(auth()->user())->performedOn($product)->log('created');
            Flux::toast(__('Product created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['name', 'sku', 'categoryId', 'description', 'minStock', 'editingId', 'image', 'existingImagePath']);
        // Computed-cache legen zodat de lijst de wijziging meteen toont.
        unset($this->products);
    }

    /**
     * Soft delete: het product verdwijnt uit de lijsten, maar historische
     * stockbewegingen en audit-records blijven ernaar verwijzen.
     */
    public function delete(Product $product): void
    {
        $this->authorize('delete', $product);

        $product->delete();
        activity()->causedBy(auth()->user())->performedOn($product)->log('deleted');
        Flux::toast(__('Product deleted.'), variant: 'success');
        unset($this->products);
    }

    /**
     * Rendert de productlijst-view; de data komt uit de computed properties.
     */
    public function render()
    {
        return view('livewire.products.index');
    }
}
