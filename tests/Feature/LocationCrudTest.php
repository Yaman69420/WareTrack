<?php

use App\Livewire\Locations\Index;
use App\Models\Location;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->worker = User::factory()->create();
    $this->warehouse = Warehouse::factory()->create();
});

test('admin can view locations page', function () {
    $this->actingAs($this->admin)
        ->get(route('locations.index'))
        ->assertOk();
});

test('worker cannot access locations page', function () {
    $this->actingAs($this->worker)
        ->get(route('locations.index'))
        ->assertForbidden();
});

test('guest is redirected from locations page', function () {
    $this->get(route('locations.index'))
        ->assertRedirect(route('login'));
});

test('admin can create a location', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('warehouseId', $this->warehouse->id)
        ->set('code', 'AA-01')
        ->call('save')
        ->assertSet('showModal', false);

    expect(Location::where('code', 'AA-01')->exists())->toBeTrue();
});

test('location code is stored in uppercase', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('warehouseId', $this->warehouse->id)
        ->set('code', 'bb-02')
        ->call('save');

    expect(Location::where('code', 'BB-02')->exists())->toBeTrue();
});

test('warehouse and code are required', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('warehouseId', null)
        ->set('code', '')
        ->call('save')
        ->assertHasErrors(['warehouseId' => 'required', 'code' => 'required']);
});

test('admin can edit a location', function () {
    $location = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'AA-01']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openEdit', $location)
        ->assertSet('editingId', $location->id)
        ->set('code', 'BB-99')
        ->call('save')
        ->assertSet('showModal', false);

    expect($location->fresh()->code)->toBe('BB-99');
});

test('admin can delete a location', function () {
    $location = Location::factory()->create(['warehouse_id' => $this->warehouse->id]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $location);

    expect(Location::find($location->id))->toBeNull();
    expect(Location::withTrashed()->find($location->id))->not->toBeNull();
});

test('location with active stock cannot be deleted', function () {
    $location = Location::factory()->create(['warehouse_id' => $this->warehouse->id]);
    // Twee stock-records (elk via de factory aan een eigen product gekoppeld)
    // zodat het aantal in de toast-boodschap verifieerbaar is.
    Stock::factory()->count(2)->create(['location_id' => $location->id, 'quantity' => 5]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $location)
        // Flux::toast() dispatcht een 'toast-show' Livewire-event: de variant
        // zit in 'dataset', de boodschap (met het productaantal) in 'slots'.
        ->assertDispatched('toast-show', function (string $name, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'danger'
                && str_contains($params['slots']['text'] ?? '', '2');
        });

    expect(Location::find($location->id))->not->toBeNull();
    expect($location->fresh()->trashed())->toBeFalse();
});

test('location with only zero-quantity stock can be deleted', function () {
    $location = Location::factory()->create(['warehouse_id' => $this->warehouse->id]);
    Stock::factory()->create(['location_id' => $location->id, 'quantity' => 0]);

    // De guard telt enkel rijen met quantity > 0: een leeg stock-record
    // (alles ooit weggeboekt) mag het verwijderen niet blokkeren.
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $location)
        ->assertDispatched('toast-show', fn (string $name, array $params) => ($params['dataset']['variant'] ?? null) === 'success');

    expect(Location::find($location->id))->toBeNull();
    expect(Location::withTrashed()->find($location->id))->not->toBeNull();
});

test('locations can be filtered by warehouse', function () {
    $other = Warehouse::factory()->create();
    Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'AA-01']);
    Location::factory()->create(['warehouse_id' => $other->id, 'code' => 'BB-01']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('filterWarehouse', $this->warehouse->id)
        ->assertSee('AA-01')
        ->assertDontSee('BB-01');
});

test('search combined with warehouse filter does not leak other warehouses', function () {
    $other = Warehouse::factory()->create();
    Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'RACK-01']);
    // Code matches the search but belongs to another warehouse — without a
    // grouped OR the code-match would bypass the warehouse filter.
    Location::factory()->create(['warehouse_id' => $other->id, 'code' => 'RACK-02']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'RACK')
        ->set('filterWarehouse', $this->warehouse->id)
        ->assertSee('RACK-01')
        ->assertDontSee('RACK-02');
});
