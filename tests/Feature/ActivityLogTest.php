<?php

use App\Enums\StockMovementType;
use App\Livewire\Activity\Index;
use App\Models\Product;
use App\Models\StockMovement;
use Livewire\Livewire;

test('guest is redirected from activity page', function () {
    $this->get(route('activity.index'))
        ->assertRedirect(route('login'));
});

test('worker can view activity page', function () {
    $this->actingAs(workerUser())
        ->get(route('activity.index'))
        ->assertOk();
});

test('search combined with type filter does not leak other types', function () {
    $incoming = StockMovement::factory()->create([
        'product_id' => Product::factory()->create(['name' => 'Alpha Widget'])->id,
        'type' => StockMovementType::Incoming,
    ]);
    // Productnaam matcht de zoekterm maar het type niet — zonder gegroepeerde
    // OR zou de naam-match de typefilter omzeilen (AND/OR-precedentie).
    StockMovement::factory()->create([
        'product_id' => Product::factory()->create(['name' => 'Alpha Gadget'])->id,
        'type' => StockMovementType::Transfer,
    ]);

    $component = Livewire::actingAs(workerUser())
        ->test(Index::class)
        ->set('search', 'Alpha')
        ->set('type', StockMovementType::Incoming->value);

    // Assert op het queryresultaat zelf i.p.v. op de pagina-HTML.
    $names = $component->instance()->movements->pluck('product.name');

    expect($names)->toContain('Alpha Widget')
        ->not->toContain('Alpha Gadget');
});
