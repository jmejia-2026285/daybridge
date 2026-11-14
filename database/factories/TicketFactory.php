<?php

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\Equipment;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'assigned_to' => null,
            'folio' => null,
            'description' => fake()->sentence(),
            'status' => ServiceStatus::Pending,
            'opened_at' => now(),
            'completed_at' => null,
            'printed_at' => null,
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
            'completed_at' => now(),
        ]);
    }
}
