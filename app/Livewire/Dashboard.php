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

#[Layout('layouts.app')]
class Dashboard extends Component
{
    #[Computed]
    public function stats(): array
    {
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

    #[Computed]
    public function lowStockProducts()
    {
        return Product::with('category')
            ->where('min_stock', '>', 0)
            ->get()
            ->filter(fn ($p) => $p->isBelowMinStock())
            ->take(5);
    }

    #[Computed]
    public function recentActivity()
    {
        return Activity::with('causer')
            ->latest()
            ->take(8)
            ->get();
    }

    /**
     * Data for the "Movements – Last 7 Days" grouped bar chart.
     */
    #[Computed]
    public function chartMovements(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

        $raw = DB::table('stock_movements')
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
     * Data for the "Stock by Warehouse" doughnut chart.
     */
    #[Computed]
    public function chartStockByWarehouse(): array
    {
        $rows = DB::table('warehouses')
            ->join('locations', 'locations.warehouse_id', '=', 'warehouses.id')
            ->join('stock', 'stock.location_id', '=', 'locations.id')
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
            'colors' => $rows->keys()->map(fn ($i) => $palette[$i % count($palette)])->toArray(),
        ];
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
