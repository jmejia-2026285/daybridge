<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'user_id' => User::factory(),
            'title' => Str::title(fake()->sentence(6)),
            'highlight' => null,
            'excerpt' => Str::limit(fake()->paragraph(), 280),
            'body' => fake()->paragraphs(3, true),
            'type' => Post::TYPE_NEWS,
            'is_featured' => false,
            'is_urgent' => false,
            'published_at' => now(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (): array => [
            'is_featured' => true,
        ]);
    }

    public function notice(): static
    {
        return $this->state(fn (): array => [
            'type' => Post::TYPE_NOTICE,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (): array => [
            'type' => Post::TYPE_NOTICE,
            'is_urgent' => true,
        ]);
    }

    public function recognition(): static
    {
        return $this->state(fn (): array => [
            'type' => Post::TYPE_RECOGNITION,
        ]);
    }
}
