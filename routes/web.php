<?php

use App\Livewire\Dashboard;
use App\Livewire\Deliveries\Create;
use App\Livewire\Deliveries\Show;
use App\Livewire\Stock\BulkCorrection;
use App\Livewire\Stock\CreateMovement;
use App\Livewire\Stock\Movements;
use App\Livewire\Suppliers\Index;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routestructuur in drie ringen
|--------------------------------------------------------------------------
| 1. Publiek: enkel de root, die doorstuurt naar dashboard of login.
| 2. auth + verified: de gedeelde werkruimte voor beide rollen.
| 3. admin (genest): masterdata en gebruikersbeheer, enkel voor admins.
|
| Er zijn geen controllers: elke route wijst rechtstreeks naar een full-page
| Livewire-component, die zelf valideert/autoriseert en naar services delegeert.
*/

// Geen landingspagina — dit is een interne app: ingelogd → dashboard, anders → login.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // Gedeeld: workers mogen leveranciers bekijken (muterende acties zijn in de
    // component zelf afgeschermd via SupplierPolicy).
    Route::get('/suppliers', Index::class)->name('suppliers.index');

    Route::get('/deliveries', App\Livewire\Deliveries\Index::class)->name('deliveries.index');
    // Admin-only, maar bewust hiér geregistreerd met inline middleware: Laravel matcht
    // routes in registratievolgorde, dus /deliveries/create moet vóór de
    // {delivery}-wildcard staan — anders vangt die het woord "create" als ID (404 i.p.v. 403).
    Route::get('/deliveries/create', Create::class)->middleware('admin')->name('deliveries.create');
    // Route-modelbinding: {delivery} wordt automatisch een Delivery-instantie (of 404).
    Route::get('/deliveries/{delivery}', Show::class)->name('deliveries.show');

    Route::get('/reports', App\Livewire\Reports\Index::class)->name('reports.index');
    Route::get('/activity', App\Livewire\Activity\Index::class)->name('activity.index');

    // Stockbewegingen registreren is het dagelijkse werk van een warehouse worker —
    // daarom gedeeld, met de policy-check (StockMovementPolicy) in de componenten.
    Route::get('/stock', App\Livewire\Stock\Index::class)->name('stock.index');
    Route::get('/stock/movements', Movements::class)->name('stock.movements');
    Route::get('/stock/movements/create', CreateMovement::class)->name('stock.movements.create');
    Route::get('/stock/bulk-correction', BulkCorrection::class)->name('stock.bulk-correction');

    // Masterdata en gebruikersbeheer: EnsureUserIsAdmin geeft workers een 403.
    Route::middleware(['admin'])->group(function () {
        Route::get('/categories', App\Livewire\Categories\Index::class)->name('categories.index');
        Route::get('/products', App\Livewire\Products\Index::class)->name('products.index');
        Route::get('/products/{product}', App\Livewire\Products\Show::class)->name('products.show');
        Route::get('/warehouses', App\Livewire\Warehouses\Index::class)->name('warehouses.index');
        Route::get('/warehouses/{warehouse}', App\Livewire\Warehouses\Show::class)->name('warehouses.show');
        Route::get('/locations', App\Livewire\Locations\Index::class)->name('locations.index');

        Route::get('/users', App\Livewire\Users\Index::class)->name('users.index');
    });
});

require __DIR__.'/settings.php';
