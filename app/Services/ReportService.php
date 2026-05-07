<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Products below their minimum stock level.
     */
    public function getLowStockProducts(): Collection
    {
        return Product::with(['category', 'stock.location.warehouse'])
            ->where('min_stock', '>', 0)
            ->get()
            ->filter(fn ($p) => $p->isBelowMinStock())
            ->sortBy(fn ($p) => $p->totalStock() - $p->min_stock); // worst first
    }

    /**
     * Current stock per location, optionally filtered by warehouse.
     */
    public function getStockPerLocation(?int $warehouseId = null): Collection
    {
        return Stock::query()
            ->with(['product.category', 'location.warehouse'])
            ->where('quantity', '>', 0)
            ->when($warehouseId, fn ($q) => $q->whereHas(
                'location', fn ($lq) => $lq->where('warehouse_id', $warehouseId)
            ))
            ->get()
            ->sortBy([
                fn ($a, $b) => strcmp($a->location->warehouse->name, $b->location->warehouse->name),
                fn ($a, $b) => strcmp($a->location->code, $b->location->code),
            ]);
    }

    /**
     * Stock movements within a date range, optionally filtered by type.
     */
    public function getMovementsForPeriod(
        CarbonInterface $from,
        CarbonInterface $to,
        ?string $type = null
    ): Collection {
        return StockMovement::query()
            ->with(['product', 'location.warehouse', 'fromLocation.warehouse', 'toLocation.warehouse', 'user'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest()
            ->get();
    }
}
