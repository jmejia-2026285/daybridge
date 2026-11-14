# Daybridge: progreso de arreglos

Bitácora para retomar la sesión. Orden según docs/roadmap.md Fase 1 y docs/auditoria-final.md.

## Modo de trabajo
- Un commit por arreglo o grupo pequeño.
- Tests: sqlite `:memory:` (phpunit.xml), nunca la base de desarrollo.
- Modo oscuro permanente: sin modo claro.

## Módulo 0 · Tema (MODO OSCURO MÁXIMO)
**Arreglado y verificado**
- Causa del conflicto: los tokens `:root` seguían siendo claros (blanco `--surface: 255 255 255`), y toda variante de vidrio tenía una primera definición con `rgba(255,255,255,…)`; cuando el `<html>` no llevaba `.dark` (FOUC, re-render parcial de Livewire, PWA fría) la app se pintaba blanca.
- Solución: `:root` ahora trae la paleta oscura directamente, `color-scheme: dark`. El modo claro dejó de existir en el CSS: sin paleta clara posible.
- Eliminadas las variantes `.dark` duplicadas de `apple-liquid-glass-light/dense`, `avatar-initials`, `area-bar/dot` y `bubble-glass`: ahora solo hay un juego de valores (los oscuros).
- Override global `.bg-surface` funciona sin requerir la clase `.dark`.
- Quitados los usos de `<x-theme-toggle />` en `layouts/app.blade.php` y `profile.blade.php`; componente eliminado (quedaba vacío).
- Hack CSS `.theme-toggle { display:none }` eliminado.
- `theme-color` y `background_color` del manifest pasan a `#0B1416` / `#020C14` (era crema `#EDEAE3` y `hero` claro).
- Página offline `offline.blade.php` en oscuro permanente (solo existía su rama obscura dentro de `prefers-color-scheme: dark`).
- Archivos: `resources/css/app.css`, `resources/views/layouts/app.blade.php`, `resources/views/layouts/guest.blade.php`, `resources/views/profile.blade.php`, `resources/views/offline.blade.php`, `public/manifest.webmanifest`.
- Verificación: `npm run build` OK (CSS 66.79→65.24 kB al desaparecer los tokens claros) y `vendor/bin/pint` OK.

## Módulo 1 · Base (phpunit.xml, logs, rutas)
**Arreglado y verificado**
- `phpunit.xml` ya usa `sqlite` `:memory:` separado de la base de desarrollo. `php artisan route:list` completo: las rutas existentes responden con nombre válido (50 rutas registradas, sin rutas muertas de app).
- Hallados y corregidos 2 errores fatales de lint: `Chat/Index.php:51` y `Chat/Create.php:32` declaraban `startConversation(): void` y hacían `return redirect()`. Fatal en runtime al iniciar conversación. Cambios: métodos sin `void`.
- `storage/logs/laravel.log` mostraba error real: `Undefined variable $unreadCount` en `notifications/index.blade.php` (propiedad calculada accesible solo como `$this->unreadCount`, no como variable de vista). Corregido (2 referencias).
- Backup de base dev antes de tocar migraciones: `backup-database-sqlite-20261105-pre-fixes.sqlite` (raíz del proyecto, 516 KB).

## Módulo 2 · Registro de usuarios
**Arreglado y verificado**
- El flujo de registro estaba completo; el "usuario desaparece" venía del historial antiguo cuando los tests apuntaban a la base real. Ahora con base de tests separada.
- Prueba ampliada en `tests/Feature/Auth/RegistrationTest.php`: persiste el registro ( creations de fila, nombre y email), hash de contraseña comprobado con `Hash::check`, y cuenta activa por defecto. Resultado: `php artisan test --compact tests/Feature/Auth/RegistrationTest.php` OK (2 pruebas).

