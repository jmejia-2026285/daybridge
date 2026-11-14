<?php

namespace Database\Seeders;

use App\Models\BirthdayWish;
use App\Models\User;
use Illuminate\Database\Seeder;

class BirthdayWishSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            '¡Que tengas un día enorme!',
            'Gracias por tanto. ¡Feliz vuelta al sol!',
            'Un abrazo grande de todo el equipo.',
            'Hoy toca celebrarte. ¡Disfruta tu día!',
        ];

        foreach (User::celebratingToday() as $recipient) {
            $senders = User::query()
                ->whereKeyNot($recipient->id)
                ->orderBy('id')
                ->take(3)
                ->get();

            foreach ($senders as $index => $sender) {
                BirthdayWish::query()->firstOrCreate([
                    'sender_id' => $sender->id,
                    'recipient_id' => $recipient->id,
                    'year' => now()->year,
                ], [
                    'message' => $messages[$index % count($messages)],
                ]);
            }
        }
    }
}
