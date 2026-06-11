<?php

namespace App\Livewire\Stock;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Bulkcorrectie: alle stocklijnen van één magazijn in één tabel hertellen.
 *
 * Typisch scenario is een inventaris/telling: de magazijnier overschrijft per
 * locatie het getelde aantal en het component bepaalt zelf de delta met de
 * huidige voorraad. Alleen gewijzigde lijnen worden opgeslagen, elk als een
 * gewone correctie via StockService — bulk is hier puur een UX-laag, geen
 * apart mutatiepad.
 */
#[Layout('layouts.app')]
class BulkCorrection extends Component
{
    public ?int $warehouseId = null;

    /**
     * Nieuwe aantallen per stocklijn, gekeyed op stock-id.
     *
     * Bewust strings: een leeggemaakt invoerveld komt binnen als '' en moet
     * te onderscheiden blijven van een bewuste 0. De cast naar int gebeurt
     * pas bij de delta-berekening en het opslaan.
     *
     * @var array<int, string>
     */
    public array $quantities = [];

    public string $notes = '';

    /**
     * Bij een nieuwe warehouse-keuze: tabel verversen en invoer vooraf invullen.
     *
     * De unset() gooit de gecachte computed properties weg — anders zou
     * prefillQuantities() nog de stocklijnen van het vórige magazijn zien.
     * Vooraf invullen met de huidige aantallen maakt de delta-weergave
     * mogelijk: alles start op "geen wijziging".
     */
    public function updatedWarehouseId(): void
    {
        unset($this->stockLines, $this->changedLines);
        $this->prefillQuantities();
    }

    /** Magazijnopties voor de keuzelijst, alfabetisch. */
    #[Computed]
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get(['id', 'name']);
    }

    /**
     * Alle stocklijnen van het gekozen magazijn, gesorteerd op locatiecode en
     * dan productnaam — de volgorde waarin een teller fysiek door het magazijn
     * loopt. Eager loading van product/categorie/locatie vermijdt N+1 in de tabel.
     */
    #[Computed]
    public function stockLines()
    {
        if (! $this->warehouseId) {
            return collect();
        }

        return Stock::with(['product.category', 'location'])
            ->whereHas('location', fn ($q) => $q->where('warehouse_id', $this->warehouseId))
            ->get()
            ->sortBy([
                ['location.code', 'asc'],
                ['product.name', 'asc'],
            ]);
    }

    /**
     * Vult $quantities met de huidige aantallen, gekeyed op stock-id.
     *
     * Dit is het startpunt "geen wijziging" waartegen changedLines() de delta's
     * afmeet; de string-cast houdt het formaat gelijk aan wat de inputs sturen.
     */
    public function prefillQuantities(): void
    {
        $this->quantities = $this->stockLines
            ->mapWithKeys(fn ($line) => [$line->id => (string) $line->quantity])
            ->toArray();
    }

    /** Eenmalige initialisatie bij het eerste laden van het component. */
    public function mount(): void
    {
        // Eénmalig vooraf invullen als warehouseId al gezet is (bv. via de URL):
        // updatedWarehouseId() vuurt niet bij mount, dus zonder dit blijft de tabel leeg.
        if ($this->warehouseId) {
            $this->prefillQuantities();
        }
    }

    /**
     * Enkel de lijnen waarvan het aantal effectief gewijzigd is.
     *
     * Dit voedt de live delta in de view (teller + markering per rij) én
     * bepaalt bij save() wat er opgeslagen wordt. Lege velden ('') tellen
     * niet als wijziging: niet ingevuld is iets anders dan "zet op 0".
     */
    #[Computed]
    public function changedLines()
    {
        return $this->stockLines->filter(function ($line) {
            $newQty = $this->quantities[$line->id] ?? null;

            return $newQty !== null && $newQty !== '' && (int) $newQty !== $line->quantity;
        });
    }

    /**
     * Slaat elke gewijzigde lijn op als een afzonderlijke correctie.
     *
     * Bewust géén overkoepelende transactie rond de lus: elke adjust() is op
     * zich atomair (transactie + lock in StockService) en een audit-record
     * per locatie is precies wat een telling moet opleveren.
     */
    public function save(StockService $stockService): void
    {
        // Een correctie is gewoon een stockbeweging — dezelfde policy-check als
        // CreateMovement, zodat bulk geen achterpoort wordt.
        $this->authorize('create', StockMovement::class);

        $this->validate([
            'warehouseId' => 'required|exists:warehouses,id',
            'notes' => 'nullable|string|max:500',
            'quantities' => 'array',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        $changed = $this->changedLines;

        // Guard clause: niets gewijzigd betekent niets opslaan — voorkomt lege
        // audit-records en maakt de gebruiker duidelijk waarom er niets gebeurde.
        if ($changed->isEmpty()) {
            Flux::toast(__('No changes to save.'), variant: 'warning');

            return;
        }

        $user = auth()->user();

        foreach ($changed as $line) {
            $newQty = (int) $this->quantities[$line->id];

            $stockService->adjust(
                $line->product,
                $line->location,
                $newQty,
                $user,
                $this->notes ?: null,
            );
        }

        Flux::toast(
            trans_choice(
                '{1} 1 location corrected.|[2,*] :count locations corrected.',
                $changed->count(),
                ['count' => $changed->count()]
            ),
            variant: 'success'
        );

        $this->notes = '';
        // Computed-cache weggooien en opnieuw vooraf invullen: de tabel toont meteen
        // de nieuwe aantallen en alle delta's staan terug op nul.
        unset($this->stockLines, $this->changedLines);
        $this->prefillQuantities();
    }

    /** Tekent de teltabel; de pagina-layout komt uit het #[Layout]-attribuut op de klasse. */
    public function render()
    {
        return view('livewire.stock.bulk-correction');
    }
}
