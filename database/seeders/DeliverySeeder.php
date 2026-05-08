<?php

namespace Database\Seeders;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class DeliverySeeder extends Seeder
{
    public function run(StockService $stock): void
    {
        $admin = User::where('email', 'admin@waretrack.test')->first();
        $worker = User::where('email', 'worker@waretrack.test')->first();

        $techSupply = Supplier::where('name', 'TechSupply BV')->first();
        $officePro = Supplier::where('name', 'OfficePro NV')->first();
        $packMasters = Supplier::where('name', 'PackMasters')->first();

        $locationA1 = Location::where('code', 'A1')->first();
        $locationA2 = Location::where('code', 'A2')->first();
        $locationB1 = Location::where('code', 'B1')->first();
        $locationAA = Location::where('code', 'AA')->first();
        $locationC1 = Location::where('code', 'C1')->first();
        $locationC2 = Location::where('code', 'C2')->first();

        $usbHub = Product::where('sku', 'EL-0001')->first();
        $hdmi = Product::where('sku', 'EL-0002')->first();
        $mouse = Product::where('sku', 'EL-0003')->first();
        $keyboard = Product::where('sku', 'EL-0004')->first();
        $paper = Product::where('sku', 'OF-0001')->first();
        $pens = Product::where('sku', 'OF-0002')->first();
        $boxSmall = Product::where('sku', 'PK-0001')->first();
        $bubble = Product::where('sku', 'PK-0002')->first();
        $boxLarge = Product::where('sku', 'PK-0003')->first();
        $tape = Product::where('sku', 'PK-0004')->first();
        $screwdriver = Product::where('sku', 'TL-0001')->first();
        $gloves = Product::where('sku', 'SF-0001')->first();
        $helmet = Product::where('sku', 'SF-0002')->first();
        $hivis = Product::where('sku', 'SF-0003')->first();

        // --- Delivery 1: Fully received (TechSupply — electronics) ---
        $delivery1 = Delivery::create([
            'supplier_id' => $techSupply->id,
            'user_id' => $admin->id,
            'status' => DeliveryStatus::Received,
            'reference' => 'PO-2026-001',
            'notes' => 'Regular electronics restock',
            'received_at' => now()->subDays(10),
        ]);

        $items1 = [
            [$usbHub, $locationA1, 20],
            [$hdmi, $locationA1, 30],
            [$mouse, $locationA2, 25],
        ];

        foreach ($items1 as [$product, $location, $qty]) {
            DeliveryItem::create([
                'delivery_id' => $delivery1->id,
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity_ordered' => $qty,
                'quantity_received' => $qty,
            ]);

            $stock->registerIncoming($product, $location, $qty, $admin, $delivery1->reference, 'Delivery '.$delivery1->reference);
        }

        // --- Delivery 2: Fully received (OfficePro — office supplies) ---
        $delivery2 = Delivery::create([
            'supplier_id' => $officePro->id,
            'user_id' => $worker->id,
            'status' => DeliveryStatus::Received,
            'reference' => 'PO-2026-002',
            'notes' => null,
            'received_at' => now()->subDays(5),
        ]);

        $items2 = [
            [$paper, $locationB1, 100],
            [$pens, $locationB1, 60],
        ];

        foreach ($items2 as [$product, $location, $qty]) {
            DeliveryItem::create([
                'delivery_id' => $delivery2->id,
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity_ordered' => $qty,
                'quantity_received' => $qty,
            ]);

            $stock->registerIncoming($product, $location, $qty, $worker, $delivery2->reference, 'Delivery '.$delivery2->reference);
        }

        // --- Delivery 3: Partially received (PackMasters — packaging) ---
        $delivery3 = Delivery::create([
            'supplier_id' => $packMasters->id,
            'user_id' => $admin->id,
            'status' => DeliveryStatus::Partial,
            'reference' => 'PO-2026-003',
            'notes' => 'Bubble wrap delayed, boxes received',
            'received_at' => now()->subDays(2),
        ]);

        DeliveryItem::create([
            'delivery_id' => $delivery3->id,
            'product_id' => $boxSmall->id,
            'location_id' => $locationAA->id,
            'quantity_ordered' => 200,
            'quantity_received' => 200,
        ]);

        $stock->registerIncoming($boxSmall, $locationAA, 200, $admin, $delivery3->reference, 'Delivery '.$delivery3->reference);

        DeliveryItem::create([
            'delivery_id' => $delivery3->id,
            'product_id' => $bubble->id,
            'location_id' => $locationAA->id,
            'quantity_ordered' => 10,
            'quantity_received' => 0,
        ]);

        // --- Delivery 4: Pending (TechSupply — safety gear) ---
        $delivery4 = Delivery::create([
            'supplier_id' => $techSupply->id,
            'user_id' => $admin->id,
            'status' => DeliveryStatus::Pending,
            'reference' => 'PO-2026-004',
            'notes' => 'Safety restock — awaiting arrival',
            'received_at' => null,
        ]);

        DeliveryItem::create([
            'delivery_id' => $delivery4->id,
            'product_id' => $gloves->id,
            'location_id' => $locationA2->id,
            'quantity_ordered' => 50,
            'quantity_received' => 0,
        ]);

        DeliveryItem::create([
            'delivery_id' => $delivery4->id,
            'product_id' => $helmet->id,
            'location_id' => $locationA2->id,
            'quantity_ordered' => 15,
            'quantity_received' => 0,
        ]);

        // --- Delivery 5: Received (PackMasters — packaging to Warehouse C) ---
        $delivery5 = Delivery::create([
            'supplier_id' => $packMasters->id,
            'user_id' => $worker->id,
            'status' => DeliveryStatus::Received,
            'reference' => 'PO-2026-005',
            'notes' => 'Cold storage packaging restock',
            'received_at' => now()->subDay(),
        ]);

        $items5 = [
            [$boxLarge, $locationC1, 80],
            [$tape, $locationC1, 40],
            [$keyboard, $locationC2, 12],
            [$hivis, $locationC2, 30],
        ];

        foreach ($items5 as [$product, $location, $qty]) {
            DeliveryItem::create([
                'delivery_id' => $delivery5->id,
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity_ordered' => $qty,
                'quantity_received' => $qty,
            ]);

            $stock->registerIncoming($product, $location, $qty, $worker, $delivery5->reference, 'Delivery '.$delivery5->reference);
        }

        // --- Extra movements for Reports demo variety ---

        // Outgoing: screwdrivers picked from A1
        $screwdriverStock = Stock::where('product_id', $screwdriver->id)->first();
        if ($screwdriverStock && $screwdriverStock->quantity >= 3) {
            $stock->registerOutgoing(
                $screwdriver,
                Location::find($screwdriverStock->location_id),
                3,
                $worker,
                'ORD-0023',
                'Outgoing for maintenance order'
            );
        }

        // Transfer: move some HDMI cables from A1 → A2
        $hdmiStockA1 = Stock::where('product_id', $hdmi->id)
            ->where('location_id', $locationA1->id)
            ->first();

        if ($hdmiStockA1 && $hdmiStockA1->quantity >= 10) {
            $stock->transfer($hdmi, $locationA1, $locationA2, 10, $admin, 'Redistribute HDMI stock');
        }

        // Correction: safety helmet count corrected after physical count
        $helmetStock = Stock::where('product_id', $helmet->id)->first();
        if ($helmetStock) {
            $helmetLocation = Location::find($helmetStock->location_id);
            $stock->adjust($helmet, $helmetLocation, $helmetStock->quantity + 2, $admin, 'Physical count correction +2');
        }
    }
}
