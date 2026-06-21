<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audittrail van de voorraad: één onveranderlijk record per stockmutatie.
 *
 * Wordt uitsluitend aangemaakt door StockService, binnen dezelfde transactie
 * als de stock-update zelf — zo kan een beweging nooit ontbreken of dubbel
 * geboekt zijn. Records worden nooit gewijzigd of verwijderd (geen soft
 * deletes nodig): de geschiedenis moet exact reconstrueerbaar blijven.
 *
 * location_id geldt voor incoming/outgoing/correction; bij een transfer
 * worden from_location_id en to_location_id gebruikt.
 */
class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'location_id',
        'from_location_id',
        'to_location_id',
        'user_id',
        'type',
        'quantity',
        'reference',
        'notes',
    ];

    /** Attribuutcasts: type als StockMovementType-enum, quantity als integer. */
    protected function casts(): array
    {
        return [
            // Enum-cast: type is altijd een StockMovementType-case (incoming/outgoing/
            // transfer/correction); een ongeldige waarde gooit meteen een ValueError.
            'type' => StockMovementType::class,
            'quantity' => 'integer',
        ];
    }

    /** Het product waarop deze beweging betrekking heeft. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** De locatie van de beweging bij incoming, outgoing en correction. */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // Twee extra relaties naar hetzelfde Location-model (voor transfers): de foreign
    // key moet expliciet, anders zou Eloquent voor beide 'location_id' afleiden.
    /** De bronlocatie waar de goederen vertrokken bij een transfer. */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /** De doellocatie waar de goederen aankwamen bij een transfer. */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /** De gebruiker die de beweging registreerde — essentieel voor het auditspoor. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
