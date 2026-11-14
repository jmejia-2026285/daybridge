<?php

namespace Database\Seeders;

use App\Enums\WallReactionType;
use App\Models\User;
use App\Models\WallComment;
use App\Models\WallPost;
use App\Models\WallReaction;
use Illuminate\Database\Seeder;

class WallSeeder extends Seeder
{
    public function run(): void
    {
        $people = User::query()->get()->keyBy('email');

        if ($people->isEmpty()) {
            return;
        }

        $author = $people->get('test@example.com') ?? $people->first();

        $posts = [
            [
                'author' => $author,
                'body' => 'Arrancamos la semana con el taller de inducción para las nuevas incorporaciones. Si ves caras nuevas por el pasillo, dales la bienvenida. 👋',
                'pinned' => true,
                'reactions' => ['maria.castillo@daybridge.test' => WallReactionType::Clap, 'jose.barrios@daybridge.test' => WallReactionType::Like],
                'comments' => [
                    ['karla.solis@daybridge.test', '¡Bienvenidos! Cualquier duda del día a día, aquí estamos.'],
                    ['luis.arevalo@daybridge.test', 'Ya les compartí el acceso a los documentos de Finanzas.'],
                ],
            ],
            [
                'author' => $people->get('gabriela.monzon@daybridge.test') ?? $author,
                'body' => 'Pequeño recordatorio: la sala de capacitación queda libre los viernes por la tarde. Si necesitan un espacio para una sesión de equipo, pueden reservarla desde el calendario.',
                'pinned' => false,
                'reactions' => ['test@example.com' => WallReactionType::Like],
                'comments' => [
                    ['andrea.figueroa@daybridge.test', 'Perfecto para la revisión de campaña de esta semana.'],
                ],
            ],
            [
                'author' => $people->get('ricardo.quinonez@daybridge.test') ?? $author,
                'body' => 'Gracias al equipo de Operaciones por cubrir el cierre de inventario con tan poco aviso. El conteo salió limpio y sin retrasos.',
                'pinned' => false,
                'reactions' => [
                    'test@example.com' => WallReactionType::Celebrate,
                    'sofia.marroquin@daybridge.test' => WallReactionType::Clap,
                    'estuardo.lemus@daybridge.test' => WallReactionType::Clap,
                ],
                'comments' => [
                    ['estuardo.lemus@daybridge.test', 'Se agradece el reconocimiento. Fue un gran esfuerzo compartido.'],
                ],
            ],
        ];

        foreach ($posts as $entry) {
            $post = WallPost::query()->firstOrCreate(
                [
                    'user_id' => $entry['author']->id,
                    'body' => $entry['body'],
                ],
                [
                    'is_pinned' => $entry['pinned'],
                ],
            );

            foreach ($entry['reactions'] as $email => $type) {
                $reactor = $people->get($email);

                if ($reactor === null) {
                    continue;
                }

                WallReaction::query()->firstOrCreate(
                    [
                        'wall_post_id' => $post->id,
                        'user_id' => $reactor->id,
                    ],
                    [
                        'type' => $type,
                    ],
                );
            }

            foreach ($entry['comments'] as [$email, $body]) {
                $commenter = $people->get($email);

                if ($commenter === null) {
                    continue;
                }

                WallComment::query()->firstOrCreate([
                    'wall_post_id' => $post->id,
                    'user_id' => $commenter->id,
                    'body' => $body,
                ]);
            }
        }
    }
}
