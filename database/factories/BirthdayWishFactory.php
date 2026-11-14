<?php

namespace Database\Factories;

use App\Models\BirthdayWish;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BirthdayWish>
 */
class BirthdayWishFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'year' => now()->year,
            'message' => '¡Feliz cumpleaños!',
        ];
    }
}
