<?php

use App\Livewire\Categories\Index;
use App\Models\Category;
use App\Models\Product;
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

test('category with linked products cannot be deleted', function () {
    $category = Category::factory()->create();
    Product::factory()->count(2)->create(['category_id' => $category->id]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $category)
        // Flux::toast() dispatcht een 'toast-show' Livewire-event: de variant
        // zit in 'dataset', de boodschap (met het productaantal) in 'slots'.
        ->assertDispatched('toast-show', function (string $name, array $params) {
            return ($params['dataset']['variant'] ?? null) === 'danger'
                && str_contains($params['slots']['text'] ?? '', '2');
        });

    expect(Category::find($category->id))->not->toBeNull();
    expect($category->fresh()->trashed())->toBeFalse();
});

test('category with only soft-deleted products can be deleted', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id])->delete();

    // De guard telt via de products()-relatie, die soft-deleted producten
    // standaard uitsluit — een categorie met enkel verwijderde producten
    // blokkeert dus niet.
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('delete', $category)
        ->assertDispatched('toast-show', fn (string $name, array $params) => ($params['dataset']['variant'] ?? null) === 'success');

    expect(Category::find($category->id))->toBeNull();
    expect(Category::withTrashed()->find($category->id))->not->toBeNull();
});

test('categories can be sorted by name via column header', function () {
    // Alpha eerst aangemaakt: de default (nieuwste eerst) zou Beta bovenaan
    // zetten, dus een Alpha-bovenaan bewijst dat de naamsortering werkt.
    Category::factory()->create(['name' => 'Alpha']);
    Category::factory()->create(['name' => 'Beta']);

    $component = Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('sort', 'name');

    // Eerste klik sorteert oplopend; tweede klik draait de richting om.
    expect($component->instance()->categories->first()->name)->toBe('Alpha');

    $component->call('sort', 'name');
    expect($component->instance()->categories->first()->name)->toBe('Beta');
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
