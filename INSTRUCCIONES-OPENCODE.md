# Daybridge: instrucciones para OpenCode

Este documento es para el agente (OpenCode) que trabaje con Daybridge en el equipo nuevo. Tiene tres partes:

1. **Instalar y ejecutar** el proyecto en el equipo nuevo.
2. **Conectar Moodle** con Daybridge.
3. **Aplicar el diseño de Daybridge a Moodle** (tema visual).

Sigue los pasos en orden. Después de cada paso hay una **comprobación**: si no se cumple, detente, informa el error exacto y no continúes.

---

## 0. Reglas para el agente

- Responde y escribe en español.
- Lee también `AGENTS.md` (convenciones de Laravel Boost de este proyecto).
- **Nunca** muestres, copies ni envíes el contenido de `.env` (contiene `APP_KEY`, contraseñas y el token de Moodle). Si necesitas un valor, pregúntalo.
- No borres `database/database.sqlite` ni `storage/app/`: contienen los datos reales.
- Antes de dar algo por terminado ejecuta `php artisan test` y confirma que todas las pruebas pasan. Hoy son **242 pruebas** en verde.
- Después de editar PHP ejecuta `vendor/bin/pint` sobre los archivos que tocaste.
- Después de editar CSS o JS ejecuta `npm run build`.
- No uses `php artisan serve` en un servidor de producción (solo sirve para desarrollo o red local).
- Si un paso pide una decisión (dominio, contraseñas, dónde está Moodle), pregunta. No inventes valores.

---

## 1. Qué es el proyecto

Daybridge es una intranet de comunicación interna: comunicados, muro, directorio, cumpleaños, tareas, encuestas, formularios, documentos, chat, notificaciones, IT Desk y capacitación (cursos de Moodle).

| Pieza | Versión / detalle |
|---|---|
| Backend | Laravel 13, PHP 8.3 o superior (probado con 8.5) |
| Interfaz | Livewire 3 + Volt (componentes en `resources/views/livewire/**`, con PHP dentro del `.blade.php`) |
| Estilos | Tailwind CSS 3 + tokens en `resources/css/app.css`, compilado con Vite 8 |
| Animaciones | GSAP en `resources/js/motion.js` |
| Base de datos | SQLite (`database/database.sqlite`) |
| Moodle | Conector REST en `app/Services/Moodle/` y página `/capacitacion` |

Carpetas clave:

- `app/Services/Moodle/`: cliente, servicio y modelo de curso de Moodle.
- `app/Console/Commands/SyncMoodleUsers.php`: comando `moodle:sync-users`.
- `resources/views/livewire/pages/learning/index.blade.php`: página de capacitación.
- `tests/Feature/LearningTest.php`: pruebas que **simulan** Moodle (no necesitan uno real).
- `resources/css/app.css` y `tailwind.config.js`: sistema de diseño (tokens).
- `public/media/brand/`: logotipos. `public/media/background/`: foto de fondo.

### Problemas conocidos ya resueltos (no los reintroduzcas)

1. **El vidrio (backdrop-filter) no desenfocaba en Chrome/Edge.** El minificador de Vite 8 (lightningcss) se queda solo con la última declaración. Si escribes `backdrop-filter` y después `-webkit-backdrop-filter`, se pierde la estándar. **Regla: escribe solo `backdrop-filter` en `app.css`**; autoprefixer agrega el prefijo en el orden correcto.
2. **El velo de fondo no se veía.** El `<body>` necesita la clase `isolate` para que el velo con `z-index: -1` quede encima de la foto. No la quites de `layouts/app.blade.php` ni de `layouts/guest.blade.php`.
3. **Livewire deja de funcionar si una vista empieza con BOM.** Guarda los `.blade.php` en UTF-8 **sin BOM**.
4. **El indicador de la barra lateral** se coloca con `placeIndicatorOnActive()` en `motion.js`. Si cambias la navegación, conserva esa llamada.
5. **Acciones de Livewire sin límite.** Usa `App\Support\ActionThrottle::hit()` en acciones que se puedan repetir en bucle (enviar, reaccionar, comentar).
6. **Usuarios desactivados.** `EnsureUserIsActive` está registrado como middleware persistente de Livewire en `AppServiceProvider`. No lo quites.
7. **En Git Bash (Windows)**, los argumentos que empiezan con `/` se convierten en rutas de Windows. Usa `MSYS_NO_PATHCONV=1` si pasas rutas URL como `/login` a un script.

