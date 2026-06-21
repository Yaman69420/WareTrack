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

test('movements can be sorted by quantity via column header', function () {
    StockMovement::factory()->create(['quantity' => 5]);
    StockMovement::factory()->create(['quantity' => 50]);

    $component = Livewire::actingAs(workerUser())
        ->test(Index::class)
        ->call('sort', 'quantity');

    // Eerste klik sorteert oplopend; tweede klik draait de richting om.
    expect($component->instance()->movements->first()->quantity)->toBe(5);

    $component->call('sort', 'quantity');
    expect($component->instance()->movements->first()->quantity)->toBe(50);
});

test('sorting ignores columns outside the whitelist', function () {
    StockMovement::factory()->create();

    $component = Livewire::actingAs(workerUser())
        ->test(Index::class)
        ->call('sort', 'user_id; DROP TABLE stock_movements')
        ->assertSet('sortBy', '');

    // Onbekende kolom: stille no-op, de default sortering blijft gelden.
    expect($component->instance()->movements->count())->toBe(1);
});
