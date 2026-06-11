<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Maakt de 5 demo-categorieën aan (Electronics, Office Supplies, Packaging,
 * Tools & Equipment, Safety) waar de producten naar verwijzen.
 */
class CategorySeeder extends Seeder
{
    /**
     * Maakt de 5 vaste productcategorieën aan waarnaar ProductSeeder later op naam verwijst.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic components and devices'],
            ['name' => 'Office Supplies', 'description' => 'Stationery and office materials'],
            ['name' => 'Packaging', 'description' => 'Boxes, tape, and packing materials'],
            ['name' => 'Tools & Equipment', 'description' => 'Hand tools and power tools'],
            ['name' => 'Safety', 'description' => 'Personal protective equipment'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
