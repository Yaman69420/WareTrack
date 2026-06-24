<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 * Maakt de 20 demo-producten aan (5 EL, 5 OF, 4 PK, 3 TL, 3 SF), elk met
 * unieke SKU, een min_stock-drempel voor de low-stock-bewaking en een demo-tegel
 * (database/seeders/product-images/<SKU>.png) die naar de public-disk gekopieerd wordt.
 */
class ProductSeeder extends Seeder
{
    /**
     * Maakt 20 demo-producten verdeeld over de 5 categorieën (5 EL, 5 OF, 4 PK, 3 TL, 3 SF).
     * De SKU's liggen vast omdat StockSeeder, SupplierProductSeeder en DeliverySeeder erop opzoeken.
     */
    public function run(): void
    {
        $electronics = Category::where('name', 'Electronics')->first();
        $office = Category::where('name', 'Office Supplies')->first();
        $packaging = Category::where('name', 'Packaging')->first();
        $tools = Category::where('name', 'Tools & Equipment')->first();
        $safety = Category::where('name', 'Safety')->first();

        $products = [
            // Electronics (5)
            ['category_id' => $electronics->id, 'name' => 'USB-C Hub 7-port', 'sku' => 'EL-0001', 'min_stock' => 10],
            ['category_id' => $electronics->id, 'name' => 'HDMI Cable 2m', 'sku' => 'EL-0002', 'min_stock' => 20],
            ['category_id' => $electronics->id, 'name' => 'Wireless Mouse', 'sku' => 'EL-0003', 'min_stock' => 15],
            ['category_id' => $electronics->id, 'name' => 'Mechanical Keyboard', 'sku' => 'EL-0004', 'min_stock' => 8],
            ['category_id' => $electronics->id, 'name' => 'Monitor Stand Adjustable', 'sku' => 'EL-0005', 'min_stock' => 5],
            // Office Supplies (5)
            ['category_id' => $office->id, 'name' => 'A4 Paper (500 sheets)', 'sku' => 'OF-0001', 'min_stock' => 50],
            ['category_id' => $office->id, 'name' => 'Ballpoint Pen (box 20)', 'sku' => 'OF-0002', 'min_stock' => 30],
            ['category_id' => $office->id, 'name' => 'Sticky Notes (pack 5)', 'sku' => 'OF-0003', 'min_stock' => 40],
            ['category_id' => $office->id, 'name' => 'Stapler Heavy Duty', 'sku' => 'OF-0004', 'min_stock' => 10],
            ['category_id' => $office->id, 'name' => 'Label Printer Tape 12mm', 'sku' => 'OF-0005', 'min_stock' => 20],
            // Packaging (4)
            ['category_id' => $packaging->id, 'name' => 'Cardboard Box Small', 'sku' => 'PK-0001', 'min_stock' => 100],
            ['category_id' => $packaging->id, 'name' => 'Bubble Wrap Roll 50m', 'sku' => 'PK-0002', 'min_stock' => 5],
            ['category_id' => $packaging->id, 'name' => 'Cardboard Box Large', 'sku' => 'PK-0003', 'min_stock' => 50],
            ['category_id' => $packaging->id, 'name' => 'Packing Tape 50m (box 6)', 'sku' => 'PK-0004', 'min_stock' => 15],
            // Tools & Equipment (3)
            ['category_id' => $tools->id, 'name' => 'Screwdriver Set', 'sku' => 'TL-0001', 'min_stock' => 5],
            ['category_id' => $tools->id, 'name' => 'Cordless Drill', 'sku' => 'TL-0002', 'min_stock' => 3],
            ['category_id' => $tools->id, 'name' => 'Pallet Jack 2500kg', 'sku' => 'TL-0003', 'min_stock' => 1],
            // Safety (3)
            ['category_id' => $safety->id, 'name' => 'Safety Gloves L', 'sku' => 'SF-0001', 'min_stock' => 25],
            ['category_id' => $safety->id, 'name' => 'Safety Helmet', 'sku' => 'SF-0002', 'min_stock' => 10],
            ['category_id' => $safety->id, 'name' => 'High-Vis Vest XL', 'sku' => 'SF-0003', 'min_stock' => 15],
        ];

        $imageDir = database_path('seeders/product-images');

        foreach ($products as $product) {
            // De bijhorende demo-tegel uit de repo naar de public-disk kopiëren, zodat
            // migrate:fresh --seed de productafbeeldingen reproduceert. Ontbreekt het
            // bestand, dan blijft image_path leeg (de UI toont dan een placeholder).
            $imagePath = null;
            $source = $imageDir.'/'.$product['sku'].'.png';

            if (is_file($source)) {
                Storage::disk('public')->putFileAs('products', new File($source), $product['sku'].'.png');
                $imagePath = 'products/'.$product['sku'].'.png';
            }

            Product::create(array_merge($product, ['description' => null, 'image_path' => $imagePath]));
        }
    }
}
