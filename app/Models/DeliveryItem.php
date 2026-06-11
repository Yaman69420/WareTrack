<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén regel van een levering: een product, een doellocatie en de aantallen.
 *
 * Het verschil tussen quantity_ordered en quantity_received bepaalt of de
 * levering volledig of gedeeltelijk binnenkwam (status op Delivery). Geen
 * soft deletes: een regel bestaat enkel binnen zijn (soft-deletable) levering.
 */
class DeliveryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'product_id',
        'location_id',
        'quantity_ordered',
        'quantity_received',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'integer',
            'quantity_received' => 'integer',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
