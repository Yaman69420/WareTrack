<?php

use App\Enums\DeliveryStatus;
use App\Livewire\Deliveries\Create;
use App\Livewire\Deliveries\Index;
use App\Livewire\Deliveries\Show;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->worker = User::factory()->create();

    $this->supplier = Supplier::factory()->create();
    $this->warehouse = Warehouse::factory()->create();
    $this->location = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'A-01']);
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create(['category_id' => $this->category->id]);
});

test('admin can view deliveries index', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertStatus(200);
});

test('worker can view deliveries index', function () {
    Livewire::actingAs($this->worker)
        ->test(Index::class)
        ->assertStatus(200);
});

test('admin can create a delivery with items', function () {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('supplierId', $this->supplier->id)
        ->set('reference', 'PO-2026-001')
        ->set('items.0.product_id', $this->product->id)
        ->set('items.0.location_id', $this->location->id)
        ->set('items.0.quantity_ordered', 10)
        ->call('save');

    $delivery = Delivery::first();
    expect($delivery)->not->toBeNull();
    expect($delivery->supplier_id)->toBe($this->supplier->id);
    expect($delivery->status)->toBe(DeliveryStatus::Pending);
    expect($delivery->items()->count())->toBe(1);
    expect($delivery->items()->first()->quantity_ordered)->toBe(10);
});

test('warehouse worker cannot access the delivery create page', function () {
    $this->actingAs($this->worker)
        ->get(route('deliveries.create'))
        ->assertForbidden();
});

test('admin can access the delivery create page', function () {
    $this->actingAs($this->admin)
        ->get(route('deliveries.create'))
        ->assertOk();
});

test('warehouse worker cannot create a delivery', function () {
    // mount() authorizes against DeliveryPolicy::create, so the component itself 403s
    Livewire::actingAs($this->worker)
        ->test(Create::class)
        ->assertForbidden();

    expect(Delivery::count())->toBe(0);
});

test('delivery requires a supplier', function () {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('supplierId', null)
        ->set('items.0.product_id', $this->product->id)
        ->set('items.0.location_id', $this->location->id)
        ->set('items.0.quantity_ordered', 5)
        ->call('save')
        ->assertHasErrors(['supplierId' => 'required']);
});

test('delivery item requires product, location and quantity', function () {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('supplierId', $this->supplier->id)
        ->set('items.0.product_id', null)
        ->set('items.0.location_id', null)
        ->set('items.0.quantity_ordered', 0)
        ->call('save')
        ->assertHasErrors(['items.0.product_id', 'items.0.location_id', 'items.0.quantity_ordered']);
});

test('can add and remove items dynamically', function () {
    $component = Livewire::actingAs($this->admin)
        ->test(Create::class);

    // Items start empty — supplier must be selected first
    expect(count($component->get('items')))->toBe(0);

    $component->call('addItem');
    expect(count($component->get('items')))->toBe(1);

    $component->call('addItem');
    expect(count($component->get('items')))->toBe(2);

    $component->call('removeItem', 0);
    expect(count($component->get('items')))->toBe(1);
});

test('processing a delivery increases stock', function () {
    $delivery = Delivery::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->admin->id,
        'status' => DeliveryStatus::Pending,
    ]);

    $delivery->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
    ]);

    $item = $delivery->items()->first();

    Livewire::actingAs($this->admin)
        ->test(Show::class, ['delivery' => $delivery])
        ->set("receivedQuantities.{$item->id}", 10)
        ->call('process');

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->location->id)->value('quantity'))->toBe(10);
    expect($delivery->fresh()->status)->toBe(DeliveryStatus::Received);
});

test('warehouse worker can process a delivery', function () {
    $delivery = Delivery::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->admin->id,
        'status' => DeliveryStatus::Pending,
    ]);

    $delivery->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
    ]);

    $item = $delivery->items()->first();

    Livewire::actingAs($this->worker)
        ->test(Show::class, ['delivery' => $delivery])
        ->set("receivedQuantities.{$item->id}", 10)
        ->call('process');

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->location->id)->value('quantity'))->toBe(10);
    expect($delivery->fresh()->status)->toBe(DeliveryStatus::Received);
});

