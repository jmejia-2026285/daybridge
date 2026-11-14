<?php

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => null,
            'type' => fake()->randomElement(['Laptop', 'Desktop', 'Impresora', 'Monitor', 'Tablet']),
            'brand' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Apple', 'Epson']),
            'model' => strtoupper(fake()->bothify('??-####')),
            'serial' => strtoupper(fake()->bothify('SN-####-????')),
            'client_name' => fake()->name(),
            'client_phone' => fake()->numerify('####-####'),
            'client_email' => fake()->safeEmail(),
            'issue' => fake()->sentence(),
            'diagnosis' => null,
            'accessories' => fake()->randomElement(['Cargador', 'Cargador y funda', 'Sin accesorios', 'Cable de poder']),
            'notes' => null,
            'status' => ServiceStatus::Pending,
            'received_by' => null,
            'received_at' => now(),
            'estimated_delivery_date' => today()->addDays(3),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => ServiceStatus::InProgress,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => ServiceStatus::Completed,
        ]);
    }
}
