<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    /**
     * Recept voor een locatie: eigen magazijn via geneste factory, unieke code in 'XX-99'-formaat
     * en een optionele naam (de code is in het domein leidend, de naam is extra).
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'code' => strtoupper(fake()->unique()->bothify('??-##')),
            'name' => fake()->optional()->words(2, true),
        ];
    }
}
