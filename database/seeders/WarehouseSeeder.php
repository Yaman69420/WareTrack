<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Maakt de 3 demo-magazijnen (A Gent, B Brussel, C Antwerpen koelopslag)
 * met samen 12 locaties, elk met eigen codeprefix.
 */
class WarehouseSeeder extends Seeder
{
    /**
     * Maakt 3 magazijnen (Gent, Brussel, Antwerpen) met samen 12 locaties: 5 gangen in A,
     * 3 secties in B en 4 zones in C. De locatiecodes liggen vast voor de andere seeders.
     */
    public function run(): void
    {
        $warehouseA = Warehouse::create([
            'name' => 'Warehouse A',
            'location' => 'Gent',
            'description' => 'Main warehouse',
        ]);

        $warehouseB = Warehouse::create([
            'name' => 'Warehouse B',
            'location' => 'Brussel',
            'description' => 'Secondary warehouse',
        ]);

        foreach (['A1', 'A2', 'A3', 'B1', 'B2'] as $code) {
            Location::create([
                'warehouse_id' => $warehouseA->id,
                'code' => $code,
                'name' => 'Aisle '.$code,
            ]);
        }

        foreach (['AA', 'AB', 'AC'] as $code) {
            Location::create([
                'warehouse_id' => $warehouseB->id,
                'code' => $code,
                'name' => 'Section '.$code,
            ]);
        }

        $warehouseC = Warehouse::create([
            'name' => 'Warehouse C',
            'location' => 'Antwerpen',
            'description' => 'Cold storage facility',
        ]);

        foreach (['C1', 'C2', 'C3', 'C4'] as $code) {
            Location::create([
                'warehouse_id' => $warehouseC->id,
                'code' => $code,
                'name' => 'Zone '.$code,
            ]);
        }
    }
}
