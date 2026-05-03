<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'sku' => strtoupper(Str::random(3) . '-' . fake()->unique()->numerify('####')),
            'description' => fake()->optional()->paragraph(),
            'image_path' => null,
            'min_stock' => fake()->numberBetween(0, 20),
        ];
    }
}
