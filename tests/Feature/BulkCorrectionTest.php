<?php

use App\Enums\StockMovementType;
use App\Livewire\Stock\BulkCorrection;
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

    $this->warehouse = Warehouse::factory()->create();
    $this->warehouseB = Warehouse::factory()->create();
    $this->location = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'A1']);
    $this->locationB = Location::factory()->create(['warehouse_id' => $this->warehouseB->id, 'code' => 'B1']);
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create(['category_id' => $this->category->id]);
});

test('admin can view bulk correction page', function () {
    Livewire::actingAs($this->admin)
        ->test(BulkCorrection::class)
        ->assertStatus(200);
});

test('guest is redirected from bulk correction page', function () {
    $this->get(route('stock.bulk-correction'))->assertRedirect(route('login'));
});

test('selecting a warehouse pre-fills quantities with current stock', function () {
    $stock = Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 15]);

    Livewire::actingAs($this->admin)
        ->test(BulkCorrection::class)
        ->set('warehouseId', $this->warehouse->id)
        ->assertSet('quantities', [$stock->id => '15']);
});

test('switching warehouse re-fills quantities without stale entries', function () {
    $stockA = Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 15]);

    $productB = Product::factory()->create(['category_id' => $this->category->id]);
    $stockB = Stock::create(['product_id' => $productB->id, 'location_id' => $this->locationB->id, 'quantity' => 7]);

    Livewire::actingAs($this->admin)
        ->test(BulkCorrection::class)
        ->set('warehouseId', $this->warehouse->id)
        ->assertSet('quantities', [$stockA->id => '15'])
        ->set('warehouseId', $this->warehouseB->id)
        ->assertSet('quantities', [$stockB->id => '7']);
});

test('selecting a warehouse without stock leaves quantities empty', function () {
    Livewire::actingAs($this->admin)
        ->test(BulkCorrection::class)
        ->set('warehouseId', $this->warehouse->id)
        ->assertSet('quantities', []);
});

test('mount pre-fills quantities when warehouse is already set', function () {
    $stock = Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 15]);

    Livewire::actingAs($this->admin)
        ->test(BulkCorrection::class, ['warehouseId' => $this->warehouse->id])
        ->assertSet('quantities', [$stock->id => '15']);
});

test('save applies corrections for changed lines only', function () {
    $stock = Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 15]);

    $productB = Product::factory()->create(['category_id' => $this->category->id]);
    $locationA2 = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'A2']);
    $stockUnchanged = Stock::create(['product_id' => $productB->id, 'location_id' => $locationA2->id, 'quantity' => 5]);

    Livewire::actingAs($this->admin)
        ->test(BulkCorrection::class)
        ->set('warehouseId', $this->warehouse->id)
        ->set("quantities.{$stock->id}", '20')
        ->set('notes', 'Physical count')
        ->call('save')
        ->assertHasNoErrors();

    expect($stock->fresh()->quantity)->toBe(20);
    expect($stockUnchanged->fresh()->quantity)->toBe(5);

    expect(StockMovement::where('type', StockMovementType::Correction)->count())->toBe(1);

    $movement = StockMovement::where('type', StockMovementType::Correction)->first();
    expect($movement->product_id)->toBe($this->product->id);
    expect($movement->location_id)->toBe($this->location->id);
    expect($movement->quantity)->toBe(5);
    expect($movement->notes)->toBe('Physical count');
});

test('warehouse worker can save bulk corrections', function () {
    // Guards the StockMovementPolicy::create authorize — corrections are open to both roles
    $worker = User::factory()->create();
    $stock = Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 15]);

    Livewire::actingAs($worker)
        ->test(BulkCorrection::class)
        ->set('warehouseId', $this->warehouse->id)
        ->set("quantities.{$stock->id}", '12')
        ->call('save')
        ->assertHasNoErrors();

    expect($stock->fresh()->quantity)->toBe(12);
    expect(StockMovement::where('type', StockMovementType::Correction)->count())->toBe(1);
});

test('save without changes creates no movements', function () {
    $stock = Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 15]);

    Livewire::actingAs($this->admin)
        ->test(BulkCorrection::class)
        ->set('warehouseId', $this->warehouse->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(StockMovement::count())->toBe(0);
    expect($stock->fresh()->quantity)->toBe(15);
});

test('save requires a selected warehouse', function () {
    Livewire::actingAs($this->admin)
        ->test(BulkCorrection::class)
        ->call('save')
        ->assertHasErrors(['warehouseId']);
});

test('save rejects negative quantities', function () {
    $stock = Stock::create(['product_id' => $this->product->id, 'location_id' => $this->location->id, 'quantity' => 15]);

    Livewire::actingAs($this->admin)
        ->test(BulkCorrection::class)
        ->set('warehouseId', $this->warehouse->id)
        ->set("quantities.{$stock->id}", '-3')
        ->call('save')
        ->assertHasErrors(["quantities.{$stock->id}"]);
});
