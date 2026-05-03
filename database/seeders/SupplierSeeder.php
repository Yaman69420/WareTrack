<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'TechSupply BV',
                'email' => 'orders@techsupply.be',
                'phone' => '+32 9 123 45 67',
                'address' => 'Industrieweg 5, 9000 Gent',
            ],
            [
                'name' => 'OfficePro NV',
                'email' => 'info@officepro.be',
                'phone' => '+32 2 234 56 78',
                'address' => 'Kantoorstraat 12, 1000 Brussel',
            ],
            [
                'name' => 'PackMasters',
                'email' => 'sales@packmasters.be',
                'phone' => null,
                'address' => null,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