---

## 2. Instalar en el equipo nuevo

### 2.1 Qué debe venir copiado del equipo anterior

| Copiar | Por qué |
|---|---|
| Todo el código del proyecto | |
| `.env` | Contiene `APP_KEY`. Si cambia, se invalidan sesiones y datos cifrados. |
| `database/database.sqlite` | Todos los datos. |
| `storage/app/` | Documentos subidos y fotos de perfil. |

| **No** copiar (se regenera) | |
|---|---|
| `vendor/`, `node_modules/`, `public/build/` | Se reinstalan y recompilan. |
| `public/storage` | Es un enlace; copiado como carpeta se rompe. Se recrea con `storage:link`. |
| `storage/framework/cache/*`, `storage/framework/views/*`, `storage/logs/*` | Cachés y registros. |

**Comprobación:** existen `.env`, `database/database.sqlite` y `storage/app/public/`.

### 2.2 Requisitos

- PHP 8.3+ con extensiones: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `gd`, `curl`, `zip`.
- Composer 2.
- Node.js 20+ y npm.

Instalar PHP y Composer si faltan:

- **Windows (PowerShell):**
  ```powershell
  Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
  ```
- **Linux:**
  ```bash
  /bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
  ```

Después de instalar, hay que **cerrar y abrir la terminal** para que `php` y `composer` estén en el PATH.

**Comprobación:**
```bash
php -v          # 8.3 o superior
php -m          # debe listar pdo_sqlite, mbstring, gd, curl, fileinfo
composer -V
node -v
```

### 2.3 Instalar dependencias y compilar

Desde la carpeta del proyecto:

```bash
composer install
npm install
npm run build
php artisan storage:link
php artisan migrate --force
php artisan optimize:clear
```

**Comprobación:**
- `php artisan migrate:status` no muestra migraciones `Pending`.
- Existe `public/build/manifest.json`.
- Existe `public/storage`.
- `php artisan test` termina con todas las pruebas en verde.

### 2.4 Ajustar la dirección del equipo

En `.env`, cambia a la IP o dominio del equipo nuevo (pregunta cuál es):

```env
APP_URL=http://IP-O-DOMINIO:8000
VITE_HMR_HOST=IP-O-DOMINIO
```

Luego: `php artisan config:clear`.

Si `APP_URL` no coincide con la dirección real, las fotos de perfil y algunos enlaces fallan.

### 2.5 Ejecutar

- **Windows (red local):** doble clic en `iniciar-servidor.bat`. Muestra las direcciones para abrir la app.
- **Cualquier sistema:**
  ```bash
  php artisan serve --host=0.0.0.0 --port=8000
  ```
- Si es un **servidor de producción con dominio**: Nginx o Apache + PHP-FPM, raíz web en `public/`, HTTPS obligatorio y `APP_ENV=production`, `APP_DEBUG=false`. Además, un worker de colas (`php artisan queue:work`, con Supervisor o systemd).

**Comprobación:** `http://IP:8000/login` responde 200 y muestra el login con fondo de montañas y el logotipo de Daybridge.

Si otros equipos no pueden abrirla, revisa el firewall: hay que permitir el puerto 8000 (o `php.exe` en Windows).

### 2.6 Registro de cuentas

Las cuentas creadas en `/register` quedan **inactivas** hasta que un administrador las activa en *Admin › Personas*. Para hacer administrador a alguien desde la terminal (pregunta el correo):

```bash
php artisan tinker --execute="App\Models\User::where('email','CORREO')->firstOrFail()->forceFill(['role'=>'admin','is_active'=>true])->save();"
```

---

## 3. Conectar Moodle

Daybridge **no incluye Moodle**. Se conecta a un Moodle que ya exista, por su API REST con un token. Vincula cuentas **por correo electrónico**: el correo debe ser el mismo en los dos sistemas.

Funciones de Moodle que usa el código (`app/Services/Moodle/`):

| Función de Moodle | Para qué |
|---|---|
| `core_webservice_get_site_info` | Comprobar la conexión |
| `core_user_get_users_by_field` | Buscar la cuenta por correo |
| `core_user_create_users` | Crear cuentas (solo con `--create`) |
| `core_enrol_get_users_courses` | Cursos y progreso de cada persona |
| `gradereport_overview_get_course_grades` | Calificaciones |

