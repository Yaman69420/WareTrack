<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $locations = Location::all();

        foreach ($products as $product) {
            $assignedLocations = $locations->random(rand(1, 3));

            foreach ($assignedLocations as $location) {
                $quantity = match (true) {
                    str_starts_with($product->sku, 'EL') => rand(5, 50),
                    str_starts_with($product->sku, 'OF') => rand(10, 200),
                    str_starts_with($product->sku, 'PK') => rand(0, 150),
                    default => rand(0, 30),
                };

                Stock::firstOrCreate(
                    ['product_id' => $product->id, 'location_id' => $location->id],
                    ['quantity' => $quantity]
                );

                $product->locations()->syncWithoutDetaching([$location->id]);
            }
        }

        // Force a few products below min_stock for demo purposes
        $lowStockProduct = Product::where('sku', 'EL-0001')->first();
        if ($lowStockProduct) {
            Stock::where('product_id', $lowStockProduct->id)->update(['quantity' => 2]);
        }

        $lowStockProduct2 = Product::where('sku', 'SF-0002')->first();
        if ($lowStockProduct2) {
            Stock::where('product_id', $lowStockProduct2->id)->update(['quantity' => 1]);
        }
    }
}
