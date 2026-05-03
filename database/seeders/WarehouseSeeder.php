<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
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
                'name' => 'Aisle ' . $code,
            ]);
        }

        foreach (['AA', 'AB', 'AC'] as $code) {
            Location::create([
                'warehouse_id' => $warehouseB->id,
                'code' => $code,
                'name' => 'Section ' . $code,
            ]);
        }
    }
}
