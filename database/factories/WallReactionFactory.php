<?php

namespace Database\Factories;

use App\Enums\WallReactionType;
use App\Models\User;
use App\Models\WallPost;
use App\Models\WallReaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WallReaction>
 */
class WallReactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wall_post_id' => WallPost::factory(),
            'user_id' => User::factory(),
            'type' => WallReactionType::Like,
        ];
    }

    public function clap(): static
    {
        return $this->state(fn (): array => [
            'type' => WallReactionType::Clap,
        ]);
    }

    public function celebrate(): static
    {
        return $this->state(fn (): array => [
            'type' => WallReactionType::Celebrate,
        ]);
    }
}