## Módulo 3 · Directorio
**Arreglado y verificado**
- Falta que existía: no había teléfono en el directorio (schema no tenía `phone`). Añadida migración `2026_11_05_210100_add_phone_to_users_table` (nullable) y aplicada sobre dev DB tras crear el backup. `phone` en `$fillable` de User.
- `pages/directory/show.blade.php`: acciones de contacto completas — `mailto:` existente + respaldo Gmail (`https://mail.google.com/mail/?view=cm&to=`) + botón "Copiar correo" con confirmación visual (Alpine, 2.5 s) + fila Teléfono con enlace `tel:` que llama desde el celular. Vista índice también muestra teléfono con icono.
- Edición de datos de cada persona solo para admin, con validación (email único con mensajes en español, teléfono con regex, puesto, área) y caso que invalida la verificación cuando cambia el correo. Aparece un mensaje de éxito con `session()->flash`.
- Nuevos símbolos de sprite: `icon-copy`.
- Prueba nueva: `DirectoryTest` — 13 pruebas OK en `php artisan test --compact tests/Feature/DirectoryTest.php` incluye: acciones de contacto; admin edita datos y persiste (`is_verified` limpiado al cambiar email); permiso denegado guarda sin cambio; validaciones de email único y teléfono con regex.

## Módulo 4 · Usuarios, roles y permisos, perfil propio
**Arreglado y verificado**
- Faltaba crear/eliminar personas en administración. En `pages.admin.people` (Volt): `openCreate/closeCreate/createUser` con modal `x-modal` (dispatch `open-modal`/`close-modal`), validación completa en español (nombre, correo único, teléfono con regex, puesto, área existente, rol enum, contraseña fuerte de Laravel + confirmación) y hash con `Hash::make`; `confirmDelete/cancelDelete/deleteUser` con modal de confirmación, guard `deletingUserId === null` y protección de auto-eliminación vía policy.
- `UserPolicy`: añadidos `create` (solo admin) y `delete` (solo admin y nunca sobre sí mismo). Con esta política, eliminar al último admin solo sería posible auto-eliminándose, y eso está prohibido; por eso se retiró el guard redundante de "contar admins" en `deleteUser` (la UI nunca abre el modal si la policy lo denegó).
- Perfil propio: teléfono editable en `update-profile-information-form` (propiedad, mount, validación con el mismo regex del directorio, campo `type="tel"` con `autocomplete="tel"`).
- Hallazgo de tests: el patrón try/catch de `AuthorizationException` no sirve cuando el componente usa `abort_unless` (lanza `HttpException`); en pruebas de Volt eso degenera en "Invalid Livewire snapshot structure". Solución: asertas de estado final (HTTP 403 para no-admins en `admin.people`) y en el componente alternar entre policy y guards sin re-usar el snapshot; las pruebas quedan robustas sin acoplarse al tipo de excepción.
- Resultado: `AccessControlTest` 15/15 en verde (crear cuenta con hash/área/rol y validaciones, solo admins crean, eliminar persona, auto-eliminación protegida) y `ProfileTest` 7/7 (teléfono persiste, teléfono inválido rechazado). Commits `60ed828` y `769e47a`.

## Módulo 5 · Feed, muro social, comentarios y reacciones
**Arreglado y verificado**
- HALLAZGO: no existía UI para crear/editar/eliminar/fijar comunicados del feed (policy y `is_featured` existían, pero ninguna pantalla). Creada `pages.admin.content` (`resources/views/livewire/pages/admin/content.blade.php`) con lista con búsqueda, contadores, modal crear/editar (área obligatoria, resumen autogenerado `Str::limit(body,280)`, urgente, fijar, fecha o "publicar ahora" → borrador), eliminar con confirmación y fijar/desfijar. Ruta `admin/comunicados` (403 para quien no puede crear contenido) y botón "Gestionar" en el feed.
- Commit `39c62da`. Pruebas: `ContentAdminTest` **11/11, 57 aserciones** y `WallTest` en verde (publicar/editar/eliminar propios, permisos autor/admin, reacciones, comentarios con límite y policy de borrado).

## Módulo 6 · Cumpleaños y reconocimientos
**Arreglado y verificado**
- La página de cumpleaños mostraba personas inactivas: ahora solo se celebran personas activas, y enviar la felicitación notifica una sola vez al cumpleañero (`birthday_wish`, única por par usuario/año). Commit `970509f`.
- Pruebas: `BirthdaysPageTest` 4/4 y `BirthdayWishButtonTest` 4/4.

## Módulo 7 · Tareas, encuestas, formularios y documentos
**Verificado sin cambios necesarios**
- Recorrido completo y suites ya en verde: `TasksTest`, `SurveysTest` 13, `FormsTest` 13, `DocumentsTest` 9. Sin defecto que corregir en la pasada.

