<?php

use App\Enums\StockMovementType;
use App\Livewire\Reports\Index;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->worker = User::factory()->create();

    $this->warehouse = Warehouse::factory()->create();
    $this->location = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'A1']);
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 10]);
});

test('admin can view reports page', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertStatus(200);
});

test('worker can view reports page', function () {
    Livewire::actingAs($this->worker)
        ->test(Index::class)
        ->assertStatus(200);
});

test('guest is redirected from reports page', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
});

test('reports page defaults to low-stock tab', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertSet('tab', 'low-stock');
});

test('reports page shows low stock products', function () {
    Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 2]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertSee($this->product->name);
});

test('reports page shows no low stock message when all stocked', function () {
    Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 50]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertSee(__('All products are sufficiently stocked.'));
});

test('setTab switches active tab', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('setTab', 'stock-per-location')
        ->assertSet('tab', 'stock-per-location');
});

test('reports stock-per-location tab shows warehouses', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('setTab', 'stock-per-location')
        ->assertSee($this->warehouse->name);
});

test('reports movements tab shows movements in period', function () {
    StockMovement::factory()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'user_id' => $this->admin->id,
        'type' => StockMovementType::Incoming,
        'quantity' => 10,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('setTab', 'movements')
        ->assertSee($this->product->name);
});

test('reports movements tab filters by type', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('setTab', 'movements')
        ->set('filterType', 'incoming')
        ->assertSet('filterType', 'incoming');
});

test('reports page initialises date filters to current month', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertSet('filterFrom', now()->startOfMonth()->format('Y-m-d'))
        ->assertSet('filterTo', now()->format('Y-m-d'));
});
