<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Seeder;

/**
 * Vult de stock-tabel met beginvoorraad én registreert via product_location
 * welke producten op welke locaties mogen staan.
 */
class StockSeeder extends Seeder
{
    /**
     * Geeft elk van de 20 producten voorraad op 1 à 3 willekeurige locaties, met aantallen die
     * passen bij de categorie. Forceert daarna EL-0001 (2 stuks) en SF-0002 (1 stuk) onder hun
     * min_stock zodat de low-stock-melding altijd iets toont in de demo.
     */
    public function run(): void
    {
        $products = Product::all();
        $locations = Location::all();

        foreach ($products as $product) {
            $assignedLocations = $locations->random(rand(1, 3));

            foreach ($assignedLocations as $location) {
                // Realistische ranges per SKU-prefix: kantoor/verpakking in bulk, rest kleinschalig;
                // PK en default kunnen 0 zijn, zodat ook lege voorraadrijen voorkomen
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

                // Pivot product↔locatie mee bijhouden zonder eerder toegekende locaties te wissen
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
