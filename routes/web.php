<?php

use App\Livewire\Products\Create;
use App\Livewire\Products\Edit;
use App\Livewire\Stock\Movements;
use App\Livewire\Suppliers\Index;
use App\Livewire\Suppliers\Show;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Accessible by admin and warehouse_worker
    Route::get('/suppliers', Index::class)->name('suppliers.index');
    Route::get('/suppliers/{supplier}', Show::class)->name('suppliers.show');

    Route::get('/deliveries', App\Livewire\Deliveries\Index::class)->name('deliveries.index');
    Route::get('/deliveries/{delivery}', App\Livewire\Deliveries\Show::class)->name('deliveries.show');

    Route::get('/stock', App\Livewire\Stock\Index::class)->name('stock.index');
    Route::get('/stock/movements', Movements::class)->name('stock.movements');

    Route::get('/reports', App\Livewire\Reports\Index::class)->name('reports.index');

    // Admin only
    Route::middleware(['admin'])->group(function () {
        Route::get('/products', App\Livewire\Products\Index::class)->name('products.index');
        Route::get('/products/create', Create::class)->name('products.create');
        Route::get('/products/{product}', App\Livewire\Products\Show::class)->name('products.show');
        Route::get('/products/{product}/edit', Edit::class)->name('products.edit');

        Route::get('/categories', App\Livewire\Categories\Index::class)->name('categories.index');

        Route::get('/warehouses', App\Livewire\Warehouses\Index::class)->name('warehouses.index');
        Route::get('/warehouses/{warehouse}', App\Livewire\Warehouses\Show::class)->name('warehouses.show');

        Route::get('/locations', App\Livewire\Locations\Index::class)->name('locations.index');

        Route::get('/suppliers/create', App\Livewire\Suppliers\Create::class)->name('suppliers.create');
        Route::get('/suppliers/{supplier}/edit', App\Livewire\Suppliers\Edit::class)->name('suppliers.edit');

        Route::get('/deliveries/create', App\Livewire\Deliveries\Create::class)->name('deliveries.create');

        Route::get('/users', App\Livewire\Users\Index::class)->name('users.index');
    });
});

require __DIR__.'/settings.php';
