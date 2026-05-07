<?php

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReportService;
use App\Services\StockService;

beforeEach(function () {
    $this->service = app(ReportService::class);
    $this->stock = app(StockService::class);
    $this->user = User::factory()->admin()->create();
    $this->category = Category::factory()->create();
    $this->warehouse = Warehouse::factory()->create();
    $this->locationA = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'A-01']);
    $this->locationB = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'B-01']);
});

// --- Low Stock ---

test('getLowStockProducts returns products below minimum', function () {
    $low = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 10]);
    $ok = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 5]);

    Stock::create(['product_id' => $low->id, 'location_id' => $this->locationA->id, 'quantity' => 3]);
    Stock::create(['product_id' => $ok->id, 'location_id' => $this->locationA->id, 'quantity' => 20]);

    $result = $this->service->getLowStockProducts();

    expect($result->contains($low))->toBeTrue();
    expect($result->contains($ok))->toBeFalse();
});

test('getLowStockProducts ignores products with min_stock zero', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);

    $result = $this->service->getLowStockProducts();

    expect($result->contains($product))->toBeFalse();
});

test('getLowStockProducts returns empty when all stock is sufficient', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 5]);
    Stock::create(['product_id' => $product->id, 'location_id' => $this->locationA->id, 'quantity' => 10]);

    expect($this->service->getLowStockProducts())->toBeEmpty();
});

// --- Stock per Location ---

test('getStockPerLocation returns all stock lines', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);
    Stock::create(['product_id' => $product->id, 'location_id' => $this->locationA->id, 'quantity' => 5]);
    Stock::create(['product_id' => $product->id, 'location_id' => $this->locationB->id, 'quantity' => 3]);

    $result = $this->service->getStockPerLocation();

    expect($result->count())->toBe(2);
});

test('getStockPerLocation filters by warehouse', function () {
    $warehouse2 = Warehouse::factory()->create();
    $locationC = Location::factory()->create(['warehouse_id' => $warehouse2->id, 'code' => 'C-01']);
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    Stock::create(['product_id' => $product->id, 'location_id' => $this->locationA->id, 'quantity' => 5]);
    Stock::create(['product_id' => $product->id, 'location_id' => $locationC->id, 'quantity' => 3]);

    $result = $this->service->getStockPerLocation($this->warehouse->id);

    expect($result->count())->toBe(1);
    expect($result->first()->location->warehouse_id)->toBe($this->warehouse->id);
});

test('getStockPerLocation excludes zero quantity lines', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);
    Stock::create(['product_id' => $product->id, 'location_id' => $this->locationA->id, 'quantity' => 0]);

    expect($this->service->getStockPerLocation())->toBeEmpty();
});

// --- Movements per Period ---

test('getMovementsForPeriod returns movements within range', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);
    $this->stock->registerIncoming($product, $this->locationA, 5, $this->user);

    $result = $this->service->getMovementsForPeriod(now()->subDay(), now()->addDay());

    expect($result->count())->toBe(1);
});

test('getMovementsForPeriod excludes movements outside range', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);
    $this->stock->registerIncoming($product, $this->locationA, 5, $this->user);

    $result = $this->service->getMovementsForPeriod(now()->addDay(), now()->addDays(2));

    expect($result)->toBeEmpty();
});

test('getMovementsForPeriod filters by type', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);
    $this->stock->registerIncoming($product, $this->locationA, 10, $this->user);
    $this->stock->registerOutgoing($product, $this->locationA, 3, $this->user);

    $result = $this->service->getMovementsForPeriod(now()->subDay(), now()->addDay(), 'incoming');

    expect($result->count())->toBe(1);
    expect($result->first()->type->value)->toBe('incoming');
});
