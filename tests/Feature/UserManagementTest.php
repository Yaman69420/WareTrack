<?php

use App\Enums\UserRole;
use App\Livewire\Users\Index;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->worker = User::factory()->create();
});

test('admin can view users page', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->assertStatus(200);
});

test('worker cannot access users page', function () {
    $this->actingAs($this->worker)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('admin can create a new user', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->set('name', 'New Worker')
        ->set('email', 'newworker@test.com')
        ->set('password', 'password123')
        ->set('role', 'warehouse_worker')
        ->call('save')
        ->assertSet('showModal', false);

    expect(User::where('email', 'newworker@test.com')->exists())->toBeTrue();
});

test('user email must be unique', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'Duplicate')
        ->set('email', $this->worker->email)
        ->set('password', 'password123')
        ->set('role', 'warehouse_worker')
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);
});

test('password is required when creating a user', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', 'No Password')
        ->set('email', 'nopassword@test.com')
        ->set('password', '')
        ->set('role', 'warehouse_worker')
        ->call('save')
        ->assertHasErrors(['password' => 'required']);
});

test('admin can edit a user role', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openEdit', $this->worker)
        ->assertSet('role', 'warehouse_worker')
        ->set('role', 'admin')
        ->call('save');

    expect($this->worker->fresh()->role)->toBe(UserRole::Admin);
});

test('password is optional when editing a user', function () {
    $oldHash = $this->worker->password;

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openEdit', $this->worker)
        ->set('password', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->worker->fresh()->password)->toBe($oldHash);
});

test('admin cannot delete their own account', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $this->admin);

    expect(User::find($this->admin->id))->not->toBeNull();
});

test('admin can delete another user', function () {
    $target = User::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $target);

    expect(User::find($target->id))->toBeNull();
});

test('users can be searched by name', function () {
    User::factory()->create(['name' => 'Unique Zoekterm']);

    $component = Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'Unique Zoekterm');

    expect($component->get('users')->total())->toBe(1);
});
