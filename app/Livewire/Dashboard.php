<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

/**
 * Startpagina met de operationele toestand van het magazijn in één oogopslag.
 *
 * Vier soorten widgets: tellers (stats), de actielijst lage voorraad, de
 * recente activiteit uit de audit-log en twee grafieken (bewegingen per dag,
 * stock per magazijn). Alles read-only; de data komt via computed properties
 * zodat elke widget zijn eigen query heeft en apart te cachen/testen is.
 */
#[Layout('layouts.app')]
class Dashboard extends Component
{
    /**
     * Tellers bovenaan het dashboard. Bewuste mix: stamdata (producten,
     * magazijnen, ...) toont de omvang van het systeem, totale stock en
     * bewegingen-vandaag tonen de activiteit van dit moment.
     */
    #[Computed]
    public function stats(): array
    {
        // Query builder i.p.v. het Stock-model: voor één SUM is een model-instantie
        // overbodig, en de tabel heet 'stock' (niet het conventionele 'stocks').
        $totalStock = (int) DB::table('stock')->sum('quantity');
        $movementsToday = StockMovement::whereDate('created_at', today())->count();

        return [
            'products' => Product::count(),
            'categories' => Category::count(),
            'warehouses' => Warehouse::count(),
            'locations' => Location::count(),
            'total_stock' => $totalStock,
            'movements_today' => $movementsToday,
        ];
    }

    /**
     * Top 5 producten onder hun minimumvoorraad — de actielijst van het dashboard.
     *
     * De drempel vergelijkt met de tótale stock over alle locaties; dat totaal
     * staat niet als kolom in de database, dus het filteren gebeurt in PHP via
     * dezelfde isBelowMinStock() als elders (één definitie van "te laag").
     * De where op min_stock > 0 beperkt de set vooraf: producten zonder
     * ingestelde drempel kunnen nooit "te laag" zijn.
     */
    #[Computed]
    public function lowStockProducts()
    {
        return Product::with(['category', 'stock'])
            ->where('min_stock', '>', 0)
            ->get()
            ->filter(fn ($p) => $p->isBelowMinStock())
            // Cap op 5: het dashboard signaleert, de volledige lijst zit in de rapporten.
            ->take(5);
    }

    /**
     * Laatste 8 regels uit de Spatie activity-log, met de veroorzaker eager
     * geladen voor de naamweergave. Hergebruikt de audit-log als feed — er is
     * geen aparte "events"-tabel nodig.
     */
    #[Computed]
    public function recentActivity()
    {
        return Activity::with('causer')
            ->latest()
            ->take(8)
            ->get();
    }

    /**
     * Data voor de gegroepeerde staafgrafiek "bewegingen – laatste 7 dagen".
     *
     * De aggregatie gebeurt in SQL (één GROUP BY-query), het uitlijnen op
     * dagen en types in PHP. Het resultaat is een kant-en-klare Chart.js-
     * structuur zodat de view/JS geen datalogica meer bevat.
     */
    #[Computed]
    public function chartMovements(): array
    {
        // De 7 dagen worden expliciet opgebouwd: dagen zonder bewegingen ontbreken
        // in het queryresultaat, maar moeten als 0 op de x-as blijven staan.
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

        $raw = DB::table('stock_movements')
            // ABS(): correcties kunnen negatief opgeslagen zijn; de grafiek toont
            // volume per type, geen netto saldo.
            ->selectRaw('DATE(created_at) as day, type, SUM(ABS(quantity)) as total')
            ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->groupBy('day', 'type')
            ->get()
            ->groupBy('day');

        $typeConfig = [
            'incoming' => ['label' => 'Incoming',   'color' => 'rgba(34,197,94,.85)'],
            'outgoing' => ['label' => 'Outgoing',   'color' => 'rgba(239,68,68,.85)'],
            'transfer' => ['label' => 'Transfer',   'color' => 'rgba(59,130,246,.85)'],
            'correction' => ['label' => 'Correction', 'color' => 'rgba(234,179,8,.85)'],
        ];

        $labels = $days->map(fn ($d) => Carbon::parse($d)->format('D d/m'))->values()->toArray();

        $datasets = [];
        foreach ($typeConfig as $type => $cfg) {
            $data = $days->map(
                fn ($day) => (int) ($raw->get($day)?->firstWhere('type', $type)?->total ?? 0)
            )->values()->toArray();

            $datasets[] = [
                'label' => $cfg['label'],
                'data' => $data,
                'backgroundColor' => $cfg['color'],
                'borderRadius' => 5,
                'borderSkipped' => false,
            ];
        }

        return compact('labels', 'datasets');
    }

    /**
     * Data voor de doughnut-grafiek "stock per magazijn".
     *
     * Eén join-query in plaats van per magazijn de relaties optellen:
     * de databank aggregeert, PHP vormt enkel nog om naar Chart.js-formaat.
     */
    #[Computed]
    public function chartStockByWarehouse(): array
    {
        $rows = DB::table('warehouses')
            ->join('locations', 'locations.warehouse_id', '=', 'warehouses.id')
            ->join('stock', 'stock.location_id', '=', 'locations.id')
            // De query builder kent de SoftDeletes-scope van Eloquent niet; zonder deze
            // whereNulls zou stock op verwijderde magazijnen/locaties meegeteld worden.
            ->whereNull('warehouses.deleted_at')
            ->whereNull('locations.deleted_at')
            ->selectRaw('warehouses.name, SUM(stock.quantity) as total')
            ->groupBy('warehouses.id', 'warehouses.name')
            ->orderByDesc('total')
            ->get();

        $palette = [
            'rgba(59,130,246,.85)',
            'rgba(139,92,246,.85)',
            'rgba(20,184,166,.85)',
            'rgba(245,158,11,.85)',
            'rgba(239,68,68,.85)',
            'rgba(236,72,153,.85)',
        ];

        return [
            'labels' => $rows->pluck('name')->toArray(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
            // Modulo over het palet: ook met meer magazijnen dan kleuren blijft elke
            // schijf een kleur krijgen (kleuren herhalen dan).
            'colors' => $rows->keys()->map(fn ($i) => $palette[$i % count($palette)])->toArray(),
        ];
    }

    /** Rendert de dashboard-view; alle widgetdata komt via de computed properties hierboven. */
    public function render()
    {
        return view('livewire.dashboard');
    }
}
