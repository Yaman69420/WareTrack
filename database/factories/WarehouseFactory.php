<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Warehouse> */
class WarehouseFactory extends Factory
{
    /**
     * Recept voor een magazijn: unieke naam ('Warehouse XX'), een stad als ligging
     * en optioneel een korte beschrijving.
     */
    public function definition(): array
    {
        return [
            'name' => 'Warehouse '.fake()->unique()->lexify('??'),
            'location' => fake()->city(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
