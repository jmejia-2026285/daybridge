<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sincronizacion diaria con Moodle (cursos, progreso y calificaciones).
// Requiere un planificador que ejecute `php artisan schedule:run` cada minuto.
Schedule::command('moodle:sync-users')->dailyAt('02:00')->withoutOverlapping();

// Respaldo diario de la base de datos y los archivos subidos, hacia el
// repositorio privado de GitHub (release por dia, retencion en services.backup).
Schedule::command('backup:run')->dailyAt('23:00')->withoutOverlapping();
