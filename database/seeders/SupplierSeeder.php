<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::create([
            'name'    => 'TechSupply BV',
            'email'   => 'orders@techsupply.be',
            'phone'   => '+32 9 123 45 67',
            'address' => 'Industrieweg 5, 9000 Gent',
        ]);

        Supplier::create([
            'name'    => 'OfficePro NV',
            'email'   => 'info@officepro.be',
            'phone'   => '+32 2 234 56 78',
            'address' => 'Kantoorstraat 12, 1000 Brussel',
        ]);

        Supplier::create([
            'name'    => 'PackMasters',
            'email'   => 'sales@packmasters.be',
            'phone'   => null,
            'address' => null,
        ]);

        Supplier::create([
            'name'    => 'SafetyFirst NV',
            'email'   => 'info@safetyfirst.be',
            'phone'   => '+32 3 456 78 90',
            'address' => 'Veiligheidsstraat 8, 2000 Antwerpen',
        ]);
    }
}
