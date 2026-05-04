<?php

use App\Livewire\Categories\Index;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->worker = User::factory()->create(); // default role = WarehouseWorker
});

test('admin can view categories page', function () {
    $this->actingAs($this->admin)
        ->get(route('categories.index'))
        ->assertOk();
});

test('worker cannot access categories page', function () {
    $this->actingAs($this->worker)
        ->get(route('categories.index'))
        ->assertForbidden();
});

test('guest is redirected from categories page', function () {
    $this->get(route('categories.index'))
        ->assertRedirect(route('login'));
});

test('admin can create a category', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->set('name', 'Electronics')
        ->set('description', 'Electronic goods')
        ->call('save')
        ->assertSet('showModal', false);

    expect(Category::where('name', 'Electronics')->exists())->toBeTrue();
});

test('category name is required', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('category name cannot exceed 100 characters', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openCreate')
        ->set('name', str_repeat('a', 101))
        ->call('save')
        ->assertHasErrors(['name' => 'max']);
});

test('admin can edit a category', function () {
    $category = Category::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openEdit', $category)
        ->assertSet('editingId', $category->id)
        ->assertSet('name', 'Old Name')
        ->set('name', 'New Name')
        ->call('save')
        ->assertSet('showModal', false);

    expect($category->fresh()->name)->toBe('New Name');
});

test('admin can delete a category', function () {
    $category = Category::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $category);

    expect(Category::find($category->id))->toBeNull();
    expect(Category::withTrashed()->find($category->id))->not->toBeNull();
});

test('categories are searchable', function () {
    Category::factory()->create(['name' => 'Electronics']);
    Category::factory()->create(['name' => 'Furniture']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('search', 'Elec')
        ->assertSee('Electronics')
        ->assertDontSee('Furniture');
});
