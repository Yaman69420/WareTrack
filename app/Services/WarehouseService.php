<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service voor het beheer van magazijnen en hun locaties (de structuur, niet de stock).
 *
 * Stockmutaties zitten bewust apart in StockService; deze klasse bewaakt de regels rond
 * de magazijnstructuur zelf, zoals het verbod om een magazijn met resterende voorraad
 * te verwijderen.
 */
class WarehouseService
{
    /**
     * Maakt een magazijn aan, optioneel meteen met zijn eerste locaties.
     *
     * In één transactie: een magazijn mag nooit half (zonder zijn locaties) in de
     * database belanden als er bij het aanmaken iets misloopt.
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
     * Voegt één locatie toe aan een bestaand magazijn.
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
     * Soft-delete van een magazijn, inclusief al zijn locaties.
     *
     * Bedrijfsregel: verwijderen kan alleen als geen enkele locatie nog voorraad heeft.
     * Anders zou stock "verdwijnen" zonder corrigerende movement in de audit trail.
     */
    public function deleteWarehouse(Warehouse $warehouse): void
    {
        // Eén exists-query over alle locaties volstaat; we hoeven niet te weten wélke
        // locatie nog stock heeft, enkel óf er nog ergens stock ligt.
        $hasStock = $warehouse->locations()
            ->whereHas('stock', fn ($q) => $q->where('quantity', '>', 0))
            ->exists();

        if ($hasStock) {
            throw new \RuntimeException("Cannot delete warehouse '{$warehouse->name}': one or more locations still have stock.");
        }

        DB::transaction(function () use ($warehouse) {
            // Locaties één voor één via het model verwijderen, niet met een bulk-query:
            // alleen dan vuren de model-events en gebeurt de soft-delete per locatie correct.
            $warehouse->locations()->each(fn (Location $l) => $l->delete());
            $warehouse->delete();
        });

        Log::info('Warehouse deleted', ['warehouse_id' => $warehouse->id, 'name' => $warehouse->name]);
    }

    /**
     * Totale voorraad over alle locaties van het magazijn.
     *
     * withSum laat de databank per locatie optellen in plaats van alle stock-rijen
     * naar PHP te halen en daar te sommeren.
     */
    public function totalStock(Warehouse $warehouse): int
    {
        return $warehouse->locations()
            ->withSum('stock', 'quantity')
            ->get()
            ->sum('stock_sum_quantity');
    }
}
