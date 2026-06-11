<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Concrete opslagplek (rek/schap) binnen een magazijn.
 *
 * Dit is het niveau waarop voorraad effectief wordt bijgehouden: stock en
 * stockbewegingen verwijzen naar een locatie, niet naar het magazijn zelf.
 * Soft deletes: een opgeheven locatie blijft zichtbaar in oude bewegingen.
 */
class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['warehouse_id', 'code', 'name'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function products(): BelongsToMany
    {
        // Tabelnaam expliciet: de Laravel-conventie zou alfabetisch 'location_product'
        // opleveren, terwijl de migratie bewust 'product_location' aanmaakte.
        return $this->belongsToMany(Product::class, 'product_location');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
