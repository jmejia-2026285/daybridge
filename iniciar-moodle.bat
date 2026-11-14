@echo off
REM Arranca la base de datos y el servidor de Moodle para Daybridge.
REM MySQL de Laragon en el puerto 3307 y Apache en el puerto 80.

set MYSQL=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin
set APACHE=C:\laragon\bin\apache\httpd-2.4.69-261002-Win64-VS18\bin

REM MySQL (si no esta corriendo ya)
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe" >NUL
if errorlevel 1 (
    echo Iniciando MySQL en el puerto 3307...
    start "" /B "%MYSQL%\mysqld.exe" --datadir=C:\laragon\data\mysql8 --port=3307 --console
) else (
    echo MySQL ya esta corriendo.
)

REM Apache (si no esta corriendo ya). El PATH de PHP es necesario para que
REM carguen intl, sodium y curl dentro de Apache.
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
if errorlevel 1 (
    echo Iniciando Apache en el puerto 80...
    set PATH=C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64;%PATH%
    start "" /B "%APACHE%\httpd.exe"
) else (
    echo Apache ya esta corriendo.
)

echo.
echo Moodle:  http://localhost/moodle
echo Usuario administrador: admin
pause
