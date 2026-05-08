<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
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

    public function render()
    {
        return view('livewire.dashboard');
    }
}
