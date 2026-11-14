<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'area_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(12),
            'closes_at' => today()->addDays(14),
            'is_active' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'is_active' => true,
            'closes_at' => today()->subDay(),
        ]);
    }
}
