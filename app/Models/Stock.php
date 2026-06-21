<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Actuele voorraadstand: één rij per unieke combinatie product + locatie.
 *
 * Dit model is puur de huidige toestand; het 'waarom' van elke wijziging zit
 * in StockMovement. Mutaties lopen altijd via StockService (met lock en
 * transactie), nooit rechtstreeks vanuit een component. Geen soft deletes:
 * een rij zonder voorraad heeft geen historische waarde, de historiek zit
 * volledig in de bewegingen.
 */
class Stock extends Model
{
    use HasFactory;

    // Afwijkend van de conventie: Laravel zou de meervoudsvorm 'stocks' verwachten,
    // maar 'stock' is als onttelbaar Engels woord correct enkelvoud in het schema.
    protected $table = 'stock';

    protected $fillable = ['product_id', 'location_id', 'quantity'];

    /** Attribuutcast: quantity als integer, zodat optellen en vergelijken nooit op strings gebeurt. */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /** Het product waarvan deze rij de voorraad bijhoudt. */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** De locatie waar deze voorraad fysiek ligt. */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
