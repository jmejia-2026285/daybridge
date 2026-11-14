<?php

namespace Database\Seeders;

use App\Enums\DocumentCategory;
use App\Models\Area;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $people = User::query()->get()->keyBy('email');
        $areas = Area::query()->pluck('id', 'slug');

        if ($people->isEmpty()) {
            return;
        }

        $uploader = $people->get('test@example.com') ?? $people->first();

        $documents = [
            [
                'title' => 'Manual de bienvenida Daybridge',
                'description' => 'Todo lo que necesitas saber en tus primeros días: accesos, herramientas y contactos clave.',
                'area' => 'personas',
                'category' => DocumentCategory::Manual,
                'slug' => 'manual-bienvenida',
            ],
            [
                'title' => 'Política de trabajo remoto',
                'description' => 'Lineamientos de horarios, disponibilidad y uso de espacios compartidos.',
                'area' => 'personas',
                'category' => DocumentCategory::Policy,
                'slug' => 'politica-trabajo-remoto',
            ],
            [
                'title' => 'Formato de solicitud de vacaciones',
                'description' => 'Plantilla para solicitar días de descanso con la aprobación de tu líder de área.',
                'area' => 'operaciones',
                'category' => DocumentCategory::Template,
                'slug' => 'formato-vacaciones',
            ],
            [
                'title' => 'Acta de cierre de trimestre',
                'description' => 'Resumen de acuerdos y próximos pasos de la revisión trimestral del equipo.',
                'area' => 'finanzas',
                'category' => DocumentCategory::Minutes,
                'slug' => 'acta-cierre-trimestre',
            ],
            [
                'title' => 'Guía de seguridad de la información',
                'description' => 'Buenas prácticas para proteger accesos, dispositivos y datos de la empresa.',
                'area' => 'tecnologia',
                'category' => DocumentCategory::Policy,
                'slug' => 'guia-seguridad-informacion',
            ],
        ];

        foreach ($documents as $entry) {
            $path = 'documents/demo-'.$entry['slug'].'.txt';
            $contents = $entry['title'].PHP_EOL.PHP_EOL.$entry['description'].PHP_EOL;

            Storage::disk('local')->put($path, $contents);

            Document::query()->firstOrCreate(
                ['title' => $entry['title']],
                [
                    'user_id' => $uploader->id,
                    'area_id' => $areas->get($entry['area']),
                    'description' => $entry['description'],
                    'category' => $entry['category'],
                    'file_path' => $path,
                    'file_name' => $entry['slug'].'.txt',
                    'mime_type' => 'text/plain',
                    'file_size' => strlen($contents),
                ],
            );
        }
    }
}
