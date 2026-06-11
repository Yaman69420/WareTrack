<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * Centrale serviceprovider van de applicatie.
 *
 * Stelt applicatiebrede standaarden in: immutabele datums, een slot op
 * destructieve databankcommando's in productie en een strikt wachtwoordbeleid.
 * Eigen bindings zijn er niet — register() blijft daarom bewust leeg.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        // Immutabele datums: bewerkingen geven een nieuwe instantie terug, zodat een
        // datum die op meerdere plaatsen circuleert nooit stiekem mee verandert.
        Date::use(CarbonImmutable::class);

        // Blokkeert wipe/fresh-achtige Artisan-commando's zodra de app in productie
        // draait — één verkeerd `migrate:fresh` mag de live stockdata niet wissen.
        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Strikt wachtwoordbeleid (12+ tekens, mix, niet gelekt) enkel in productie;
        // `null` valt lokaal terug op de soepele standaard, handig bij testaccounts.
        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
