<?php

namespace Database\Seeders;

use App\Enums\ItDeskRole;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $areas = Area::query()->pluck('id', 'slug');

        $people = [
            ['Ana Lucía Morales', 'test@example.com', 'personas', 'People Partner', 0, 33, 4, 2, UserRole::Admin, null],
            ['Diego Salazar', 'diego.salazar@daybridge.test', 'operaciones', 'Coordinador de Logística', 0, 38, 6, 4, UserRole::Editor, null],
            ['María José Castillo', 'maria.castillo@daybridge.test', 'tecnologia', 'Tech Lead', 0, 34, 5, 1, UserRole::Editor, ItDeskRole::Admin],
            ['Luis Fernando Arévalo', 'luis.arevalo@daybridge.test', 'finanzas', 'Controller', 3, 41, 7, 5, UserRole::User, null],
            ['Andrea Figueroa', 'andrea.figueroa@daybridge.test', 'crecimiento', 'Brand Designer', 7, 29, 2, 3, UserRole::User, null],
            ['Carlos Enrique Mérida', 'carlos.merida@daybridge.test', 'operaciones', 'Jefe de Planta', 9, 45, 8, 2, UserRole::User, null],
            ['Sofía Marroquín', 'sofia.marroquin@daybridge.test', 'personas', 'Talent Partner', 12, 28, 1, 6, UserRole::User, null],
            ['José Pablo Barrios', 'jose.barrios@daybridge.test', 'tecnologia', 'Ingeniero de Plataforma', 16, 31, 3, 2, UserRole::Editor, ItDeskRole::Tecnico],
            ['Gabriela Monzón', 'gabriela.monzon@daybridge.test', 'crecimiento', 'Content Strategist', 21, 30, 2, 8, UserRole::User, null],
            ['Ricardo Quiñónez', 'ricardo.quinonez@daybridge.test', 'finanzas', 'Analista Financiero', 26, 36, 4, 1, UserRole::User, null],
            ['Karla Patricia Solís', 'karla.solis@daybridge.test', 'personas', 'Learning & Development', 31, 39, 3, 7, UserRole::Editor, null],
            ['Estuardo Lemus', 'estuardo.lemus@daybridge.test', 'operaciones', 'Supervisor de Turno', 37, 42, 5, 3, UserRole::User, null],
            ['Fernanda Aguilar', 'fernanda.aguilar@daybridge.test', 'tecnologia', 'QA Engineer', 44, 27, 1, 2, UserRole::User, ItDeskRole::Tecnico],
            ['Mario René Chávez', 'mario.chavez@daybridge.test', 'finanzas', 'Auditor Interno', 52, 44, 6, 9, UserRole::User, null],
        ];

        foreach ($people as [$name, $email, $area, $jobTitle, $offset, $age, $years, $months, $role, $itDeskRole]) {
            User::query()->updateOrCreate(['email' => $email], [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => 'password',
                'role' => $role,
                'it_desk_role' => $itDeskRole,
                'is_active' => true,
                'area_id' => $areas[$area],
                'job_title' => $jobTitle,
                'birth_date' => today()->subYears($age)->addDays($offset),
                'joined_at' => today()->subYears($years)->subMonths($months),
            ]);
        }
    }
}
