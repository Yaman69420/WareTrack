<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // Accessible by admin and warehouse_worker
    Route::get('/suppliers', App\Livewire\Suppliers\Index::class)->name('suppliers.index');

    Route::get('/deliveries', App\Livewire\Deliveries\Index::class)->name('deliveries.index');
    Route::get('/deliveries/create', App\Livewire\Deliveries\Create::class)->name('deliveries.create');
    Route::get('/deliveries/{delivery}', App\Livewire\Deliveries\Show::class)->name('deliveries.show');

    Route::get('/stock', App\Livewire\Stock\Index::class)->name('stock.index');
    Route::get('/stock/movements', App\Livewire\Stock\Movements::class)->name('stock.movements');
    Route::get('/stock/movements/create', App\Livewire\Stock\CreateMovement::class)->name('stock.movements.create');

    // Admin only
    Route::middleware(['admin'])->group(function () {
        Route::get('/categories', App\Livewire\Categories\Index::class)->name('categories.index');
        Route::get('/products', App\Livewire\Products\Index::class)->name('products.index');
        Route::get('/warehouses', App\Livewire\Warehouses\Index::class)->name('warehouses.index');
        Route::get('/locations', App\Livewire\Locations\Index::class)->name('locations.index');
    });
});

require __DIR__.'/settings.php';
