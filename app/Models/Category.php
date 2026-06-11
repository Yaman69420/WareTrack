<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Productcategorie: groepeert producten voor filtering en rapportage.
 *
 * Gebruikt soft deletes: een verwijderde categorie verdwijnt uit de lijsten,
 * maar bestaande producten behouden hun category_id zodat historiek en
 * rapporten over oude data correct blijven.
 */
class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
