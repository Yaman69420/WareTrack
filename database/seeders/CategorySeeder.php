<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
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