### 3.1 Preparar Moodle (lo hace una persona administradora de Moodle)

1. *Administración del sitio › Funciones avanzadas*: activar **Servicios web**.
2. *Administración del sitio › Servidor › Servicios web › Gestionar protocolos*: activar **REST**.
3. *Servicios externos*: crear un servicio llamado `Daybridge` y agregar **solo** las cinco funciones de la tabla.
4. Crear un **usuario técnico** (no usar el administrador) con un rol que tenga:
   `webservice/rest:use`, `moodle/user:viewdetails`, `moodle/course:viewparticipants`, `gradereport/overview:view` y, si se usará `--create`, `moodle/user:create`.
5. *Gestionar tokens*: generar un token para ese usuario y el servicio `Daybridge`. En **Restricción de IP**, poner solo la IP del servidor de Daybridge.

### 3.2 Configurar Daybridge

Agregar al `.env` (pide la URL y el token; no los escribas en ningún otro archivo):

```env
MOODLE_ENABLED=true
MOODLE_URL=https://moodle.dominio.com
MOODLE_TOKEN=token_generado_en_moodle
MOODLE_TIMEOUT=10
MOODLE_CACHE_MINUTES=10
MOODLE_USER_AUTH=manual
```

- `MOODLE_URL` debe empezar con `https://` en producción y **sin** `/` al final.
- `MOODLE_USER_AUTH` es el método de autenticación de las cuentas que se creen en Moodle (`manual`, `ldap`, `oauth2`, etc.). Debe coincidir con el que use Moodle.

Luego:

```bash
php artisan config:clear
php artisan moodle:sync-users --email=CORREO_DE_PRUEBA   # primero con una persona
php artisan moodle:sync-users                             # vincula a todos por correo
php artisan moodle:sync-users --create                    # opcional: crea en Moodle las cuentas que falten
```

**Comprobación:**
- El comando muestra una tabla con `Id Moodle` para cada persona y termina sin errores.
- En Daybridge, `/capacitacion` muestra los cursos de la persona con su progreso.
- Si dice "Moodle no está configurado", revisa las tres variables y ejecuta `php artisan config:clear`.
- Si dice "No fue posible conectar con Moodle", revisa la URL, el HTTPS y la restricción de IP del token.

### 3.3 Tarea pendiente: sincronización automática

Hoy la sincronización solo corre a mano. Para programarla:

1. En `routes/console.php` agregar:
   ```php
   use Illuminate\Support\Facades\Schedule;

   Schedule::command('moodle:sync-users')->dailyAt('02:00')->withoutOverlapping();
   ```
2. Activar el planificador:
   - **Linux:** cron `* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1`
   - **Windows:** en el Programador de tareas, una tarea que cada minuto ejecute `php artisan schedule:run` en la carpeta del proyecto.
3. Confirmar con `php artisan schedule:list` que el comando aparece.

### 3.4 Probar sin un Moodle real

`tests/Feature/LearningTest.php` simula las respuestas de Moodle con `Http::fake()`. Si cambias algo de la integración, agrega o ajusta pruebas ahí y ejecuta:

```bash
php artisan test --filter=LearningTest
```

---

## 4. Aplicar el diseño de Daybridge a Moodle

Objetivo: que al pasar de Daybridge a Moodle se sienta el mismo producto. La identidad es **oscura, editorial, con acentos dorados y vidrio translúcido sobre una foto de montañas**.

### 4.1 Valores del sistema de diseño ("Señal")

Salen de `resources/css/app.css` y `tailwind.config.js`. Usa **exactamente** estos valores.

**Colores**