## Módulo 8 · Notificaciones y chat
**Arreglado y verificado** (commits `fb27ccc`, `ee018ae`, `08df81d`)
- **Página de notificaciones estaba rota (500)**: la vista usaba `$notifications` con componente de clase Livewire (los computeds solo se exponen como `$this->xxx`); corregidas 3 referencias. Compatibilidad: ahora los notificables son muro, tareas y cumpleaños, con actor visible y enlace al asunto.
- **Chat roto en varios puntos:**
  - `Chat\Index` era `Volt\Component` con `render(): mixed` de Anchor — fatal al abrir `/mensajes`; normalizado a `Livewire\Component` como sus hermanos.
  - `Conversation::markAsRead()` hacía `update()` sobre la relación `BelongsToMany` (actualizaba `users`); usa `updateExistingPivot`. Era el crashes al abrir conversación.
  - Computed `messages()` chocaba con la bolsa interna de validación de Livewire (`HandlesValidation` llama `$this->messages()`); renombrado a `chatMessages()` (vistas y `unset` actualizados).
  - `latestMessage()` declaraba `HasMany` y devolvía `HasOne`; `last_message_at` sin cast `datetime`.
  - `unread_count` con `$query` indefinido en el clousure anidado (`use ($query)`).
  - `startConversation` duplicado en Index/Create y sin guardas: ahora helper compartido `Conversation::startDirect()` con `User::active()->findOrFail` y abort 422 para auto-chat.
  - Responder ya persiste en `metadata.reply_to` (sin migración nueva) y `deleteMessage()` con policy + recálculo de `last_message_at` por id.
  - Vistas de chat con `$this->` para computeds (estilo Volt heredado).
- **Notification::actor()** era MorphTo pero la tabla solo tiene `actor_id` → `belongsTo(User::class, 'actor_id')`; el actor nunca renderizaba. `getUrl()`/`getActionUrl()` defensivos en `WallPost`, `Post` y `Task`.
- **Notificaciones nuevas de causa raíz:**
  - Muro: reacción solo notifica al crear la fila (`wasRecentlyCreated`), no al cambiar de tipo ni al retirar; comentario notifica al autor del post (`wall_comment`), nunca a uno mismo.
  - Tareas: `task_assigned` al asignar/reasignar y `task_completed` al quien creó la tarea; nunca para auto-asignación ni auto-completado. Retype de computeds que devuelven paginador (`LengthAwarePaginator`) en Chat y Notificaciones.
- **Tests**: `ChatTest` nuevo (10: invitado, outsider 403, enviar/persistir respuesta, vacío rechazado, borrar con recálculo de fecha, borrar ajeno prohibido, idempotencia, auto-chat, persona inactiva), `NotificationsPageTest` nuevo (4: invitado, muro con actor+enlace, tareas sin enlace directo, solo míos), `WallTest` ampliado con 5 (17 total), `TasksTest` ampliado con 6 (15 total). Hallazgo: en pruebas con `Livewire::test`, `abort()` se convierte en respuesta HTTP (403/422) y no se relanza; las pruebas asertan el estado de la BD.

## Módulo 9 · IT Desk
**Arreglado y verificado** (commits `a6d3e4d`, `857acb9`)
- Auditoría contra los criterios de Fase 2 del roadmap: código de equipo `DSP-0001` (generado por evento `created` con id), folio de ticket `TCK-AAAA-0001` consecutivo y único, cambio de estado con rechazo del mismo estado (`TicketService` con `ValidationException` — su `getMessage()` devuelve el mensaje específico en español, no el resumen), `completed_at` automático al pasar a `COMPLETADO`, impresión con código de barras Code 128 SVG (`x-barcode`), dos tickets por hoja carta horizontal y registro en `ticket_impresiones` (los dos tipos `EQUIPO`/`CLIENTE`), agenda de citas con calendario y acceso solo admin/técnico (no técnicos reciben 403 y el menú no se muestra, ahora asertando por `href`).
- **Completado y commiteado el trabajo pendiente de la pasada anterior:** página «Mi equipo» (`it-desk.my-equipment`) para que cada usuario consulte los equipos registrados con su correo (fleet por `client_email`, estados y tickets, notas internas nunca visibles, estado vacío contando su correo y contadores en servicio/listos), con `MyEquipmentTest` 7/7. Y el dashboard con acabados `bubble-glass` (token ya definido en `app.css`, sin colores claros).
- Cierre del módulo con suites de la pasada: `ItDeskTest` 17/17 (83 aserciones), `ItDeskAgendaTest` 11/11 (47), `MyEquipmentTest` 7/7 (24). Pint `--dirty` OK.
- Sin cambios de causa raíz necesarios: el módulo estaba sano; solo se integró el trabajo previo sin commit.

