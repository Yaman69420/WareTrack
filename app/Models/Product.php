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

    /** Attribuutcast: min_stock als integer, zodat de drempelvergelijking numeriek klopt. */
    protected function casts(): array
    {
        return [
            'min_stock' => 'integer',
        ];
    }

    /** De categorie waartoe dit product behoort. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** De locaties waaraan dit product is toegewezen (many-to-many via product_location). */
    public function locations(): BelongsToMany
    {
        // Tabelnaam expliciet: de Laravel-conventie zou alfabetisch 'location_product'
        // opleveren, terwijl de migratie bewust 'product_location' aanmaakte.
        return $this->belongsToMany(Location::class, 'product_location');
    }

    /** De actuele voorraadrijen van dit product, één per locatie waar het ligt. */
    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /** De volledige bewegingshistoriek van dit product. */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** De leveringsregels waarin dit product besteld of ontvangen werd. */
    public function deliveryItems(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /** De leveranciers die dit product kunnen leveren (many-to-many via supplier_product). */
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
