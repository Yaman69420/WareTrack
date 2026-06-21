<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Leverancier: de externe partij waarvan leveringen binnenkomen.
 *
 * Gekoppeld aan producten via een many-to-many (een leverancier levert
 * meerdere producten, een product kan meerdere leveranciers hebben).
 * Soft deletes: oude leveringen blijven hun leverancier tonen, ook nadat
 * de samenwerking is stopgezet.
 */
class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'address', 'notes'];

    /** Alle leveringen die van deze leverancier binnenkwamen. */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /** De producten die deze leverancier kan leveren (many-to-many via supplier_product). */
    public function products(): BelongsToMany
    {
        // Tabelnaam expliciet: Laravel zou alfabetisch 'product_supplier' genereren,
        // de migratie heet 'supplier_product'. Zonder deze parameter faalt de relatie.
        return $this->belongsToMany(Product::class, 'supplier_product');
    }
}
