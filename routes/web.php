<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // Accessible by admin and warehouse_worker
    Route::get('/suppliers', App\Livewire\Suppliers\Index::class)->name('suppliers.index');

    // Admin only
    Route::middleware(['admin'])->group(function () {
        Route::get('/categories', App\Livewire\Categories\Index::class)->name('categories.index');
        Route::get('/products', App\Livewire\Products\Index::class)->name('products.index');
        Route::get('/warehouses', App\Livewire\Warehouses\Index::class)->name('warehouses.index');
        Route::get('/locations', App\Livewire\Locations\Index::class)->name('locations.index');
    });
});

require __DIR__.'/settings.php';
