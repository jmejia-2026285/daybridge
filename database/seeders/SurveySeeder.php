<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyVote;
use App\Models\User;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        $people = User::query()->get()->keyBy('email');

        if ($people->isEmpty()) {
            return;
        }

        $creator = $people->get('test@example.com') ?? $people->first();

        $surveys = [
            [
                'title' => 'Clima del trimestre',
                'description' => 'Tres preguntas rápidas para entender cómo se siente el equipo antes del cierre del trimestre.',
                'closes_at' => today()->addDays(10),
                'is_active' => true,
                'questions' => [
                    [
                        'prompt' => '¿Cómo te sientes con la carga de trabajo actual?',
                        'options' => ['Muy bien', 'Bien', 'Podría mejorar', 'Me cuesta sostenerla'],
                        'voters' => ['maria.castillo@daybridge.test', 'jose.barrios@daybridge.test', 'karla.solis@daybridge.test', 'luis.arevalo@daybridge.test'],
                    ],
                    [
                        'prompt' => '¿Qué tan claro tienes lo que se espera de tu rol este trimestre?',
                        'options' => ['Muy claro', 'Claro', 'Poco claro', 'Necesito hablarlo'],
                        'voters' => ['maria.castillo@daybridge.test', 'sofia.marroquin@daybridge.test', 'estuardo.lemus@daybridge.test'],
                    ],
                    [
                        'prompt' => '¿Qué necesitas del equipo para trabajar mejor?',
                        'options' => ['Más contexto', 'Menos reuniones', 'Mejores herramientas', 'Más tiempo enfocado'],
                        'voters' => ['jose.barrios@daybridge.test', 'gabriela.monzon@daybridge.test', 'ricardo.quinonez@daybridge.test'],
                    ],
                ],
            ],
            [
                'title' => 'Próximo taller interno',
                'description' => 'Ya cerramos esta encuesta. Los resultados nos ayudan a elegir el tema del próximo taller.',
                'closes_at' => today()->subDays(2),
                'is_active' => true,
                'questions' => [
                    [
                        'prompt' => '¿Qué tema te interesa más para el próximo taller?',
                        'options' => ['Comunicación efectiva', 'Herramientas de productividad', 'Finanzas para equipos', 'Bienestar y energía'],
                        'voters' => ['maria.castillo@daybridge.test', 'karla.solis@daybridge.test', 'andrea.figueroa@daybridge.test', 'fernanda.aguilar@daybridge.test'],
                    ],
                    [
                        'prompt' => '¿En qué horario te funciona mejor asistir?',
                        'options' => ['Mañana', 'Mediodía', 'Tarde'],
                        'voters' => ['jose.barrios@daybridge.test', 'sofia.marroquin@daybridge.test', 'luis.arevalo@daybridge.test'],
                    ],
                ],
            ],
        ];

        foreach ($surveys as $entry) {
            $survey = Survey::query()->firstOrCreate(
                ['title' => $entry['title']],
                [
                    'user_id' => $creator->id,
                    'description' => $entry['description'],
                    'closes_at' => $entry['closes_at'],
                    'is_active' => $entry['is_active'],
                ],
            );

            foreach ($entry['questions'] as $questionIndex => $questionEntry) {
                $question = SurveyQuestion::query()->firstOrCreate(
                    [
                        'survey_id' => $survey->id,
                        'prompt' => $questionEntry['prompt'],
                    ],
                    [
                        'sort_order' => $questionIndex,
                    ],
                );

                $options = collect($questionEntry['options'])
                    ->map(fn (string $label, int $optionIndex): SurveyOption => SurveyOption::query()->firstOrCreate(
                        [
                            'survey_question_id' => $question->id,
                            'label' => $label,
                        ],
                        [
                            'sort_order' => $optionIndex,
                        ],
                    ))
                    ->values();

                foreach ($questionEntry['voters'] as $voterIndex => $email) {
                    $voter = $people->get($email);

                    if ($voter === null) {
                        continue;
                    }

                    SurveyVote::query()->firstOrCreate(
                        [
                            'survey_question_id' => $question->id,
                            'user_id' => $voter->id,
                        ],
                        [
                            'survey_option_id' => $options[$voterIndex % $options->count()]->id,
                        ],
                    );
                }
            }
        }
    }
}
