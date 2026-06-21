<?php

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\WarehouseService;

beforeEach(function () {
    $this->service = app(WarehouseService::class);
});

// --- createWarehouse ---

test('createWarehouse creates a warehouse with no locations', function () {
    $warehouse = $this->service->createWarehouse('Test Warehouse', 'Gent', 'A test warehouse');

    expect(Warehouse::count())->toBe(1);
    expect($warehouse->name)->toBe('Test Warehouse');
    expect($warehouse->location)->toBe('Gent');
    expect($warehouse->description)->toBe('A test warehouse');
    expect($warehouse->locations()->count())->toBe(0);
});

test('createWarehouse creates initial locations when provided', function () {
    $warehouse = $this->service->createWarehouse('Test Warehouse', 'Brussel', null, [
        ['code' => 'A1', 'name' => 'Aisle A1'],
        ['code' => 'A2', 'name' => null],
    ]);

    expect($warehouse->locations()->count())->toBe(2);
    expect($warehouse->locations()->where('code', 'A1')->value('name'))->toBe('Aisle A1');
    expect($warehouse->locations()->where('code', 'A2')->value('name'))->toBeNull();
});

test('createWarehouse with null description stores null', function () {
    $warehouse = $this->service->createWarehouse('Warehouse X', 'Antwerpen', null);

    expect($warehouse->description)->toBeNull();
});

test('createWarehouse returns a persisted Warehouse model', function () {
    $warehouse = $this->service->createWarehouse('Warehouse Y', 'Luik', null);

    expect($warehouse)->toBeInstanceOf(Warehouse::class);
    expect($warehouse->exists)->toBeTrue();
    expect($warehouse->id)->toBeGreaterThan(0);
});

// --- addLocation ---

test('addLocation adds a location to an existing warehouse', function () {
    $warehouse = Warehouse::factory()->create();

    $location = $this->service->addLocation($warehouse, 'Z9', 'Zone Z9');

    expect($warehouse->locations()->count())->toBe(1);
    expect($location->code)->toBe('Z9');
    expect($location->name)->toBe('Zone Z9');
    expect($location->warehouse_id)->toBe($warehouse->id);
});

test('addLocation with null name stores null', function () {
    $warehouse = Warehouse::factory()->create();

    $location = $this->service->addLocation($warehouse, 'X1');

    expect($location->name)->toBeNull();
});

test('addLocation returns a persisted Location model', function () {
    $warehouse = Warehouse::factory()->create();

    $location = $this->service->addLocation($warehouse, 'B5', 'Bay 5');

    expect($location)->toBeInstanceOf(Location::class);
    expect($location->exists)->toBeTrue();
});

// --- deleteWarehouse ---

test('deleteWarehouse soft-deletes warehouse and its locations when no stock', function () {
    $warehouse = Warehouse::factory()->create();
    Location::factory()->count(3)->create(['warehouse_id' => $warehouse->id]);

    $this->service->deleteWarehouse($warehouse);

    expect(Warehouse::withTrashed()->find($warehouse->id)->deleted_at)->not->toBeNull();
    expect(Location::withTrashed()->where('warehouse_id', $warehouse->id)->count())->toBe(3);
    expect(Location::where('warehouse_id', $warehouse->id)->count())->toBe(0);
});

test('deleteWarehouse throws when a location has stock', function () {
    $warehouse = Warehouse::factory()->create();
    $location = Location::factory()->create(['warehouse_id' => $warehouse->id]);
    $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);

    Stock::create(['product_id' => $product->id, 'location_id' => $location->id, 'quantity' => 5]);

    expect(fn () => $this->service->deleteWarehouse($warehouse))
        ->toThrow(RuntimeException::class, 'Cannot delete warehouse');

    expect(Warehouse::find($warehouse->id))->not->toBeNull();
});

test('deleteWarehouse allows deletion when all stock quantities are zero', function () {
    $warehouse = Warehouse::factory()->create();
    $location = Location::factory()->create(['warehouse_id' => $warehouse->id]);
    $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);

    Stock::create(['product_id' => $product->id, 'location_id' => $location->id, 'quantity' => 0]);

    $this->service->deleteWarehouse($warehouse);

    expect(Warehouse::find($warehouse->id))->toBeNull();
});

// --- totalStock ---

test('totalStock returns sum of all location quantities', function () {
    $warehouse = Warehouse::factory()->create();
    $locationA = Location::factory()->create(['warehouse_id' => $warehouse->id]);
    $locationB = Location::factory()->create(['warehouse_id' => $warehouse->id]);
    $product = Product::factory()->create(['category_id' => Category::factory()->create()->id]);

    Stock::create(['product_id' => $product->id, 'location_id' => $locationA->id, 'quantity' => 10]);
    Stock::create(['product_id' => $product->id, 'location_id' => $locationB->id, 'quantity' => 7]);

    expect($this->service->totalStock($warehouse))->toBe(17);
});

test('totalStock returns zero for warehouse with no stock records', function () {
    $warehouse = Warehouse::factory()->create();
    Location::factory()->count(2)->create(['warehouse_id' => $warehouse->id]);

    expect($this->service->totalStock($warehouse))->toBe(0);
});

test('totalStock returns zero for warehouse with no locations', function () {
    $warehouse = Warehouse::factory()->create();

    expect($this->service->totalStock($warehouse))->toBe(0);
});