| Token | Uso | Hex |
|---|---|---|
| canvas | Fondo general | `#020C14` |
| surface | Tarjetas y paneles sólidos | `#051623` |
| sunken | Campos de formulario, zonas hundidas | `#02080F` |
| line | Bordes | `#1E2832` |
| line-strong | Bordes destacados | `#323C4B` |
| ink | Texto principal | `#FFFFFF` |
| ink-secondary | Texto secundario | `#D2DCE1` |
| ink-muted | Etiquetas y texto de apoyo | `#BEC8CD` |
| ink-disabled | Deshabilitado | `#788287` |
| brand | Botón principal, elemento activo | `#B39051` |
| brand-strong | Hover del botón principal | `#8C6E3C` |
| accent | Enlaces, resaltados | `#DAB981` |
| accent-strong | Enlace activo / hover | `#FFD6A6` |
| accent-foreground | Texto sobre accent | `#051E39` |
| success | Correcto / completado | `#4CAF50` |
| warning | Advertencia | `#FFD600` |
| danger | Error | `#F44336` |
| info | Información | `#2196F3` |
| signal | Punto naranja del logotipo (uso mínimo) | `#FF5A1F` |

**Tipografía:** `Archivo` (Google Fonts) con ejes de ancho y peso:
`https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@75..125,400..900&display=swap`

- Títulos grandes: peso 900, `font-stretch: 112.5%` (expandida), interletrado `-0.035em`.
- Etiquetas pequeñas: mayúsculas, peso 600, `0.6875rem`, interletrado `0.08em`, color ink-muted.
- Texto: peso 400, `1rem`, interlineado `1.625`.

**Formas y movimiento**

- Radio de tarjetas y paneles: `20px`. Campos de formulario: cápsula (`9999px`). Botones: `0.375rem`.
- Transiciones: `180ms` con `cubic-bezier(0.2, 0, 0, 1)`.
- Respetar `prefers-reduced-motion` y `prefers-reduced-transparency`.

**Vidrio (solo en controles flotantes)**

```css
background-color: rgba(2, 12, 20, 0.6);
backdrop-filter: blur(24px) saturate(180%);
border: 1px solid rgba(255, 255, 255, 0.1);
border-radius: 20px;
box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.1), 0 4px 12px rgba(0, 0, 0, 0.3);
```

**Dónde SÍ va vidrio en Moodle:** barra superior, cajones laterales (índice del curso y bloques), menús desplegables, ventanas modales y avisos emergentes.
**Dónde NO:** contenido de cursos, lecciones, foros, cuestionarios, tablas de calificaciones y editores de texto. Ahí se usa `surface` sólido para que se lea sin esfuerzo.

**Recursos gráficos** (en la carpeta `public/` de Daybridge):

| Archivo | Uso en Moodle |
|---|---|
| `media/brand/A-d-burbuja_horizontal-oscuro.png` | Logotipo (versión clara para fondo oscuro) |
| `media/brand/A-d-burbuja_icono.png` | Logotipo compacto |
| `media/background/fondo-montanas-desktop.jpg` | Imagen de fondo |
| `icons/favicon.png` | Favicon |

### 4.2 Opción A: rápida, sin programar (configuración de Moodle)

Sirve para cualquier Moodle 4.x o 5.x con el tema **Boost** (incluido MoodleCloud). Hazla primero; si se queda corta, pasa a la opción B.

1. *Administración del sitio › Apariencia › Logotipos*:
   - **Logotipo:** `A-d-burbuja_horizontal-oscuro.png`
   - **Logotipo compacto:** `A-d-burbuja_icono.png`
   - **Favicon:** `favicon.png`
2. *Administración del sitio › Apariencia › HTML adicional › Dentro de HEAD*: cargar la fuente.
   ```html
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@75..125,400..900&display=swap" rel="stylesheet">
   ```
