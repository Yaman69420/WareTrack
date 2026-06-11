<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kop van een inkomende levering van een leverancier.
 *
 * De eigenlijke regels (welk product, hoeveel) zitten in DeliveryItem; dit
 * model bewaart de context: leverancier, ontvanger (user), status en moment
 * van ontvangst. Soft deletes houden geannuleerde leveringen traceerbaar.
 */
class Delivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'status',
        'reference',
        'notes',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            // Enum-cast: status is overal in de code een DeliveryStatus-case (pending/
            // partial/received), nooit een losse string. Tikfouten breken zo al bij PHP zelf.
            'status' => DeliveryStatus::class,
            'received_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }
}
