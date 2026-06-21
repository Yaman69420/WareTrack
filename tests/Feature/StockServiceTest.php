<?php

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;

beforeEach(function () {
    $this->service = app(StockService::class);
    $this->user = User::factory()->admin()->create();
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create(['category_id' => $this->category->id]);
    $this->warehouse = Warehouse::factory()->create();
    $this->locationA = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'A-01']);
    $this->locationB = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'B-01']);
});

// --- Incoming ---

test('incoming stock increases quantity and creates movement', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 10, $this->user);

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->locationA->id)->value('quantity'))->toBe(10);

    $movement = StockMovement::first();
    expect($movement->type)->toBe(StockMovementType::Incoming);
    expect($movement->quantity)->toBe(10);
    expect($movement->product_id)->toBe($this->product->id);
    expect($movement->location_id)->toBe($this->locationA->id);
    expect($movement->user_id)->toBe($this->user->id);
});

test('multiple incoming movements accumulate correctly', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 5, $this->user);
    $this->service->registerIncoming($this->product, $this->locationA, 3, $this->user);

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->locationA->id)->value('quantity'))->toBe(8);
});

test('incoming stores reference and notes', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 10, $this->user, 'PO-001', 'Test notes');

    $movement = StockMovement::first();
    expect($movement->reference)->toBe('PO-001');
    expect($movement->notes)->toBe('Test notes');
});

// --- Outgoing ---

test('outgoing stock decreases quantity and creates movement', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 10, $this->user);
    $this->service->registerOutgoing($this->product, $this->locationA, 4, $this->user);

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->locationA->id)->value('quantity'))->toBe(6);

    $movement = StockMovement::where('type', StockMovementType::Outgoing)->first();
    expect($movement->quantity)->toBe(-4);
});

test('outgoing throws InsufficientStockException when stock is too low', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 5, $this->user);

    expect(fn () => $this->service->registerOutgoing($this->product, $this->locationA, 10, $this->user))
        ->toThrow(InsufficientStockException::class);
});

test('outgoing with zero available stock throws exception', function () {
    expect(fn () => $this->service->registerOutgoing($this->product, $this->locationA, 1, $this->user))
        ->toThrow(InsufficientStockException::class);
});

test('stock cannot go negative', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 3, $this->user);

    try {
        $this->service->registerOutgoing($this->product, $this->locationA, 5, $this->user);
    } catch (InsufficientStockException) {
    }

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->locationA->id)->value('quantity'))->toBe(3);
});

// --- Transfer ---

test('transfer moves stock between locations', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 10, $this->user);
    $this->service->transfer($this->product, $this->locationA, $this->locationB, 6, $this->user);

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->locationA->id)->value('quantity'))->toBe(4);
    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->locationB->id)->value('quantity'))->toBe(6);

    $movement = StockMovement::where('type', StockMovementType::Transfer)->first();
    expect($movement->from_location_id)->toBe($this->locationA->id);
    expect($movement->to_location_id)->toBe($this->locationB->id);
    expect($movement->quantity)->toBe(6);
});

test('transfer throws InsufficientStockException when source has too little stock', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 3, $this->user);

    expect(fn () => $this->service->transfer($this->product, $this->locationA, $this->locationB, 5, $this->user))
        ->toThrow(InsufficientStockException::class);
});

test('transfer to same location throws InvalidArgumentException', function () {
    expect(fn () => $this->service->transfer($this->product, $this->locationA, $this->locationA, 5, $this->user))
        ->toThrow(InvalidArgumentException::class);
});

// --- Correction ---

test('correction sets stock to exact quantity', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 10, $this->user);
    $this->service->adjust($this->product, $this->locationA, 7, $this->user);

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->locationA->id)->value('quantity'))->toBe(7);

    $movement = StockMovement::where('type', StockMovementType::Correction)->first();
    expect($movement->quantity)->toBe(-3);
});

test('correction on empty stock creates stock record', function () {
    $this->service->adjust($this->product, $this->locationA, 15, $this->user);

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->locationA->id)->value('quantity'))->toBe(15);
});

// --- Helpers ---

test('getCurrentStock returns correct quantity', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 8, $this->user);

    expect($this->service->getCurrentStock($this->product, $this->locationA))->toBe(8);
    expect($this->service->getCurrentStock($this->product, $this->locationB))->toBe(0);
});

test('getTotalStockForProduct sums all locations', function () {
    $this->service->registerIncoming($this->product, $this->locationA, 5, $this->user);
    $this->service->registerIncoming($this->product, $this->locationB, 3, $this->user);

    expect($this->service->getTotalStockForProduct($this->product))->toBe(8);
});
