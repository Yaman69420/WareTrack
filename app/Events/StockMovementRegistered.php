<?php

namespace App\Events;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockMovementRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly StockMovement $movement,
    ) {}
}
