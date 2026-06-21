<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    /**
     * Recept voor een leverancier: bedrijfsnaam met optionele contactgegevens — zoals in
     * de praktijk zijn e-mail, telefoon en adres niet altijd gekend (nullable kolommen).
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'notes' => null,
        ];
    }
}
