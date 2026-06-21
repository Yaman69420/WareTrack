<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Delivery> */
class DeliveryFactory extends Factory
{
    /**
     * Recept voor een levering in de neutrale beginstaat: status Pending, nog geen ontvangstdatum,
     * met optionele PO-referentie. Leverancier en aanmelder ontstaan via geneste factories.
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory(),
            'status' => DeliveryStatus::Pending,
            'reference' => fake()->optional()->bothify('PO-####'),
            'notes' => null,
            'received_at' => null,
        ];
    }
}
