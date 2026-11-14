<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'technician_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'scheduled_date' => today(),
            'scheduled_time' => '09:00',
            'completed' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'completed' => true,
        ]);
    }
}
