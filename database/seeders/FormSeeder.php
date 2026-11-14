<?php

namespace Database\Seeders;

use App\Enums\FormFieldType;
use App\Models\Area;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        $people = User::query()->get()->keyBy('email');
        $areas = Area::query()->pluck('id', 'slug');

        if ($people->isEmpty()) {
            return;
        }

        $creator = $people->get('test@example.com') ?? $people->first();

        $forms = [
            [
                'title' => 'Solicitud de vacaciones',
                'description' => 'Cuéntanos cuándo quieres descansar y qué tipo de permiso necesitas. Personas te confirmará por correo.',
                'area' => 'personas',
                'closes_at' => today()->addDays(21),
                'is_active' => true,
                'fields' => [
                    ['label' => 'Nombre completo', 'type' => FormFieldType::Text, 'required' => true],
                    ['label' => 'Fecha de inicio', 'type' => FormFieldType::Date, 'required' => true],
                    ['label' => 'Fecha de fin', 'type' => FormFieldType::Date, 'required' => true],
                    ['label' => 'Tipo de permiso', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['Vacaciones', 'Permiso personal', 'Licencia médica']],
                    ['label' => 'Comentarios', 'type' => FormFieldType::Textarea, 'required' => false],
                ],
                'submissions' => [
                    [
                        'email' => 'maria.castillo@daybridge.test',
                        'answers' => [
                            'Nombre completo' => 'María Castillo',
                            'Fecha de inicio' => today()->addDays(12)->toDateString(),
                            'Fecha de fin' => today()->addDays(16)->toDateString(),
                            'Tipo de permiso' => 'Vacaciones',
                            'Comentarios' => 'Ya coordiné la cobertura con el equipo de diseño.',
                        ],
                    ],
                    [
                        'email' => 'jose.barrios@daybridge.test',
                        'answers' => [
                            'Nombre completo' => 'José Barrios',
                            'Fecha de inicio' => today()->addDays(20)->toDateString(),
                            'Fecha de fin' => today()->addDays(21)->toDateString(),
                            'Tipo de permiso' => 'Permiso personal',
                            'Comentarios' => 'Trámite personal en el banco.',
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Reporte de incidencia de equipo',
                'description' => 'Registra cualquier falla en tu equipo o accesos. IT Desk dará seguimiento con un ticket.',
                'area' => 'tecnologia',
                'closes_at' => today()->addDays(10),
                'is_active' => true,
                'fields' => [
                    ['label' => 'Código del equipo', 'type' => FormFieldType::Text, 'required' => true],
                    ['label' => 'Descripción del problema', 'type' => FormFieldType::Textarea, 'required' => true],
                    ['label' => 'Prioridad', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['Baja', 'Media', 'Alta']],
                    ['label' => 'Correo de contacto', 'type' => FormFieldType::Email, 'required' => true],
                ],
                'submissions' => [
                    [
                        'email' => 'luis.arevalo@daybridge.test',
                        'answers' => [
                            'Código del equipo' => 'DSP-0001',
                            'Descripción del problema' => 'La batería no dura más de dos horas y el cargador se calienta.',
                            'Prioridad' => 'Alta',
                            'Correo de contacto' => 'luis.arevalo@daybridge.test',
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Registro de capacitación interna',
                'description' => 'Formulario de ejemplo ya cerrado. Sirve para llevar el registro de las personas que asistieron.',
                'area' => 'operaciones',
                'closes_at' => today()->subDays(3),
                'is_active' => true,
                'fields' => [
                    ['label' => 'Nombre', 'type' => FormFieldType::Text, 'required' => true],
                    ['label' => 'Capacitación', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['Comunicación efectiva', 'Finanzas para equipos', 'Seguridad de la información']],
                    ['label' => 'Fecha de asistencia', 'type' => FormFieldType::Date, 'required' => true],
                ],
                'submissions' => [
                    [
                        'email' => 'karla.solis@daybridge.test',
                        'answers' => [
                            'Nombre' => 'Karla Solís',
                            'Capacitación' => 'Comunicación efectiva',
                            'Fecha de asistencia' => today()->subDays(6)->toDateString(),
                        ],
                    ],
                ],
            ],
        ];

        foreach ($forms as $entry) {
            $form = Form::query()->firstOrCreate(
                ['title' => $entry['title']],
                [
                    'user_id' => $creator->id,
                    'area_id' => $areas->get($entry['area']),
                    'description' => $entry['description'],
                    'closes_at' => $entry['closes_at'],
                    'is_active' => $entry['is_active'],
                ],
            );

            $fields = collect($entry['fields'])
                ->mapWithKeys(function (array $field, int $index) use ($form): array {
                    $created = FormField::query()->firstOrCreate(
                        [
                            'form_id' => $form->id,
                            'label' => $field['label'],
                        ],
                        [
                            'type' => $field['type'],
                            'options' => $field['options'] ?? null,
                            'is_required' => $field['required'],
                            'sort_order' => $index,
                        ],
                    );

                    return [$field['label'] => $created];
                });

            foreach ($entry['submissions'] as $submissionEntry) {
                $person = $people->get($submissionEntry['email']);

                if ($person === null) {
                    continue;
                }

                $answers = [];

                foreach ($submissionEntry['answers'] as $label => $value) {
                    $field = $fields->get($label);

                    if ($field !== null) {
                        $answers[$field->id] = $value;
                    }
                }

                FormSubmission::query()->firstOrCreate(
                    [
                        'form_id' => $form->id,
                        'user_id' => $person->id,
                    ],
                    [
                        'answers' => $answers,
                    ],
                );
            }
        }
    }
}