3. *Administración del sitio › Apariencia › Temas › Boost*:
   - Pestaña **General**:
     - **Color de marca:** `#B39051`
     - **Imagen de fondo:** `fondo-montanas-desktop.jpg`
   - Pestaña **Avanzado › SCSS inicial en bruto** (variables; se aplican antes de compilar Bootstrap):
     ```scss
     $font-family-sans-serif: "Archivo", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;

     $primary:   #B39051;
     $secondary: #323C4B;
     $success:   #4CAF50;
     $info:      #2196F3;
     $warning:   #FFD600;
     $danger:    #F44336;

     $body-bg:    #020C14;
     $body-color: #FFFFFF;
     $text-muted: #BEC8CD;
     $link-color: #DAB981;
     $link-hover-color: #FFD6A6;

     $border-color: #1E2832;
     $card-bg: #051623;
     $card-border-color: #1E2832;
     $card-border-radius: 20px;

     $input-bg: #02080F;
     $input-color: #FFFFFF;
     $input-border-color: #323C4B;
     $input-placeholder-color: #788287;
     $input-border-radius: 9999px;

     $dropdown-bg: #051623;
     $dropdown-link-color: #FFFFFF;
     $dropdown-link-hover-bg: #1E2832;
     $dropdown-link-hover-color: #FFFFFF;

     $modal-content-bg: #051623;
     $list-group-bg: #051623;
     $list-group-border-color: #1E2832;
     $table-color: #FFFFFF;

     $btn-border-radius: 0.375rem;
     $border-radius: 0.375rem;
     ```
   - Pestaña **Avanzado › SCSS en bruto** (reglas; se aplican al final):
     ```scss
     // Velo sobre la foto de fondo (mismo tratamiento que Daybridge).
     body {
         background-color: #020C14;
         background-attachment: fixed;
         background-size: cover;
         isolation: isolate;
     }

     body::before {
         content: "";
         position: fixed;
         inset: 0;
         z-index: -1;
         pointer-events: none;
         opacity: 0.7;
         background:
             linear-gradient(to bottom, rgba(2, 12, 20, 0.6) 0%, rgba(2, 12, 20, 0.25) 35%, transparent 60%),
             radial-gradient(circle at center, rgba(0, 0, 0, 0.5) 0%, #020C14 100%);
     }

     // Vidrio: barra superior, cajones, menús y modales.
     .navbar.fixed-top,
     .drawer,
     .dropdown-menu,
     .modal-content,
     .toast {
         background-color: rgba(2, 12, 20, 0.6) !important;
         backdrop-filter: blur(24px) saturate(180%);
         border: 1px solid rgba(255, 255, 255, 0.1);
         box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.1), 0 4px 12px rgba(0, 0, 0, 0.3);
     }

     .dropdown-menu,
     .modal-content { border-radius: 20px; }

     // El contenido de lectura se queda sólido.
     #region-main,
     .course-content,
     .card {
         background-color: #051623;
         border-color: #1E2832;
     }

     #region-main { border-radius: 20px; }

     // Títulos con la personalidad editorial de Daybridge.
     h1, .h1, h2, .h2, .page-header-headings h1 {
         font-weight: 900;
         font-stretch: 112.5%;
         letter-spacing: -0.035em;
     }

     // Etiquetas pequeñas en mayúsculas.
     .badge,
     .text-uppercase,
     legend {
         letter-spacing: 0.08em;
     }

     // Progreso de cursos con el dorado de marca.
     .progress { background-color: #02080F; border-radius: 9999px; }
     .progress-bar { background-color: #DAB981; }

     // Botón principal.
     .btn-primary {
         color: #FFFFFF;
         &:hover, &:focus { background-color: #8C6E3C; border-color: #8C6E3C; }
     }

     // Foco visible en todos los controles.
     a:focus-visible,
     .btn:focus-visible,
     .form-control:focus {
         outline: 2px solid #DAB981;
         outline-offset: 2px;
         box-shadow: none;
     }

     // Inicio de sesión: tarjeta como en Daybridge.
     #page-login-index .login-container,
     #page-login-index .loginform {
         background-color: rgba(2, 12, 20, 0.6);
         backdrop-filter: blur(24px) saturate(180%);
         border: 1px solid rgba(255, 255, 255, 0.1);
         border-radius: 20px;
     }

     // Accesibilidad y respaldo.
     @media (prefers-reduced-transparency: reduce) {
         .navbar.fixed-top, .drawer, .dropdown-menu, .modal-content, .toast,
         #page-login-index .login-container, #page-login-index .loginform {
             background-color: #051623 !important;
             backdrop-filter: none;
         }
     }

     @media (prefers-reduced-motion: reduce) {
         *, ::before, ::after {
             transition-duration: 0.01ms !important;
             animation-duration: 0.01ms !important;
         }
     }
     ```
4. Guardar y **purgar cachés**: *Administración del sitio › Desarrollo › Purgar cachés* (o `php admin/cli/purge_caches.php` en el servidor de Moodle).

**Comprobación:** fondo de montañas oscuro, logotipo claro arriba, botones dorados, barra superior y menús translúcidos con desenfoque, y contenido de cursos sobre paneles sólidos legibles.

> Los selectores de Moodle cambian entre versiones (por ejemplo `.drawer` o `.login-container`). Si una regla no aplica, inspecciona el HTML de esa versión y ajusta el selector; no agregues `!important` a ciegas.

