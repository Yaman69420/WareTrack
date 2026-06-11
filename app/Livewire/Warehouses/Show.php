<?php

namespace App\Livewire\Warehouses;

use App\Models\Location;
use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Detailpagina van één magazijn: locaties als grid of als heatmap, met
 * kerncijfers en locatiebeheer (aanmaken/bewerken/verwijderen) ter plaatse.
 *
 * De voorraad- en productcijfers per locatie komen via SQL-subqueries mee in
 * de locatie-query zelf, zodat ook een groot magazijn met één query laadt.
 */
#[Layout('layouts.app')]
class Show extends Component
{
    public Warehouse $warehouse;

    // Weergavemodus: 'grid' (lijst) of 'heatmap' (kleur per voorraadniveau).
    public string $viewMode = 'grid';

    // Add location modal
    public bool $showModal = false;

    public ?int $editingLocationId = null;

    public string $code = '';

    public string $locationName = '';

    public function mount(Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    #[Computed]
    public function locations()
    {
        // Twee subqueries per locatie-rij i.p.v. queries in een lus: COALESCE
        // geeft 0 voor lege locaties; de product-count telt enkel quantity > 0
        // zodat ooit-leeggemaakte stockrijen het cijfer niet vertekenen.
        $totalStockSub = DB::raw('(SELECT COALESCE(SUM(s.quantity), 0) FROM stock s WHERE s.location_id = locations.id) as total_stock');
        $productCountSub = DB::raw('(SELECT COUNT(DISTINCT s.product_id) FROM stock s WHERE s.location_id = locations.id AND s.quantity > 0) as product_count');

        return $this->warehouse->locations()
            ->addSelect(['locations.*', $totalStockSub, $productCountSub])
            ->orderBy('code')
            ->get();
    }

    /**
     * Hoogste voorraad van alle locaties: de noemer om de heatmap-kleuren te
     * normaliseren. Minimaal 1, anders deelt de view door nul bij een leeg
     * magazijn.
     */
    #[Computed]
    public function maxLocationStock(): int
    {
        return (int) $this->locations->max('total_stock') ?: 1;
    }

    /**
     * Kerncijfers voor de header. Locatie-aantal en totale voorraad komen uit
     * de al geladen locations-collectie; enkel het aantal unieke producten
     * vergt een eigen query, omdat distinct over locaties heen moet tellen.
     */
    #[Computed]
    public function stats(): array
    {
        $locations = $this->locations;

        return [
            'location_count' => $locations->count(),
            'total_stock' => $locations->sum('total_stock'),
            // Distinct over het hele magazijn: hetzelfde product op twee
            // locaties telt één keer. Soft-deleted locaties expliciet
            // uitsluiten — de Query Builder kent de Eloquent-scope niet.
            'product_count' => DB::table('stock')
                ->join('locations', 'stock.location_id', '=', 'locations.id')
                ->where('locations.warehouse_id', $this->warehouse->id)
                ->whereNull('locations.deleted_at')
                ->distinct('stock.product_id')
                ->count('stock.product_id'),
        ];
    }

    public function openCreateLocation(): void
    {
        $this->reset(['code', 'locationName', 'editingLocationId']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEditLocation(Location $location): void
    {
        $this->editingLocationId = $location->id;
        $this->code = $location->code;
        $this->locationName = $location->name ?? '';
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Eén save-methode voor create én update van een locatie binnen dit
     * magazijn; $editingLocationId bepaalt de modus.
     */
    public function saveLocation(): void
    {
        $this->validate([
            'code' => 'required|string|max:20',
            'locationName' => 'nullable|string|max:100',
        ]);

        if ($this->editingLocationId) {
            $loc = Location::findOrFail($this->editingLocationId);
            $loc->update([
                'code' => strtoupper($this->code),
                'name' => $this->locationName ?: null,
            ]);
            activity()->causedBy(auth()->user())->performedOn($loc)->log('updated');
            Flux::toast(__('Location updated.'), variant: 'success');
        } else {
            // Aanmaken via de relatie: warehouse_id wordt automatisch gezet en
            // kan dus nooit naar een ander magazijn wijzen.
            $loc = $this->warehouse->locations()->create([
                'code' => strtoupper($this->code),
                'name' => $this->locationName ?: null,
            ]);
            activity()->causedBy(auth()->user())->performedOn($loc)->log('created');
            Flux::toast(__('Location created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['code', 'locationName', 'editingLocationId']);
        // Alle afgeleide caches legen: de drie computed properties bouwen op
        // dezelfde locatiedata en mogen niet onderling uit sync raken.
        unset($this->locations, $this->stats, $this->maxLocationStock);
    }

    /**
     * Soft delete van een locatie: historische bewegingen blijven ernaar
     * verwijzen, maar de locatie verdwijnt uit grid, heatmap en statistieken.
     */
    public function deleteLocation(Location $location): void
    {
        $location->delete();
        activity()->causedBy(auth()->user())->performedOn($location)->log('deleted');
        Flux::toast(__('Location deleted.'), variant: 'success');
        unset($this->locations, $this->stats, $this->maxLocationStock);
    }

    public function render()
    {
        return view('livewire.warehouses.show');
    }
}
