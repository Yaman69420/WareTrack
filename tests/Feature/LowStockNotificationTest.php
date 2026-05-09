<?php

use App\Events\StockMovementRegistered;
use App\Listeners\SendLowStockNotification;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\LowStockAlert;
use App\Services\StockService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->service = app(StockService::class);
    $this->admin = User::factory()->admin()->create();
    $this->warehouse = Warehouse::factory()->create();
    $this->location = Location::factory()->create(['warehouse_id' => $this->warehouse->id]);
    $this->category = Category::factory()->create();
});

// ---------------------------------------------------------------------------
// Event dispatching — assert the event is fired for all 4 stock operations
// ---------------------------------------------------------------------------

test('StockMovementRegistered is dispatched after incoming stock', function () {
    Event::fake([StockMovementRegistered::class]);

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);

    $this->service->registerIncoming($product, $this->location, 10, $this->admin);

    Event::assertDispatched(StockMovementRegistered::class, fn ($e) => $e->product->id === $product->id);
});

test('StockMovementRegistered is dispatched after outgoing stock', function () {
    Event::fake([StockMovementRegistered::class]);

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);
    Stock::factory()->create(['product_id' => $product->id, 'location_id' => $this->location->id, 'quantity' => 20]);

    $this->service->registerOutgoing($product, $this->location, 5, $this->admin);

    Event::assertDispatched(StockMovementRegistered::class);
});

test('StockMovementRegistered is dispatched after transfer', function () {
    Event::fake([StockMovementRegistered::class]);

    $locationB = Location::factory()->create(['warehouse_id' => $this->warehouse->id]);
    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);
    Stock::factory()->create(['product_id' => $product->id, 'location_id' => $this->location->id, 'quantity' => 20]);

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
// Queue — assert the listener is pushed onto the queue (ShouldQueue)
// ---------------------------------------------------------------------------

test('SendLowStockNotification listener is queued on the notifications queue', function () {
    Queue::fake();

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);

    $this->service->registerIncoming($product, $this->location, 5, $this->admin);

    // Laravel wraps ShouldQueue listeners in CallQueuedListener internally
    Queue::assertPushedOn(
        'notifications',
        CallQueuedListener::class,
        fn ($job) => $job->class === SendLowStockNotification::class,
    );
});

// ---------------------------------------------------------------------------
// Listener unit tests — call handle() directly so we can assert notifications
// without spinning up a real queue worker
// ---------------------------------------------------------------------------

function fireListener(Product $product, Location $location, User $user): void
{
    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'user_id' => $user->id,
    ]);

    (new SendLowStockNotification)->handle(new StockMovementRegistered($product, $movement));
}

test('low-stock alert is sent to admins when stock drops below minimum', function () {
    Notification::fake();
    Cache::flush();

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 10]);
    Stock::factory()->create(['product_id' => $product->id, 'location_id' => $this->location->id, 'quantity' => 5]);

    fireListener($product->fresh(['stock', 'category']), $this->location, $this->admin);

    Notification::assertSentTo($this->admin, LowStockAlert::class);
});

test('no low-stock alert is sent when stock is above minimum', function () {
    Notification::fake();
    Cache::flush();

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 5]);
    Stock::factory()->create(['product_id' => $product->id, 'location_id' => $this->location->id, 'quantity' => 20]);

    fireListener($product->fresh(['stock', 'category']), $this->location, $this->admin);

    Notification::assertNothingSent();
});

test('no low-stock alert when min_stock is zero', function () {
    Notification::fake();
    Cache::flush();

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);
    Stock::factory()->create(['product_id' => $product->id, 'location_id' => $this->location->id, 'quantity' => 0]);

    fireListener($product->fresh(['stock', 'category']), $this->location, $this->admin);

    Notification::assertNothingSent();
});

// ---------------------------------------------------------------------------
// 24-hour throttle
// ---------------------------------------------------------------------------

test('low-stock alert is only sent once per 24 hours per product', function () {
    Notification::fake();
    Cache::flush();

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 10]);
    Stock::factory()->create(['product_id' => $product->id, 'location_id' => $this->location->id, 'quantity' => 5]);

    $fresh = $product->fresh(['stock', 'category']);

    fireListener($fresh, $this->location, $this->admin);
    fireListener($fresh, $this->location, $this->admin); // throttled

    Notification::assertSentToTimes($this->admin, LowStockAlert::class, 1);
});

test('low-stock alert fires again after cache expires', function () {
    Notification::fake();
    Cache::flush();

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 10]);
    Stock::factory()->create(['product_id' => $product->id, 'location_id' => $this->location->id, 'quantity' => 5]);

    $fresh = $product->fresh(['stock', 'category']);

    fireListener($fresh, $this->location, $this->admin);
    Notification::assertSentToTimes($this->admin, LowStockAlert::class, 1);

    Cache::forget("low_stock_alert:{$product->id}");

    fireListener($fresh, $this->location, $this->admin);
    Notification::assertSentToTimes($this->admin, LowStockAlert::class, 2);
});

// ---------------------------------------------------------------------------
// Listener skips products with no min_stock configured
// ---------------------------------------------------------------------------

test('listener skips products with no min_stock configured', function () {
    Notification::fake();
    Cache::flush();

    $product = Product::factory()->create(['category_id' => $this->category->id, 'min_stock' => 0]);
    Stock::factory()->create(['product_id' => $product->id, 'location_id' => $this->location->id, 'quantity' => 0]);

    fireListener($product->fresh(['stock', 'category']), $this->location, $this->admin);

    Notification::assertNothingSent();
});
