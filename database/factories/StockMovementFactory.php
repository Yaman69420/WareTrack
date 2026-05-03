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
