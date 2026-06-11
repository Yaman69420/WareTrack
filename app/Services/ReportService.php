<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Leesrapportages over voorraad en bewegingen.
 *
 * Bevat uitsluitend read-only queries en muteert dus nooit — alle schrijfacties lopen
 * via StockService. De eager loads in elke methode laden exact de relaties die de
 * rapportviews tonen, zodat er geen N+1-queries ontstaan.
 */
class ReportService
{
    /**
     * Producten die onder hun ingestelde minimumvoorraad zitten.
     *
     * De where op min_stock filtert in SQL alvast alle producten zonder bewaking weg;
     * de eigenlijke vergelijking gebeurt daarna in PHP omdat isBelowMinStock() de som
     * over alle locaties nodig heeft.
     */
    public function getLowStockProducts(): Collection
    {
        return Product::with(['category', 'stock.location.warehouse'])
            ->where('min_stock', '>', 0)
            ->get()
            ->filter(fn ($p) => $p->isBelowMinStock())
            ->sortBy(fn ($p) => $p->totalStock() - $p->min_stock); // grootste tekort eerst
    }

    /**
     * Actuele voorraad per locatie, optioneel beperkt tot één magazijn.
     *
     * Nulregels worden bewust weggefilterd: een locatie waar een product ooit lag
     * maar nu niet meer, hoort niet thuis in dit overzicht.
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
            // Sorteren gebeurt in PHP omdat de sorteersleutels (magazijnnaam en
            // locatiecode) in gerelateerde tabellen zitten, niet op de stock-rij zelf.
            ->sortBy([
                fn ($a, $b) => strcmp($a->location->warehouse->name, $b->location->warehouse->name),
                fn ($a, $b) => strcmp($a->location->code, $b->location->code),
            ]);
    }

    /**
     * Stockbewegingen binnen een periode, optioneel gefilterd op type.
     *
     * startOfDay/endOfDay zorgen dat de einddatum volledig meetelt — een whereBetween
     * op rauwe datums zou alles van de laatste dag (na middernacht) missen.
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
