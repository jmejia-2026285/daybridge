<?php

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'color' => fake()->randomElement(['#0F3D3E', '#7C2D2D', '#3B4A57', '#A06B12', '#4F6B2E']),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
