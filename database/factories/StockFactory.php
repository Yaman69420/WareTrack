<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Stock> */
class StockFactory extends Factory
{
    /**
     * Recept voor een voorraadrij: vers product en verse locatie via geneste factories,
     * met 0 tot 200 stuks — 0 mag, want een lege rij is een geldig voorraadscenario.
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'location_id' => Location::factory(),
            'quantity' => fake()->numberBetween(0, 200),
        ];
    }
}