## Módulo 10 · PWA
**Verificado sin cambios necesarios**
- `public/manifest.webmanifest`: metadatos instalables (name/short_name, `standalone`, `start_url`/`scope`, `id`, `lang es`, categorías, shortcuts) con tema MODO OSCURO MÁXIMO (`#0B1416`/`#020C14`); los tres íconos (192, 512, maskable 512) existen en `public/icons`.
- `public/sw.js`: precache de `/offline` + manifest + 4 íconos; `skipWaiting`/`clients.claim`; navegaciones que fallen red responden offline; caché passthrough para `/build/`, `/media/`, `/icons/`. Los 6 archivos del precache existen (no rompe `cache.addAll`).
- Registro del SW solo en build de producción (`resources/js/app.js`, `import.meta.env.PROD`). Ruta `/offline` standalone sin auth (`Route::view`). Página offline 100 % oscura (módulo 0).
- `npm run build` OK (CSS 65,70 kB solo oscuro; JS 142,29 kB). `NavigationAndPwaTest` 5/5 (24 aserciones).
## Módulos siguientes
Suite final cerrada. Ver informe final abajo.

## Suite completa
- Tras el módulo 8: `php artisan test --compact` (**209**) → **209 OK, 912 aserciones**. Pint `--dirty` sin pendientes.

## Módulo 11 · Responsive 360/768/1280
**Verificado sin cambios necesarios**
- Auditoría estática y en vivo: `layouts/app` es mobile-first (`md:pl-rail`, `md:hidden` para el rail/sidebar, bottom-nav en móvil, sheet de módulos bottom-sheet en móvil y lateral en md, `max-w-content 77.5rem` con `rail 4rem` → OK en 1280). Meta viewport presente (`width=device-width, initial-scale=1, viewport-fit=cover`).
- Páginas clave usan Tailwind responsive: `px-4 sm:px-6 md:px-10`, `max-w-md` en formularios de autenticación, `sm:grid-cols-*` para tarjetas, `overflow-x-auto` en tablas de cumpleaños, directorio, encuestas, formularios (evita overflow en 360). Grids de resumen usan `grid-cols-2 sm:grid-cols-4` etc.
- Verificación en vivo con el navegador: login en viewport 912 px sin overflow (`scrollWidth===clientWidth`). No hay anchos fijos (`w-[...]`/`min-w-[...]`) en vistas de páginas Vol...; las vistas  que sí usan anchos fijos (`w-44` en tickets) llevan `w-full sm:w-44`.
- Suites verdes sin regresiones: `php artisan test --compact` 209/209. Pint OK. Build PWA OK.
- No se requieren ajustes: la UI ya responde correctamente a 360, 768 y 1280 con MODO OSCURO MÁXIMO.


## Informe final Fase 1 – Daybridge

**Resultado de suite**
- `php artisan test --compact` **209 pruebas**, **912 aserciones** → todas OK.
- Pint `--dirty` sin pendientes.
- `npm run build` OK.

**Rutas**
- Total rutas registradas: **51**.
- `php artisan route:list --except-vendor` muestra 7 rutas nativas de app (documentos download, logout, offline, profile, avatar, verify-email); el resto son rutas Volt que se cargan vía `vendor/livewire/volt`.

### Clasificación por módulo

