<?php

use App\Enums\UserRole;
use App\Events\StockMovementRegistered;
use App\Listeners\SendLowStockNotification;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\LowStockAlert;
use App\Services\StockService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->service  = app(StockService::class);
    $this->admin    = User::factory()->admin()->create();
    $this->warehouse = Warehouse::factory()->create();
    $this->location = Location::factory()->create(['warehouse_id' => $this->warehouse->id]);
    $this->category = Category::factory()->create();
});

// ---------------------------------------------------------------------------
// Event dispatching
// ---------------------------------------------------------------------------

test('StockMovementRegistered is dispatched after incoming stock', function () {
    Event::fake([StockMovementRegistered::class]);

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);

    $this->service->registerIncoming($product, $this->location, 10, $this->admin);

    Event::assertDispatched(StockMovementRegistered::class, function ($event) use ($product) {
        return $event->product->id === $product->id;
    });
});

test('StockMovementRegistered is dispatched after outgoing stock', function () {
    Event::fake([StockMovementRegistered::class]);

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);

    // Seed stock directly so outgoing doesn't throw InsufficientStockException
    \App\Models\Stock::factory()->create([
        'product_id'  => $product->id,
        'location_id' => $this->location->id,
        'quantity'    => 20,
    ]);

    $this->service->registerOutgoing($product, $this->location, 5, $this->admin);

    Event::assertDispatched(StockMovementRegistered::class);
});

test('StockMovementRegistered is dispatched after transfer', function () {
    Event::fake([StockMovementRegistered::class]);

    $locationB = Location::factory()->create(['warehouse_id' => $this->warehouse->id]);
    $product   = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);

    \App\Models\Stock::factory()->create([
        'product_id'  => $product->id,
        'location_id' => $this->location->id,
        'quantity'    => 20,
    ]);

    $this->service->transfer($product, $this->location, $locationB, 5, $this->admin);

    Event::assertDispatched(StockMovementRegistered::class);
});

test('StockMovementRegistered is dispatched after stock correction', function () {
    Event::fake([StockMovementRegistered::class]);

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);

    $this->service->adjust($product, $this->location, 50, $this->admin);

    Event::assertDispatched(StockMovementRegistered::class);
});

// ---------------------------------------------------------------------------
// Notification sending
// ---------------------------------------------------------------------------

test('low-stock alert is sent to admins when stock drops below minimum', function () {
    Notification::fake();

    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'min_stock'   => 10,
    ]);

    // Outgoing will bring total to 5 — below min_stock of 10
    \App\Models\Stock::factory()->create([
        'product_id'  => $product->id,
        'location_id' => $this->location->id,
        'quantity'    => 15,
    ]);

    $this->service->registerOutgoing($product, $this->location, 10, $this->admin);

    Notification::assertSentTo($this->admin, LowStockAlert::class);
});

test('no low-stock alert is sent when stock is above minimum', function () {
    Notification::fake();

    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'min_stock'   => 5,
    ]);

    $this->service->registerIncoming($product, $this->location, 20, $this->admin);

    Notification::assertNothingSent();
});

test('no low-stock alert when min_stock is zero', function () {
    Notification::fake();

    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'min_stock'   => 0,
    ]);

    $this->service->registerIncoming($product, $this->location, 5, $this->admin);

    Notification::assertNothingSent();
});

// ---------------------------------------------------------------------------
// 24-hour throttle
// ---------------------------------------------------------------------------

test('low-stock alert is only sent once per 24 hours per product', function () {
    Notification::fake();
    Cache::flush();

    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'min_stock'   => 10,
    ]);

    \App\Models\Stock::factory()->create([
        'product_id'  => $product->id,
        'location_id' => $this->location->id,
        'quantity'    => 100,
    ]);

    // First outgoing — drops to 5 (below 10) → notification sent
    $this->service->registerOutgoing($product, $this->location, 95, $this->admin);

    // Second outgoing — still below minimum → should be throttled
    $this->service->registerOutgoing($product, $this->location, 1, $this->admin);

    Notification::assertSentToTimes($this->admin, LowStockAlert::class, 1);
});

test('low-stock alert fires again after cache expires', function () {
    Notification::fake();
    Cache::flush();

    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'min_stock'   => 10,
    ]);

    \App\Models\Stock::factory()->create([
        'product_id'  => $product->id,
        'location_id' => $this->location->id,
        'quantity'    => 50,
    ]);

    // Trigger notification
    $this->service->registerOutgoing($product, $this->location, 45, $this->admin);
    Notification::assertSentToTimes($this->admin, LowStockAlert::class, 1);

    // Manually clear throttle cache (simulates 24h passing)
    Cache::forget("low_stock_alert:{$product->id}");

    // Add stock so we can subtract again, then drop below minimum again
    $this->service->registerIncoming($product, $this->location, 10, $this->admin);
    // Total is now 15, above min — no notification
    Notification::assertSentToTimes($this->admin, LowStockAlert::class, 1);

    $this->service->registerOutgoing($product, $this->location, 10, $this->admin);
    // Total is now 5, below min — cache cleared → notification fires again
    Notification::assertSentToTimes($this->admin, LowStockAlert::class, 2);
});

// ---------------------------------------------------------------------------
// Listener unit test
// ---------------------------------------------------------------------------

test('listener skips products with no min_stock configured', function () {
    Notification::fake();

    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'min_stock'   => 0,
    ]);

    \App\Models\Stock::factory()->create([
        'product_id'  => $product->id,
        'location_id' => $this->location->id,
        'quantity'    => 0,
    ]);

    $movement = \App\Models\StockMovement::factory()->create([
        'product_id'  => $product->id,
        'location_id' => $this->location->id,
        'user_id'     => $this->admin->id,
    ]);

    $listener = new SendLowStockNotification();
    $listener->handle(new StockMovementRegistered($product, $movement));

    Notification::assertNothingSent();
});
