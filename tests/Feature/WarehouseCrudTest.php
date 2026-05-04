<?php

use App\Livewire\Warehouses\Index;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->worker = User::factory()->create();
});

test('admin can view warehouses page', function () {
    $this->actingAs($this->admin)
        ->get(route('warehouses.index'))
        ->assertOk();
});

test('worker cannot access warehouses page', function () {
    $this->actingAs($this->worker)
        ->get(route('warehouses.index'))
        ->assertForbidden();
});

test('guest is redirected from warehouses page', function () {
    $this->get(route('warehouses.index'))
        ->assertRedirect(route('login'));
});

test('admin can create a warehouse', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->set('name', 'Warehouse A')
        ->set('location', 'Brussels')
        ->call('save')
        ->assertSet('showModal', false);

    expect(Warehouse::where('name', 'Warehouse A')->exists())->toBeTrue();
});

test('warehouse name and location are required', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', '')
        ->set('location', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'location' => 'required']);
});

test('admin can edit a warehouse', function () {
    $warehouse = Warehouse::factory()->create(['name' => 'Old Name', 'location' => 'Ghent']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openEdit', $warehouse)
        ->assertSet('editingId', $warehouse->id)
        ->set('name', 'New Name')
        ->call('save')
        ->assertSet('showModal', false);

    expect($warehouse->fresh()->name)->toBe('New Name');
});

test('admin can delete a warehouse', function () {
    $warehouse = Warehouse::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $warehouse);

    expect(Warehouse::find($warehouse->id))->toBeNull();
    expect(Warehouse::withTrashed()->find($warehouse->id))->not->toBeNull();
});

test('warehouses are searchable by name and location', function () {
    Warehouse::factory()->create(['name' => 'Warehouse Alpha', 'location' => 'Brussels']);
    Warehouse::factory()->create(['name' => 'Warehouse Beta', 'location' => 'Ghent']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'Alpha')
        ->assertSee('Warehouse Alpha')
        ->assertDontSee('Warehouse Beta');
});
