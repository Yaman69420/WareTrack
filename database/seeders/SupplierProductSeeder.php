<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierProductSeeder extends Seeder
{
    public function run(): void
    {
        $techSupply  = Supplier::where('name', 'TechSupply BV')->first();
        $officePro   = Supplier::where('name', 'OfficePro NV')->first();
        $packMasters = Supplier::where('name', 'PackMasters')->first();
        $safetyFirst = Supplier::where('name', 'SafetyFirst NV')->first();

        // TechSupply BV → Electronics + Tools & Equipment
        $techSupply->products()->sync(
            Product::whereIn('sku', ['EL-0001', 'EL-0002', 'EL-0003', 'EL-0004', 'EL-0005', 'TL-0001', 'TL-0002', 'TL-0003'])->pluck('id')
        );

        // OfficePro NV → Office Supplies
        $officePro->products()->sync(
            Product::whereIn('sku', ['OF-0001', 'OF-0002', 'OF-0003', 'OF-0004', 'OF-0005'])->pluck('id')
        );

        // PackMasters → Packaging
        $packMasters->products()->sync(
            Product::whereIn('sku', ['PK-0001', 'PK-0002', 'PK-0003', 'PK-0004'])->pluck('id')
        );

        // SafetyFirst NV → Safety
        $safetyFirst->products()->sync(
            Product::whereIn('sku', ['SF-0001', 'SF-0002', 'SF-0003'])->pluck('id')
        );
    }
}
