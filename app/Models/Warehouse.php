<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fysiek magazijn: de top van de hiërarchie magazijn -> locatie -> stock.
 *
 * Voorraad hangt nooit rechtstreeks aan een magazijn maar altijd aan een
 * locatie erbinnen. Gebruikt soft deletes zodat een gesloten magazijn uit de
 * UI verdwijnt zonder de gekoppelde locaties en historiek te breken.
 */
class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'location', 'description'];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
