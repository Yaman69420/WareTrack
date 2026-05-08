<?php

use App\Enums\StockMovementType;
use App\Livewire\Stock\CreateMovement;
use App\Livewire\Stock\Index;
use App\Livewire\Stock\Movements;
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
    $this->warehouseB = Warehouse::factory()->create();
    $this->location = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'A1']);
    $this->locationB = Location::factory()->create(['warehouse_id' => $this->warehouseB->id, 'code' => 'B1']);
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 10]);
});

// =========================================================
// Stock/Index
// =========================================================

test('admin can view stock index', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertStatus(200);
});

test('worker can view stock index', function () {
    Livewire::actingAs($this->worker)
        ->test(Index::class)
        ->assertStatus(200);
});

test('guest is redirected from stock index', function () {
    $this->get(route('stock.index'))->assertRedirect(route('login'));
});

test('stock index shows products with stock', function () {
    Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 15]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertSee($this->product->name);
});

test('stock index search filters by product name', function () {
    $other = Product::factory()->create(['category_id' => $this->category->id, 'name' => 'Unique Other Product']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'Unique Other')
        ->assertSee($other->name)
        ->assertDontSee($this->product->name);
});

test('stock index filters by warehouse', function () {
    Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 5]);

    $productB = Product::factory()->create(['category_id' => $this->category->id]);
    Stock::create(['product_id' => $productB->id, 'location_id' => $this->locationB->id, 'quantity' => 3]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('filterWarehouse', $this->warehouse->id)
        ->assertSee($this->product->name)
        ->assertDontSee($productB->name);
});

// =========================================================
// Stock/Movements
// =========================================================

test('admin can view stock movements page', function () {
    Livewire::actingAs($this->admin)
        ->test(Movements::class)
        ->assertStatus(200);
});

test('worker can view stock movements page', function () {
    Livewire::actingAs($this->worker)
        ->test(Movements::class)
        ->assertStatus(200);
});

test('movements page shows existing movements', function () {
    StockMovement::factory()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'user_id' => $this->admin->id,
        'type' => StockMovementType::Incoming,
        'quantity' => 10,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Movements::class)
        ->assertSee($this->product->name);
});

test('movements page filters by type', function () {
    StockMovement::factory()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'user_id' => $this->admin->id,
        'type' => StockMovementType::Incoming,
        'quantity' => 10,
    ]);

    StockMovement::factory()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'user_id' => $this->admin->id,
        'type' => StockMovementType::Outgoing,
        'quantity' => 3,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Movements::class)
        ->set('filterType', 'incoming')
        ->assertSee($this->product->name);
});

// =========================================================
// Stock/CreateMovement
// =========================================================

test('admin can view create movement page', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateMovement::class)
        ->assertStatus(200);
});

test('worker can view create movement page', function () {
    Livewire::actingAs($this->worker)
        ->test(CreateMovement::class)
        ->assertStatus(200);
});

test('create movement requires type and product', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateMovement::class)
        ->call('save')
        ->assertHasErrors(['type', 'productId']);
});

test('incoming movement requires a location', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateMovement::class)
        ->set('type', 'incoming')
        ->set('productId', $this->product->id)
        ->set('quantity', 5)
        ->call('save')
        ->assertHasErrors(['locationId']);
});

test('transfer movement requires from and to location', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateMovement::class)
        ->set('type', 'transfer')
        ->set('productId', $this->product->id)
        ->set('quantity', 5)
        ->call('save')
        ->assertHasErrors(['fromLocationId', 'toLocationId']);
});

test('incoming movement saves and redirects', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateMovement::class)
        ->set('type', 'incoming')
        ->set('productId', $this->product->id)
        ->set('locationId', $this->location->id)
        ->set('quantity', 10)
        ->call('save')
        ->assertRedirect(route('stock.movements'));

    expect(StockMovement::where('type', StockMovementType::Incoming)->count())->toBe(1);
    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->location->id)->value('quantity'))->toBe(10);
});

test('outgoing movement fails with insufficient stock error', function () {
    Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 2]);

    Livewire::actingAs($this->admin)
        ->test(CreateMovement::class)
        ->set('type', 'outgoing')
        ->set('productId', $this->product->id)
        ->set('locationId', $this->location->id)
        ->set('quantity', 10)
        ->call('save')
        ->assertHasErrors(['quantity']);
});

test('correction movement sets exact quantity', function () {
    Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 5]);

    Livewire::actingAs($this->admin)
        ->test(CreateMovement::class)
        ->set('type', 'correction')
        ->set('productId', $this->product->id)
        ->set('locationId', $this->location->id)
        ->set('quantity', 20)
        ->call('save')
        ->assertRedirect(route('stock.movements'));

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->location->id)->value('quantity'))->toBe(20);
});

test('transfer movement requires different from and to location', function () {
    Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 10]);

    Livewire::actingAs($this->admin)
        ->test(CreateMovement::class)
        ->set('type', 'transfer')
        ->set('productId', $this->product->id)
        ->set('fromLocationId', $this->location->id)
        ->set('toLocationId', $this->location->id)
        ->set('quantity', 5)
        ->call('save')
        ->assertHasErrors(['toLocationId']);
});
