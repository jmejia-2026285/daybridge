@echo off
rem ============================================================
rem  Inicia TODO el entorno Daybridge + Moodle en la red local.
rem  Doble clic para arrancar; cierra esta ventana para detener Daybridge.
rem ============================================================
chcp 65001 >nul
cd /d "%~dp0"
set "PATH=%USERPROFILE%\.config\herd-lite\bin;%PATH%"

set "LARAGON=C:\laragon"
set "MOODLE_DIR=%LARAGON%\www\moodle"
set "MYSQL_BIN=%LARAGON%\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe"
set "MYSQL_DATA=%LARAGON%\data\mysql8"
set "APACHE_BIN=%LARAGON%\bin\apache\httpd-2.4.69-261002-Win64-VS18\bin\httpd.exe"
set "APACHE_PHP=%LARAGON%\bin\php\php-8.3.36-Win32-vs16-x64"

where php >nul 2>nul
if errorlevel 1 (
    echo [ERROR] No se encontro PHP. Instalalo con php.new o revisa %USERPROFILE%\.config\herd-lite\bin
    pause
    exit /b 1
)

rem PHP de Laragon en el PATH: Apache lo necesita para servir Moodle (mod_php).
set "PATH=%USERPROFILE%\.config\herd-lite\bin;%APACHE_PHP%;%PATH%"

rem ---------- 1. Detectar la IP de la red local y actualizar la configuracion ----------
set "IP="
for /f "tokens=*" %%a in ('powershell -NoProfile -Command "$i = Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*' -and $_.PrefixOrigin -ne 'WellKnown' } | Select-Object -First 1; if ($i) { $i.IPAddress }"') do set "IP=%%a"

if defined IP (
    set "DAYBRIDGE_IP=%IP%"
    powershell -NoProfile -Command "$f='%~dp0.env'; if (Test-Path $f) { $c=Get-Content $f -Raw; $c=$c -replace '(?m)^APP_URL=.*',('APP_URL=http://'+$env:DAYBRIDGE_IP+':8000') -replace '(?m)^VITE_HMR_HOST=.*',('VITE_HMR_HOST='+$env:DAYBRIDGE_IP) -replace '(?m)^MOODLE_URL=.*',('MOODLE_URL=http://'+$env:DAYBRIDGE_IP+'/moodle'); Set-Content $f $c -NoNewline }"
    if exist "%MOODLE_DIR%\config.php" powershell -NoProfile -Command "$f='%MOODLE_DIR%\config.php'; $c=Get-Content $f -Raw; $c=$c -replace '\$CFG->wwwroot\s*=\s*''[^'']*''',('\$CFG->wwwroot   = ''http://'+$env:DAYBRIDGE_IP+'/moodle'''); Set-Content $f $c -NoNewline"
    rem La URL de Moodle cambia: las rutas de assets quedan guardadas en su cache.
    if exist "%APACHE_PHP%\php.exe" "%APACHE_PHP%\php.exe" "%MOODLE_DIR%\admin\cli\purge_caches.php" >nul 2>&1
    echo  Red local detectada: %IP%
)

rem ---------- 2. MySQL (Daybridge y Moodle) ----------
netstat -ano | findstr /r /c:":3307 .*LISTENING" >nul
if errorlevel 1 (
    if exist "%MYSQL_BIN%" (
        echo  Iniciando MySQL...
        start "MySQL 3307" /min "%MYSQL_BIN%" --datadir="%MYSQL_DATA%" --port=3307 --console
    ) else (
        echo  [AVISO] No se encontro MySQL en %MYSQL_BIN%
    )
)

rem ---------- 3. Apache (sirve Moodle en el puerto 80, ruta /moodle) ----------
netstat -ano | findstr /r /c:":80 .*LISTENING" >nul
if errorlevel 1 (
    if exist "%APACHE_BIN%" (
        echo  Iniciando Apache ^(Moodle^)...
        start "Apache Moodle" /min "%APACHE_BIN%"
    ) else (
        echo  [AVISO] No se encontro Apache en %APACHE_BIN%
    )
)

rem ---------- 4. Daybridge ----------
if not exist "public\build\manifest.json" (
    echo Compilando estilos y scripts por primera vez...
    call npm run build || (pause & exit /b 1)
)

rem El enlace se rompe al copiar el proyecto entre equipos: se recrea si es una carpeta vacia.
if exist "public\storage" (
    if not exist "public\storage\*.*" (
        rmdir "public\storage"
        php artisan storage:link >nul
    )
) else (
    php artisan storage:link >nul
)

rem Solo la config (recoge cambios del .env); borrar las vistas hace lenta la primera carga.
php artisan config:clear >nul

rem Mantener las contrasenas de Moodle identicas a las de Daybridge.
if exist "%MOODLE_DIR%\admin\cli\daybridge-sync-passwords.php" "%APACHE_PHP%\php.exe" "%MOODLE_DIR%\admin\cli\daybridge-sync-passwords.php" 2>&1 | findstr /c:"Actualizado"

echo.
echo  Todo esta corriendo. Abrelo desde:
echo    - Este equipo:      http://127.0.0.1:8000  ^(Daybridge^)
echo    - Otro equipo/cel:  http://%IP%:8000       ^(Daybridge^)
echo    - Moodle:           http://%IP%/moodle     ^(desde cualquier equipo^)
echo.
echo  Cierra esta ventana para detener el servidor de Daybridge.
echo.

rem ---------- 5. Planificador de tareas programadas ----------
rem Ejecuta `schedule:run` cada minuto mientras el servidor este abierto
rem (sincronizacion con Moodle 02:00, etc.).
start "Daybridge Planificador" cmd /c "for /l %%i in () do (php artisan schedule:run >nul 2>&1 & timeout /t 60 /nobreak >nul)"

php artisan serve --host=0.0.0.0 --port=8000
pause
