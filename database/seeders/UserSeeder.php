<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Maakt de twee vaste demo-accounts (admin@waretrack.test en
 * worker@waretrack.test, wachtwoord "password", e-mail vooraf geverifieerd)
 * plus drie willekeurige extra gebruikers voor realisme in de lijsten.
 */
class UserSeeder extends Seeder
{
    /**
     * Maakt de 2 vaste demo-accounts (admin@waretrack.test en worker@waretrack.test,
     * wachtwoord "password") plus 3 willekeurige magazijniers via de factory — 5 users totaal.
     * De vaste e-mailadressen worden door DeliverySeeder en de jury-demo gebruikt om in te loggen.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@waretrack.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Jan Janssen',
            'email' => 'worker@waretrack.test',
            'password' => Hash::make('password'),
            'role' => UserRole::WarehouseWorker,
            'email_verified_at' => now(),
        ]);

        User::factory(3)->create();
    }
}
