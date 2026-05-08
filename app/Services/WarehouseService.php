<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseService
{
    /**
     * Create a new warehouse with optional initial locations.
     *
     * @param  array<int, array{code: string, name: string|null}>  $locations
     */
    public function createWarehouse(string $name, string $location, ?string $description, array $locations = []): Warehouse
    {
        return DB::transaction(function () use ($name, $location, $description, $locations) {
            $warehouse = Warehouse::create([
                'name' => $name,
                'location' => $location,
                'description' => $description,
            ]);

            foreach ($locations as $loc) {
                $warehouse->locations()->create([
                    'code' => $loc['code'],
                    'name' => $loc['name'] ?? null,
                ]);
            }

            Log::info('Warehouse created', ['warehouse_id' => $warehouse->id, 'name' => $warehouse->name]);

            return $warehouse;
        });
    }

    /**
     * Add a location to an existing warehouse.
     */
    public function addLocation(Warehouse $warehouse, string $code, ?string $name = null): Location
    {
        $location = $warehouse->locations()->create([
            'code' => $code,
            'name' => $name,
        ]);

        Log::info('Location added', [
            'warehouse_id' => $warehouse->id,
            'location_id' => $location->id,
            'code' => $code,
        ]);

        return $location;
    }

    /**
     * Soft-delete a warehouse (cascades to locations via model events).
     * Only allowed when all locations have zero stock.
     */
    public function deleteWarehouse(Warehouse $warehouse): void
    {
        $hasStock = $warehouse->locations()
            ->whereHas('stock', fn ($q) => $q->where('quantity', '>', 0))
            ->exists();

        if ($hasStock) {
            throw new \RuntimeException("Cannot delete warehouse '{$warehouse->name}': one or more locations still have stock.");
        }

        DB::transaction(function () use ($warehouse) {
            $warehouse->locations()->each(fn (Location $l) => $l->delete());
            $warehouse->delete();
        });

        Log::info('Warehouse deleted', ['warehouse_id' => $warehouse->id, 'name' => $warehouse->name]);
    }

    /**
     * Return total stock quantity across all locations in a warehouse.
     */
    public function totalStock(Warehouse $warehouse): int
    {
        return $warehouse->locations()
            ->withSum('stock', 'quantity')
            ->get()
            ->sum('stock_sum_quantity');
    }
}
