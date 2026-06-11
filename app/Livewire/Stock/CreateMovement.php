<?php

namespace App\Livewire\Stock;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Formulier voor het registreren van één stockbeweging (alle vier de types).
 *
 * Dit component verzorgt enkel de UX en de invoervalidatie; de eigenlijke
 * mutatie loopt altijd via StockService, zodat transactie-, lock- en
 * auditlogica op één plek blijven. Eén formulier voor vier types houdt de
 * flow voor de gebruiker uniform: type kiezen, product, plaats, aantal.
 *
 * Architectuurnoot (geldt voor elk full-page component in deze app): deze
 * klasse vervult de controller-rol — de route in web.php wijst rechtstreeks
 * hierheen, er is bewust geen aparte Controller-klasse — én houdt de
 * schermstaat bij (de publieke properties waar de view via wire:model aan
 * bindt). De presentatie blijft apart: de bijbehorende Blade-view is
 * resources/views/livewire/stock/create-movement.blade.php.
 */
#[Layout('layouts.app')]
class CreateMovement extends Component
{
    public string $type = '';

    public ?int $productId = null;

    // Plaats-stap voor incoming/outgoing/correction: één warehouse → locatie
    public ?int $warehouseId = null;

    public ?int $locationId = null;

    // Plaats-stap voor transfer: aparte van/naar-cascade (bron en doel onafhankelijk)
    public ?int $fromWarehouseId = null;

    public ?int $fromLocationId = null;

    public ?int $toWarehouseId = null;

    public ?int $toLocationId = null;

    public int $quantity = 1;

    public string $reference = '';

    public string $notes = '';

    /**
     * Cascade-UX: bij een nieuwe warehouse-keuze wordt de locatie gewist.
     *
     * Zonder deze reset kan een locatie van het vórige magazijn geselecteerd
     * blijven terwijl de dropdown al nieuwe opties toont — een stille
     * inconsistentie die pas bij het opslaan zou opvallen.
     */
    public function updatedWarehouseId(): void
    {
        $this->locationId = null;
    }

    /** Zelfde cascade-reset, maar voor de bronkant ("van") van een transfer. */
    public function updatedFromWarehouseId(): void
    {
        $this->fromLocationId = null;
    }

    /** Zelfde cascade-reset, maar voor de doelkant ("naar") van een transfer. */
    public function updatedToWarehouseId(): void
    {
        $this->toLocationId = null;
    }

    /**
     * Bij een typewissel worden álle plaatsvelden gewist.
     *
     * Transfer gebruikt van/naar-velden, de andere types één warehouse/locatie.
     * Door beide sets te resetten kan geen verborgen waarde van het vorige
     * type meevalideren of -opslaan.
     */
    public function updatedType(): void
    {
        $this->warehouseId = null;
        $this->locationId = null;
        $this->fromWarehouseId = null;
        $this->fromLocationId = null;
        $this->toWarehouseId = null;
        $this->toLocationId = null;
    }

    /** Productopties voor de dropdown, alfabetisch; enkel de kolommen die de view nodig heeft. */
    #[Computed]
    public function products()
    {
        return Product::orderBy('name')->get(['id', 'name', 'sku']);
    }

    /** Magazijnopties, eerste stap van de warehouse → locatie-cascade. */
    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Locatie-opties, afhankelijk van de gekozen warehouse (tweede stap van
     * de cascade). Lege collectie zolang er geen warehouse gekozen is, zodat
     * de view geen aparte null-check nodig heeft.
     */
    #[Computed]
    public function locations()
    {
        return $this->warehouseId
            ? Location::where('warehouse_id', $this->warehouseId)->orderBy('code')->get()
            : collect();
    }

    /** Locatie-opties voor de bronkant van een transfer; zelfde cascadelogica als locations(). */
    #[Computed]
    public function fromLocations()
    {
        return $this->fromWarehouseId
            ? Location::where('warehouse_id', $this->fromWarehouseId)->orderBy('code')->get()
            : collect();
    }

    /** Locatie-opties voor de doelkant van een transfer; zelfde cascadelogica als locations(). */
    #[Computed]
    public function toLocations()
    {
        return $this->toWarehouseId
            ? Location::where('warehouse_id', $this->toWarehouseId)->orderBy('code')->get()
            : collect();
    }

