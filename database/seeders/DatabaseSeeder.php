<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Maakt de app demo-klaar na `php artisan migrate:fresh --seed`.
 *
 * De volgorde hieronder is een afhankelijkheidsketen en mag niet wijzigen:
 * elke seeder verwijst naar records van zijn voorgangers. SupplierProductSeeder
 * moet bv. NA ProductSeeder draaien — de pivot synct naar product-ID's die dan
 * pas bestaan.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            WarehouseSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,       // producten verwijzen naar categorieën
            SupplierProductSeeder::class, // pivot verwijst naar leveranciers én producten
            StockSeeder::class,         // stock verwijst naar producten én locaties
            DeliverySeeder::class,      // leveringen verwijzen naar leveranciers en producten
        ]);
    }
}