### 4.3 Opción B: tema propio `theme_daybridge` (recomendada a largo plazo)

Un tema hijo de Boost que se instala como plugin y se versiona con el código. Solo es posible si hay acceso a los archivos del servidor de Moodle (**no** en MoodleCloud).

Estructura (dentro de la carpeta de Moodle, en `theme/daybridge/`):

```text
theme/daybridge/
├── config.php
├── version.php
├── lib.php
├── lang/en/theme_daybridge.php
├── lang/es/theme_daybridge.php
├── scss/pre.scss      ← variables de la opción A (SCSS inicial en bruto)
├── scss/post.scss     ← reglas de la opción A (SCSS en bruto)
└── pix/
    ├── favicon.png
    └── screenshot.png
```

`version.php`:
```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'theme_daybridge';
$plugin->version   = 2026111100;
$plugin->requires  = 2022041900; // Moodle 4.0 o superior.
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0';
$plugin->dependencies = ['theme_boost' => 2022041900];
```

`config.php`:
```php
<?php
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'daybridge';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->usefallback = true;
$THEME->scss = function ($theme) {
    return theme_daybridge_get_main_scss_content($theme);
};
$THEME->prescsscallback = 'theme_daybridge_get_pre_scss';
$THEME->extrascsscallback = 'theme_daybridge_get_extra_scss';
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
```

Los layouts se heredan de Boost por `$THEME->parents`; no hace falta copiarlos.

`lib.php`:
```php
<?php
defined('MOODLE_INTERNAL') || die();

function theme_daybridge_get_main_scss_content($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
}

function theme_daybridge_get_pre_scss($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/daybridge/scss/pre.scss');
}

function theme_daybridge_get_extra_scss($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/daybridge/scss/post.scss');
}
```

`lang/es/theme_daybridge.php` (y una copia en `lang/en/` con el texto en inglés; Moodle exige el idioma inglés):
```php
<?php
$string['pluginname'] = 'Daybridge';
$string['choosereadme'] = 'Tema oscuro con la identidad visual de Daybridge.';
```

Instalación:

1. Copiar `theme/daybridge/` al servidor de Moodle.
2. Entrar a Moodle como administrador: detecta el plugin nuevo y pide actualizar la base de datos. Aceptar.
3. *Administración del sitio › Apariencia › Selector de temas*: elegir **Daybridge**.
4. Subir logotipos y favicon como en el paso 1 de la opción A, y cargar la fuente como en el paso 2.
5. Purgar cachés.

Para iterar el diseño, activa temporalmente *Desarrollo › Modo diseñador de temas* (recompila el SCSS en cada carga). **Desactívalo** al terminar porque hace lento el sitio.

Si antes aplicaste la opción A, **vacía** los campos de SCSS de Boost para no duplicar reglas.

### 4.4 Comprobación visual (en ambas opciones)

Revisa estas páginas en escritorio (1366 px) y celular (390 px):

1. Inicio de sesión.
2. Área personal (tarjetas de cursos con barra de progreso).
3. Página de un curso (índice lateral y contenido).
4. Un foro y un cuestionario (texto largo y formularios legibles sobre fondo sólido).
5. Calificaciones (tabla legible).
6. Menú de usuario y una ventana modal (vidrio con desenfoque).

Criterios:
- Texto con contraste de al menos 4.5:1 (3:1 en títulos grandes).
- Nada desborda horizontalmente en el celular.
- Con "reducir transparencia" del sistema activado, todo se lee sobre fondos sólidos.
- **Editores de texto (TinyMCE/Atto):** si el texto escrito se ve oscuro sobre oscuro, deja el área del editor en claro (`background:#FFFFFF; color:#02080F`) y documenta la excepción.

---

## 5. Lista final

- [ ] `php artisan test`: todas las pruebas en verde.
- [ ] `npm run build` sin errores.
- [ ] La app abre en `APP_URL` y desde otro equipo de la red.
- [ ] `php artisan moodle:sync-users` vincula las cuentas.
- [ ] `/capacitacion` muestra cursos reales.
- [ ] Moodle muestra el diseño de Daybridge en las 6 páginas de la sección 4.4.
- [ ] `.env` no se ha compartido ni subido a ningún repositorio.
