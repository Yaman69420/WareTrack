<?php

use App\Livewire\Suppliers\Index;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->worker = User::factory()->create(); // warehouse_worker by default
});

test('admin can view suppliers page', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertStatus(200);
});

test('warehouse worker can view suppliers page', function () {
    Livewire::actingAs($this->worker)
        ->test(Index::class)
        ->assertStatus(200);
});

test('guest is redirected from suppliers page', function () {
    $this->get(route('suppliers.index'))
        ->assertRedirect(route('login'));
});

test('admin can create a supplier', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->set('name', 'Acme Corp')
        ->set('email', 'info@acme.com')
        ->set('phone', '+32 123 45 67')
        ->call('save')
        ->assertSet('showModal', false);

    expect(Supplier::where('name', 'Acme Corp')->exists())->toBeTrue();
});

test('supplier name is required', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('supplier email must be valid', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'Acme Corp')
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['email' => 'email']);
});

test('supplier can be created without optional fields', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'Minimal Supplier')
        ->call('save')
        ->assertSet('showModal', false);

    $supplier = Supplier::where('name', 'Minimal Supplier')->first();
    expect($supplier)->not->toBeNull();
    expect($supplier->email)->toBeNull();
    expect($supplier->phone)->toBeNull();
});

test('admin can edit a supplier', function () {
    $supplier = Supplier::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openEdit', $supplier)
        ->assertSet('editingId', $supplier->id)
        ->assertSet('name', 'Old Name')
        ->set('name', 'New Name')
        ->call('save')
        ->assertSet('showModal', false);

    expect($supplier->fresh()->name)->toBe('New Name');
});

test('admin can delete a supplier', function () {
    $supplier = Supplier::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $supplier);

    expect(Supplier::find($supplier->id))->toBeNull();
    expect(Supplier::withTrashed()->find($supplier->id))->not->toBeNull();
});

test('warehouse worker cannot create a supplier', function () {
    Livewire::actingAs($this->worker)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'Forbidden Corp')
        ->call('save')
        ->assertForbidden();

    expect(Supplier::count())->toBe(0);
});

test('warehouse worker cannot update a supplier', function () {
    $supplier = Supplier::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->worker)
        ->test(Index::class)
        ->call('openEdit', $supplier)
        ->set('name', 'New Name')
        ->call('save')
        ->assertForbidden();

    expect($supplier->fresh()->name)->toBe('Old Name');
});

test('warehouse worker cannot delete a supplier', function () {
    $supplier = Supplier::factory()->create();

    Livewire::actingAs($this->worker)
        ->test(Index::class)
        ->call('delete', $supplier)
        ->assertForbidden();

    expect(Supplier::find($supplier->id))->not->toBeNull();
});

test('suppliers can be searched by name', function () {
    Supplier::factory()->create(['name' => 'Acme Corp']);
    Supplier::factory()->create(['name' => 'Beta Supplies']);

    $component = Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'Acme');

    expect($component->get('suppliers')->total())->toBe(1);
});

test('suppliers can be sorted by name via column header', function () {
    Supplier::factory()->create(['name' => 'Acme Corp']);
    Supplier::factory()->create(['name' => 'Zenith Ltd']);

    $component = Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('sort', 'name');

    // Eerste klik sorteert oplopend; tweede klik draait de richting om.
    expect($component->instance()->suppliers->first()->name)->toBe('Acme Corp');

    $component->call('sort', 'name');
    expect($component->instance()->suppliers->first()->name)->toBe('Zenith Ltd');
});

test('suppliers can be searched by email', function () {
    Supplier::factory()->create(['name' => 'Acme Corp', 'email' => 'acme@example.com']);
    Supplier::factory()->create(['name' => 'Beta Supplies', 'email' => 'beta@example.com']);

    $component = Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'acme@');

    expect($component->get('suppliers')->total())->toBe(1);
});
