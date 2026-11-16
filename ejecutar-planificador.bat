@echo off
rem Ejecuta el planificador de Laravel (tareas programadas: respaldo diario,
rem sincronizacion con Moodle). Lo invoca la tarea de Windows "Daybridge Planificador".
cd /d C:\laragon\www\daybridge-completo
"C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" artisan schedule:run