    /** Alle bewegingstypes uit de enum, zodat de view nooit uit de pas loopt met het domein. */
    #[Computed]
    public function types(): array
    {
        return StockMovementType::cases();
    }

    /**
     * Huidige voorraad voor het gekozen product + locatie, getoond als hint.
     *
     * Null betekent "nog niets gekozen" (geen hint tonen); 0 betekent dat de
     * combinatie wél compleet is maar er geen stockrij bestaat — dat
     * onderscheid heeft de view nodig.
     */
    #[Computed]
    public function currentStock(): ?int
    {
        if (! $this->productId || ! $this->locationId) {
            return null;
        }

        return Stock::where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity') ?? 0;
    }

    /**
     * Valideert de invoer en delegeert de mutatie naar StockService.
     *
     * StockService komt binnen via method injection: Livewire resolvet hem
     * uit de container op het moment van de actie, zodat hij niet als state
     * in het component hoeft te leven (componenten worden geserialiseerd
     * tussen requests).
     */
    public function save(StockService $stockService): void
    {
        // Beide rollen mogen dit, maar die beslissing ligt in de policy — niet hier.
        // Verandert de regel ooit, dan hoeft alleen de policy aangepast te worden.
        $this->authorize('create', StockMovement::class);

        $this->validate([
            'type' => 'required|in:incoming,outgoing,transfer,correction',
            'productId' => 'required|exists:products,id',
            // Conditioneel minimum: een correctie zet de absolute voorraad en mag dus
            // 0 zijn (locatie leegmaken); de andere types verplaatsen een aantal en
            // hebben pas betekenis vanaf 1.
            'quantity' => 'required|integer|'.($this->type === 'correction' ? 'min:0' : 'min:1'),
            // Transfer gebruikt van/naar-velden; de overige types één locatie.
            // 'different' blokkeert een transfer naar dezelfde locatie al bij validatie.
            'locationId' => 'required_unless:type,transfer|nullable|exists:locations,id',
            'fromLocationId' => 'required_if:type,transfer|nullable|exists:locations,id',
            'toLocationId' => 'required_if:type,transfer|nullable|exists:locations,id|different:fromLocationId',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($this->productId);
        $user = auth()->user();

        try {
            // match in plaats van if/else: PHP dwingt af dat elk type een tak heeft,
            // een nieuw movement-type zonder tak faalt hard in plaats van stil.
            match ($this->type) {
                'incoming' => $stockService->registerIncoming(
                    $product,
                    Location::findOrFail($this->locationId),
                    $this->quantity,
                    $user,
                    $this->reference ?: null,
                    $this->notes ?: null,
                ),
                'outgoing' => $stockService->registerOutgoing(
                    $product,
                    Location::findOrFail($this->locationId),
                    $this->quantity,
                    $user,
                    $this->reference ?: null,
                    $this->notes ?: null,
                ),
                'transfer' => $stockService->transfer(
                    $product,
                    Location::findOrFail($this->fromLocationId),
                    Location::findOrFail($this->toLocationId),
                    $this->quantity,
                    $user,
                    $this->notes ?: null,
                ),
                'correction' => $stockService->adjust(
                    $product,
                    Location::findOrFail($this->locationId),
                    $this->quantity,
                    $user,
                    $this->notes ?: null,
                ),
            };

            Flux::toast(__('Stock movement registered.'), variant: 'success');
            $this->redirect(route('stock.movements'), navigate: true);
        } catch (InsufficientStockException $e) {
            // Domeinfout vertaald naar een veld-error op 'quantity': de gebruiker ziet
            // de melding bij het veld dat hij moet aanpassen, in plaats van een 500
            // of een losse flash-melding. De service heeft de transactie al teruggerold.
            $this->addError('quantity', __('Insufficient stock: ').$e->getMessage());
        }
    }

    /** Tekent het formulier; de pagina-layout komt uit het #[Layout]-attribuut op de klasse. */
    public function render()
    {
        return view('livewire.stock.create-movement');
    }
}
