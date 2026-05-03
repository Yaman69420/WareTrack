<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Warehouse> */
class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Warehouse ' . fake()->unique()->lexify('??'),
            'location' => fake()->city(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
