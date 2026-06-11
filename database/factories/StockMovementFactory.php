<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockMovement> */
class StockMovementFactory extends Factory
{
    /**
     * Recept voor een voorraadbeweging: standaard een inkomende boeking van 1 tot 50 stuks.
     * De van/naar-locaties blijven leeg — die zijn enkel relevant bij transfers en worden
     * in tests per geval ingevuld.
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'location_id' => Location::factory(),
            'from_location_id' => null,
            'to_location_id' => null,
            'user_id' => User::factory(),
            'type' => StockMovementType::Incoming,
            'quantity' => fake()->numberBetween(1, 50),
            'reference' => null,
            'notes' => null,
        ];
    }
}
