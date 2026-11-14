<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AreaSeeder::class,
            UserSeeder::class,
            PostSeeder::class,
            WallSeeder::class,
            BirthdayWishSeeder::class,
            ItDeskSeeder::class,
            TaskSeeder::class,
            SurveySeeder::class,
            DocumentSeeder::class,
            FormSeeder::class,
        ]);
    }
}