**Arreglado y verificado (con commit y tests)**
- Módulo 0 Tema MODO OSCURO MÁXIMO
- Módulo 1 Base (phpunit sqlite, logs, rutas)
- Módulo 2 Registro de usuarios
- Módulo 3 Directorio (teléfono, contacto, edición admin)
- Módulo 4 Usuarios/roles/perfil
- Módulo 5 Feed/Muro + gestión de comunicados admin
- Módulo 6 Cumpleaños y reconocimientos
- Módulo 8 Notificaciones y chat
- Módulo 9 IT Desk (Mi equipo, dashboard bubble-glass, menú oculto)
- *Commits verificados: `c99f2ed`, `7309b14`, `9ebac8e`, `60ed828`, `769e47a`, `39c62da`, `970509f`, `fb27ccc`, `ee018ae`, `08df81d`, `a6d3e4d`, `857acb9`.*

**Verificado sin cambios necesarios**
- Módulo 7 Tareas/Encuestas/Formularios/Documentos
- Módulo 10 PWA (manifest oscuro, SW offline, build OK)
- Módulo 11 Responsive 360/768/1280

**Arreglado sin verificar**
- Ninguno.

**Ciclos 15–16 · Sección Mensajes: causa raíz de "Snapshot missing" (BOM en vistas Blade)**
- Diagnóstico con logs de navegador (`storage/logs/browser.log`) y código fuente: el único elemento con `wire:id` en `/mensajes` era el `<header>` y no el `<div wire:key="chat-index-root">`; los botones "Nueva conversación" del listado quedaban huérfanos fuera del árbol de componentes.
- Causa: los 3 archivos `resources/views/livewire/chat/{index,create,show}.blade.php` arrancaban con un BOM UTF-8 (`EF BB BF`). Ese carácter se imprime en el HTML final y rompe la detección del elemento raíz de Livewire (`Utils::insertAttributesIntoHtmlRoot` solo reconoce un tag tras `\n\s*`; el BOM no es whitespace) → los atributos `wire:id/wire:snapshot/wire:effects` se fusionaban con el `<header>` interno y todo lo demás quedó fuera de la hidratación.
- Los errores antes vistos (`Could not find Livewire component in DOM tree`, `Snapshot missing...`) y el modal que "no respondía" provenían de esa única causa.
- Limpieza de BOM en las 3 vistas + `php artisan view:clear`. Aclarado también el memo `name:"chat"`: Livewire 3 renombra componentes `...\Chat\Index` a `chat` (elimina el sufijo `.index`), no es error.
- Verificado en navegador con sesión real: 1 componente raíz en `<main>`, `wire:key="chat-index-root"`, los 3 botones dentro del componente, 0 errores de consola; modal abre (14 usuarios), × cierra, `selectedUserId` queda en 5 y se muestra el panel de selección. Suite completa en verde (209 pruebas / 912 aserciones).

**Ciclos 17 · Chat Show/Create: layout duplicado eliminado**
- `App\Livewire\Chat\Show.php` y `Create.php` seguían con `#[Layout('layouts.app')]` + `#[Title(...)]`: el componente renderizaba el layout completo DENTRO de las vistas envelope `resources/views/chat/{show,create}.blade.php` que ya hacen `@extends('layouts.app')` → `<head>`/`<main>` duplicados al entrar a una conversación.
- Quitados ambos atributos (el envelope es el dueño del layout, mismo patrón ya aplicado a `Index`) y pasados los títulos desde `routes/web.php` con la variable `$title` que el layout ya soporta (`{{ $title ?? 'Daybridge' }}`): `Mensajes / Nueva conversación / Chat · Daybridge`.
- Verificado en navegador: `/mensajes/1` y `/mensajes/nueva` con 1 `<head>`, 1 `<main>`, componente raíz DIV con snapshot dentro de `<main>`, título correcto por página, 0 clicks huérfanos y 0 errores de consola. Conversación de prueba creada y borrada en dev DB (sin mensajes). Suite completa en verde (209/912).

