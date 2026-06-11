<?php

namespace App\Livewire\Deliveries;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Aanmaakformulier voor een verwachte levering (admin-only).
 *
 * De levering wordt hier enkel geregistreerd met status Pending; de effectieve
 * stockverhoging gebeurt pas bij het verwerken in Deliveries\Show. Itemrijen
 * verschijnen bewust pas ná de leverancierkeuze, zodat de productdropdown
 * altijd gefilterd is op wat die leverancier effectief levert.
 */
#[Layout('layouts.app')]
class Create extends Component
{
    public ?int $supplierId = null;

    public string $reference = '';

    public string $notes = '';

    // Itemrijen als platte arrays (product, locatie, besteld aantal); de UI bindt per index
    public array $items = [];

    /**
     * Wisselt de gebruiker van leverancier, dan vervallen alle itemrijen.
     * Zo kan er nooit een product van de vórige leverancier blijven hangen
     * in een rij terwijl de dropdown intussen andere producten toont.
     */
    public function updatedSupplierId(): void
    {
        $this->items = [];
        $this->addItem();
    }

    /**
     * Controleert bij de eerste paginalading of de gebruiker leveringen mag aanmaken.
     */
    public function mount(): void
    {
        // Leveringen aanmaken is admin-only (DeliveryPolicy); workers verwerken ze enkel
        $this->authorize('create', Delivery::class);

        // Bewust geen addItem() hier: rijen verschijnen pas na leverancierkeuze
        // (zie updatedSupplierId), anders staat er een rij zonder bruikbare productlijst.
    }

    /**
     * Alle leveranciers, alfabetisch gesorteerd, voor de leverancierdropdown.
     */
    #[Computed]
    public function suppliers()
    {
        return Supplier::orderBy('name')->get();
    }

    /**
     * Productlijst voor de itemrijen, gefilterd op de gekozen leverancier
     * via de supplier_product-pivot. Zonder leverancier: lege collectie,
     * de UI toont dan geen itemrijen.
     */
    #[Computed]
    public function products()
    {
        if ($this->supplierId) {
            $supplier = Supplier::find($this->supplierId);
            $products = $supplier?->products()->orderBy('name')->get() ?? collect();

            // Fallback naar álle producten als de leverancier er nog geen gekoppeld heeft:
            // een lege dropdown zou het aanmaken volledig blokkeren voor nieuwe leveranciers.
            return $products->isNotEmpty() ? $products : Product::orderBy('name')->get();
        }

        return collect();
    }

    /**
     * Alle locaties met hun magazijn (eager loaded), op code gesorteerd, voor de locatiedropdowns.
     */
    #[Computed]
    public function locations()
    {
        return Location::with('warehouse')->orderBy('code')->get();
    }

    /**
     * Voegt een lege itemrij toe; standaardaantal 1 omdat 0 de validatie (min:1) toch niet haalt.
     */
    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => null,
            'location_id' => null,
            'quantity_ordered' => 1,
        ];
    }

    /**
     * Verwijdert de itemrij op de gegeven index. array_splice herindexeert de array,
     * zodat de wire:model-bindingen per index blijven kloppen na het verwijderen.
     */
    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
    }

    /**
     * Slaat de levering met items op. De levering start altijd als Pending
     * met quantity_received op 0 — ontvangst wordt pas geregistreerd bij het
     * verwerken in Show, waar ook de stock effectief verhoogd wordt.
     */
    public function save(): void
    {
        // Guard herhaald in de actie zelf: mount() beschermt enkel de eerste paginalading,
        // een latere Livewire-actierequest kan rechtstreeks deze methode aanroepen.
        $this->authorize('create', Delivery::class);

        $this->validate([
            'supplierId' => 'required|exists:suppliers,id',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.location_id' => 'required|exists:locations,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
        ]);

        $delivery = Delivery::create([
            'supplier_id' => $this->supplierId,
            'user_id' => auth()->id(),
            'status' => DeliveryStatus::Pending,
            // Lege strings worden NULL, zodat "niet ingevuld" eenduidig is in de databank
            'reference' => $this->reference ?: null,
            'notes' => $this->notes ?: null,
        ]);

        foreach ($this->items as $item) {
            $delivery->items()->create([
                'product_id' => $item['product_id'],
                'location_id' => $item['location_id'],
                'quantity_ordered' => $item['quantity_ordered'],
                // Expliciet 0 i.p.v. een DB-default: het verschil ordered/received
                // is de kern van de partial-flow en hoort zichtbaar in de code te staan.
                'quantity_received' => 0,
            ]);
        }

        activity()->causedBy(auth()->user())->performedOn($delivery)->log('created');

        $this->redirect(route('deliveries.show', $delivery), navigate: true);
    }

    /**
     * Rendert het aanmaakformulier voor leveringen.
     */
    public function render()
    {
        return view('livewire.deliveries.create');
    }
}
