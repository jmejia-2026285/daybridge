<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WallPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WallPost>
 */
class WallPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'is_pinned' => false,
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn (): array => [
            'is_pinned' => true,
        ]);
    }
}