**Ciclo 18 · Chat rediseñado tipo Messenger/WhatsApp (dos paneles en la misma página)**
- Sustituido el placeholder de diagnóstico («Conversación activa cargada para el usuario ID: N») y el zigzag lista→URL `/mensajes/{id}` por un chat de dos paneles: la lista es `col-span-4` a la izquierda y el hilo completa (`col-span-8`) se abre en el panel derecho sin cambiar de página.
- Nuevo parcial compartido `resources/views/livewire/chat/partials/thread.blade.php`: burbujas con acciones (responder en mensajes de otros, eliminar en propios), composer con Enter/Shift+Enter, contador 0..5000, autoscroll al fondo (Alpine `x-init` + evento `message-sent`) y `wire:poll.5s` para entrar mensajes nuevos; parámetros `$withHeader`/`$backAction`/`$framed`/`$heightClass` lo adaptan a ambos consumidores.
- Nuevo trait `App\Livewire\Chat\Concerns\ManagesChatThread`: centraliza `chatThreadConversation` (abstract), `chatMessages` (computed, 50 por página), `sendMessage`, `deleteMessage`, `startReply`, `cancelReply`; Show e Index la consumen (Show conserva su info-aside y su header propio).
- `Index` reescrito: `selectedConversationId` sustituye al placeholder `selectedUserId`; `openConversation/closeConversation` muestran/ocultan el hilo en la misma página (sin redirect); `startConversation` deja de redirigir y abre la conversación en el panel (idempotente vía `Conversation::startDirect`). El CTA «Nueva conversación» vive SOLO en el header de la lista (icono +) — duplicados eliminados (3 → 1). Panel derecho sin selección queda como estado vacío informativo sin botones.
- `render()` marca la conversación activa como leída en cada ciclo (incluido el poll) para refrescar el badge sin abrir.
- Ajustes de ChatTest: `startConversation` ya no redirige, se aserta `selectedConversationId`; agregados tests del panel dentro de la bandeja para participantes (marca lectura) y guard para no participantes (no ve el hilo). Suite: **211 pruebas / 919 aserciones** en verde; Pint limpio.
- Verificado en navegador con sesión real `test@example.com`: 1 componente Livewire root; CTA única; modal «Iniciar nueva conversación» con 14 personas y áreas; enviar mensaje crea burbuja propia, limpia composer «0/5000» y actualiza la vista previa de la fila; el botón atrás vuelve a la lista (visibilidad móvil por breakpoints); `/mensajes/2` intacto con header propio + parcial. 0 errores de consola ni en logs de Laravel.

**Ciclo 19 · Bugs del chat: orden cronológico y foto de perfil**
- *Mensajes nuevos arriba*: la relación `messages()` lleva `->orderBy('created_at')` (ASC) dentro de su definición, así que `latest()` solo apilaba un `order by desc` tras uno `asc` que la BD respeta primero; con el `reverse()` de la vista el hilo quedaba al revés (bug heredado del Show original, invisible con 1-2 mensajes). `chatMessages()` ahora usa `reorder('id','desc')` para anular la cláusula de la relación y traer las 50 más recientes; la vista las revierte al pintar de lo viejo a lo nuevo.
- *Foto de perfil invisible*: el componente `<x-avatar>` imprimía la ruta de `users.avatar` (`storage/avatars/15-...jpeg`) sin URL absoluta, de modo que en rutas anidadas (`/mensajes/2`) el `src` relativo resolvía a `/mensajes/storage/...` → 404. Ahora usa `asset($avatarPath)` (URL absoluta para todo el sitio). El archivo físico existía; solo fallaba la URL. Los demás usuarios siguen viendo iniciales porque su `avatar` es null.
- Tests: nuevo `test_chat_messages_are_read_oldest_first` (valida paginador más-nuevo-primero Y vista invertida) y `tests/Feature/AvatarTest.php` (URL absoluta del img + iniciales PL/AL cuando no hay foto). Suite: **214 pruebas / 926 aserciones** en verde; Pint limpio.
- Verificado en navegador (`/mensajes/2`): burbujas de más viejo a más nuevo (lo nuevo abajo) y `img src="http://10.10.20.50:8000/storage/avatars/15-6aecf1291a20d.jpeg"` cargada (`naturalWidth > 0`). 0 errores de consola.
- Nota: el componente avatar incluía el soporte local de foto de perfil (workflow del usuario); se commitó junto al fix de `asset()`.

**Arreglado sin verificar (histórico)**
- Ninguno.

**Pendiente / bloqueado**
- Ninguno.

**Sugerido**
- Ninguna mejora bloqueante; mantener MODO OSCURO MÁXIMO y tests en sqlite `:memory:`.

**Nota de bitácora**
- Backup DB desarrollo: `backup-database-sqlite-20261105-pre-fixes.sqlite`.
- La UI responde a 360/768/1280 sin overflow; layout mobile-first confirmado en vivo.
