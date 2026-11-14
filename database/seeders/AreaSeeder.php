<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Operaciones', 'slug' => 'operaciones', 'color' => '#0F3D3E', 'sort_order' => 1],
            ['name' => 'Personas', 'slug' => 'personas', 'color' => '#7C2D2D', 'sort_order' => 2],
            ['name' => 'Tecnología', 'slug' => 'tecnologia', 'color' => '#3B4A57', 'sort_order' => 3],
            ['name' => 'Finanzas', 'slug' => 'finanzas', 'color' => '#A06B12', 'sort_order' => 4],
            ['name' => 'Crecimiento', 'slug' => 'crecimiento', 'color' => '#4F6B2E', 'sort_order' => 5],
        ];

        foreach ($areas as $area) {
            Area::query()->updateOrCreate(['slug' => $area['slug']], $area);
        }
    }
}
