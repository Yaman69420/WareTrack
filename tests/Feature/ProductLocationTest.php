<?php

use App\Livewire\Products\Index;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->category = Category::factory()->create();
    $this->warehouse = Warehouse::factory()->create();
    $this->locationA = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'AA-01']);
    $this->locationB = Location::factory()->create(['warehouse_id' => $this->warehouse->id, 'code' => 'AA-02']);
    $this->product = Product::factory()->create(['category_id' => $this->category->id]);
});

test('admin can open manage locations modal', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openLocations', $this->product)
        ->assertSet('showLocationsModal', true)
        ->assertSet('managingProductId', $this->product->id);
});

test('manage locations modal preloads existing locations', function () {
    $this->product->locations()->attach($this->locationA->id);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openLocations', $this->product)
        ->assertSet('selectedLocations', [(string) $this->locationA->id]);
});

test('admin can assign locations to a product', function () {
    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openLocations', $this->product)
        ->set('selectedLocations', [(string) $this->locationA->id, (string) $this->locationB->id])
        ->call('saveLocations')
        ->assertSet('showLocationsModal', false);

    expect($this->product->fresh()->locations()->count())->toBe(2);
});

test('saving locations syncs and removes deselected ones', function () {
    $this->product->locations()->attach([$this->locationA->id, $this->locationB->id]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openLocations', $this->product)
        ->set('selectedLocations', [(string) $this->locationA->id])
        ->call('saveLocations');

    expect($this->product->fresh()->locations()->count())->toBe(1);
    expect($this->product->fresh()->locations()->first()->id)->toBe($this->locationA->id);
});

test('admin can clear all locations from a product', function () {
    $this->product->locations()->attach($this->locationA->id);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('openLocations', $this->product)
        ->set('selectedLocations', [])
        ->call('saveLocations');

    expect($this->product->fresh()->locations()->count())->toBe(0);
});
