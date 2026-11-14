<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WallComment;
use App\Models\WallPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WallComment>
 */
class WallCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wall_post_id' => WallPost::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