test('partial delivery sets status to partial', function () {
    $delivery = Delivery::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->admin->id,
        'status' => DeliveryStatus::Pending,
    ]);

    $delivery->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
    ]);

    $item = $delivery->items()->first();

    Livewire::actingAs($this->admin)
        ->test(Show::class, ['delivery' => $delivery])
        ->set("receivedQuantities.{$item->id}", 4)
        ->call('process');

    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->location->id)->value('quantity'))->toBe(4);
    expect($delivery->fresh()->status)->toBe(DeliveryStatus::Partial);
});

test('fully received delivery cannot be processed again', function () {
    $delivery = Delivery::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->admin->id,
        'status' => DeliveryStatus::Received,
        'received_at' => now(),
    ]);

    $delivery->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'quantity_ordered' => 10,
        'quantity_received' => 10,
    ]);

    $stockBefore = Stock::where('product_id', $this->product->id)->value('quantity') ?? 0;

    Livewire::actingAs($this->admin)
        ->test(Show::class, ['delivery' => $delivery])
        ->call('process');

    expect(Stock::where('product_id', $this->product->id)->value('quantity') ?? 0)->toBe($stockBefore);
});

test('over-receiving is capped at the ordered quantity', function () {
    $delivery = Delivery::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->admin->id,
        'status' => DeliveryStatus::Pending,
    ]);

    $delivery->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
    ]);

    $item = $delivery->items()->first();

    Livewire::actingAs($this->admin)
        ->test(Show::class, ['delivery' => $delivery])
        ->set("receivedQuantities.{$item->id}", 15)
        ->call('process');

    // Gecapt op het bestelde aantal: nooit meer ontvangen dan besteld
    expect($item->fresh()->quantity_received)->toBe(10);
    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->location->id)->value('quantity'))->toBe(10);
    expect($delivery->fresh()->status)->toBe(DeliveryStatus::Received);
});

test('over-receiving on a partial delivery is capped at the remaining quantity', function () {
    $delivery = Delivery::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->admin->id,
        'status' => DeliveryStatus::Partial,
    ]);

    $delivery->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'quantity_ordered' => 10,
        'quantity_received' => 4,
    ]);

    $item = $delivery->items()->first();

    Livewire::actingAs($this->admin)
        ->test(Show::class, ['delivery' => $delivery])
        ->set("receivedQuantities.{$item->id}", 99)
        ->call('process');

    // Max ontvangbaar was 10 - 4 = 6: de stock stijgt met exact dat saldo
    expect($item->fresh()->quantity_received)->toBe(10);
    expect(Stock::where('product_id', $this->product->id)->where('location_id', $this->location->id)->value('quantity'))->toBe(6);
    expect($delivery->fresh()->status)->toBe(DeliveryStatus::Received);
});

test('deliveries can be filtered by status', function () {
    Delivery::factory()->create(['supplier_id' => $this->supplier->id, 'user_id' => $this->admin->id, 'status' => DeliveryStatus::Pending]);
    Delivery::factory()->create(['supplier_id' => $this->supplier->id, 'user_id' => $this->admin->id, 'status' => DeliveryStatus::Received, 'received_at' => now()]);

    $component = Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('filterStatus', 'pending');

    expect($component->get('deliveries')->total())->toBe(1);
});

test('deliveries can be sorted by reference via column header', function () {
    Delivery::factory()->create(['supplier_id' => $this->supplier->id, 'user_id' => $this->admin->id, 'reference' => 'PO-0001']);
    Delivery::factory()->create(['supplier_id' => $this->supplier->id, 'user_id' => $this->admin->id, 'reference' => 'PO-9999']);

    $component = Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('sort', 'reference');

    // Eerste klik sorteert oplopend; tweede klik draait de richting om.
    expect($component->instance()->deliveries->first()->reference)->toBe('PO-0001');

    $component->call('sort', 'reference');
    expect($component->instance()->deliveries->first()->reference)->toBe('PO-9999');
});
