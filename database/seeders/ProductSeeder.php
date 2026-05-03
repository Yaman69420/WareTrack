<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $electronics = Category::where('name', 'Electronics')->first();
        $office = Category::where('name', 'Office Supplies')->first();
        $packaging = Category::where('name', 'Packaging')->first();
        $tools = Category::where('name', 'Tools & Equipment')->first();
        $safety = Category::where('name', 'Safety')->first();

        $products = [
            ['category_id' => $electronics->id, 'name' => 'USB-C Hub 7-port', 'sku' => 'EL-0001', 'min_stock' => 10],
            ['category_id' => $electronics->id, 'name' => 'HDMI Cable 2m', 'sku' => 'EL-0002', 'min_stock' => 20],
            ['category_id' => $electronics->id, 'name' => 'Wireless Mouse', 'sku' => 'EL-0003', 'min_stock' => 15],
            ['category_id' => $office->id, 'name' => 'A4 Paper (500 sheets)', 'sku' => 'OF-0001', 'min_stock' => 50],
            ['category_id' => $office->id, 'name' => 'Ballpoint Pen (box 20)', 'sku' => 'OF-0002', 'min_stock' => 30],
            ['category_id' => $packaging->id, 'name' => 'Cardboard Box Small', 'sku' => 'PK-0001', 'min_stock' => 100],
            ['category_id' => $packaging->id, 'name' => 'Bubble Wrap Roll 50m', 'sku' => 'PK-0002', 'min_stock' => 5],
            ['category_id' => $tools->id, 'name' => 'Screwdriver Set', 'sku' => 'TL-0001', 'min_stock' => 5],
            ['category_id' => $safety->id, 'name' => 'Safety Gloves L', 'sku' => 'SF-0001', 'min_stock' => 25],
            ['category_id' => $safety->id, 'name' => 'Safety Helmet', 'sku' => 'SF-0002', 'min_stock' => 10],
        ];

        foreach ($products as $product) {
            Product::create(array_merge($product, ['description' => null, 'image_path' => null]));
        }
    }
}
