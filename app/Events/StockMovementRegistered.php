<?php

namespace App\Events;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dat StockService dispatcht na elke geslaagde stockmutatie.
 *
 * Ontkoppelt de mutatie van haar gevolgen: de service hoeft niet te weten wie reageert,
 * listeners (zoals de low-stock-check) haken hier los op in. SerializesModels zet de
 * modellen als id op de queue; de worker haalt ze daar vers mee op.
 */
class StockMovementRegistered
{
    use Dispatchable, SerializesModels;

    /**
     * Payload van het event: het gewijzigde product en de zojuist geboekte beweging.
     * Readonly promoted properties: listeners kunnen de payload lezen maar nooit aanpassen.
     */
    public function __construct(
        public readonly Product $product,
        public readonly StockMovement $movement,
    ) {}
}
