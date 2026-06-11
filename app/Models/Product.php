<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Centrale entiteit van het domein: het artikel dat in het magazijn ligt.
 *
 * Een product hoort bij één categorie, kan op meerdere locaties liggen
 * (stock per locatie) en kan door meerdere leveranciers geleverd worden.
 * min_stock is de drempel voor de lage-voorraadwaarschuwing. Soft deletes:
 * een geschrapt product blijft leesbaar in oude bewegingen en leveringen.
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'description',
        'image_path',
        'min_stock',
    ];

    protected function casts(): array
    {
        return [
            'min_stock' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function locations(): BelongsToMany
    {
        // Tabelnaam expliciet: de Laravel-conventie zou alfabetisch 'location_product'
        // opleveren, terwijl de migratie bewust 'product_location' aanmaakte.
        return $this->belongsToMany(Location::class, 'product_location');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    public function suppliers(): BelongsToMany
    {
        // Tabelnaam expliciet: Laravel zou alfabetisch 'product_supplier' genereren,
        // de migratie heet 'supplier_product'. Zonder deze parameter faalt de relatie.
        return $this->belongsToMany(Supplier::class, 'supplier_product');
    }

    /**
     * Totale voorraad van dit product, gesommeerd over alle locaties.
     *
     * Sommeert de relatie als collection (niet via een aparte query) zodat een
     * eager-geladen 'stock' wordt hergebruikt en lijstweergaves geen N+1 krijgen.
     */
    public function totalStock(): int
    {
        return $this->stock->sum('quantity');
    }

    /**
     * Drempelcheck voor de lage-voorraadwaarschuwing.
     *
     * Bewust strikt kleiner dan: exact op min_stock zitten is nog net voldoende,
     * pas eronder verschijnt het product in de waarschuwingslijst.
     */
    public function isBelowMinStock(): bool
    {
        return $this->totalStock() < $this->min_stock;
    }
}
