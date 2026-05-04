<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Warehouse;
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
        return [
            'products' => Product::count(),
            'categories' => Category::count(),
            'warehouses' => Warehouse::count(),
            'locations' => Location::count(),
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
