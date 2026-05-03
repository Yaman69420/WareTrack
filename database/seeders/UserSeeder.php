<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
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
