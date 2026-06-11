<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Models\Concerns\CausesActivity;

/**
 * Gebruiker van het systeem, met een rol (admin of magazijnmedewerker).
 *
 * Authenticatie loopt via Fortify (incl. two-factor). CausesActivity (Spatie
 * activitylog) koppelt elke gelogde actie aan deze gebruiker, zodat het
 * auditspoor toont wíe iets deed. Autorisatie steunt op de role-enum, niet
 * op een volwaardig permissiepakket: twee vaste rollen volstaan hier.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use CausesActivity, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // 'hashed' laat Laravel zelf bcrypt toepassen bij het toekennen van een
            // wachtwoord; er kan dus nooit per ongeluk plaintext in de database belanden.
            'password' => 'hashed',
            // Enum-cast: rolvergelijkingen gebeuren tegen UserRole-cases, niet tegen
            // losse strings — een typfout in een rolnaam valt zo direct op.
            'role' => UserRole::class,
        ];
    }

    // Rolchecks als benoemde helpers: de enum-vergelijking staat op één plek,
    // policies en views lezen $user->isAdmin() i.p.v. overal de enum te importeren.
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isWarehouseWorker(): bool
    {
        return $this->role === UserRole::WarehouseWorker;
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Initialen voor de avatar in de UI.
     *
     * Bewust beperkt tot de eerste twee naamdelen: bij lange namen ("Jan van
     * der Berg") blijft het resultaat zo altijd twee tekens breed.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
