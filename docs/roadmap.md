# Daybridge: roadmap por fases

Plataforma interna de equipo (comunicación, operación y engagement) con IT Desk integrado. Laravel + Blade + Vite, instalable como PWA en PC y celular.

> **Diseño:** ya está definido en `docs/direccion-visual.md` y en los componentes existentes. Este documento no define ni cambia estilos. Toda pantalla nueva reutiliza los componentes, tokens y patrones que ya existen.

---

## 0. Reglas para quien implemente (leer antes de cada fase)

1. **No rediseñar ni refactorizar lo que ya funciona.** Solo agregar o completar.
2. **Antes de crear una tabla, modelo, ruta o componente, buscar si ya existe.** Reutilizar y extender; no duplicar.
3. **Base de datos:** nombres de tablas y columnas en minúsculas y snake_case. Las tablas de este documento son una propuesta: adaptarlas a lo que ya existe en `database/migrations`.
4. **Nunca ejecutar `migrate:fresh`, `migrate:refresh` ni `db:seed` sobre la base de desarrollo sin avisar.** Las pruebas usan una base separada (verificar `phpunit.xml`).
5. **Autorización:** Policies o Gates en todo lo que crea, edita o elimina. Solo el autor o un admin modifica contenido ajeno.
6. **Validación:** Form Requests con mensajes en español y errores visibles junto a cada campo.
7. **Eliminar:** siempre con confirmación (modal) y mensaje de éxito tras cada acción. Preferir borrado lógico (`deleted_at`) en contenido de usuarios.
8. **Cada pantalla es responsive** (360, 768 y 1280 px) y funciona con teclado y en celular (botones táctiles de mínimo 44 px).
9. **Todo lo que se guarda debe persistir al recargar.** Probar de verdad cada acción.
10. **Un módulo a la vez, un commit por módulo,** con resumen de 5 líneas: qué se agregó, archivos tocados y qué queda pendiente.
11. **Pruebas:** al menos crear, editar, eliminar y permiso denegado por cada recurso nuevo.
12. **Si algo es una mejora y no parte de la fase, anotarlo en una lista aparte** y no hacerlo.
13. **Si una decisión cambia estructura, rutas o la forma de trabajo, preguntar antes.**

### Prompt de arranque (pegar en OpenCode, cambiando el número de fase)

```
Lee docs/roadmap.md completo y trabaja solo en la FASE N. Respeta la sección 0 (reglas). Antes de escribir código, entrégame un plan corto con las tablas, rutas y pantallas que vas a crear o a reutilizar, y espera mi OK. Después implementa un módulo a la vez, con pruebas y un commit por módulo. Al terminar, dame un informe con tres listas: hecho, pendiente y sugerido.
```

---

## Convenciones técnicas

- **Roles:** `admin`, `editor`, `usuario`, más el permiso `tecnico` para IT Desk. Se resuelve con roles/permisos, no con tablas de login separadas.
- **Grupos:** usuarios pertenecen a grupos (área, equipo, proyecto). Los grupos sirven para segmentar publicaciones, tareas y notificaciones.
- **Segmentación de contenido:** todo contenido publicable tiene `alcance` (todos, grupo, usuario) y se filtra en consulta, no solo en la vista.
- **Notificaciones:** sistema de notificaciones de Laravel (canal base de datos); los demás canales (push, correo) se agregan en la Fase 4.
- **Tareas programadas:** scheduler de Laravel para recordatorios, cumpleaños y reglas por tiempo.
- **Archivos:** disco `public` con `storage:link`; límites de tamaño y tipos permitidos validados.
- **Auditoría:** registrar quién crea, edita o elimina en módulos sensibles (IT Desk, usuarios, roles).

---

## Fase 1: Base y cierre de brechas

**Objetivo:** que todo lo ya construido esté completo, estable y con permisos correctos antes de sumar módulos.

### 1.1 Auditoría (solo leer y probar, sin modificar)
Tabla con una fila por módulo y columnas: crear, ver, editar, eliminar, buscar/filtrar, permisos por rol, funciona en celular. Cada celda es `OK`, `FALTA` o `ROTO`, con la causa o el archivo en una línea. **Detenerse y esperar aprobación antes de corregir.**

Módulos a auditar: feed, muro social, cumpleaños y reconocimientos, directorio, tareas, encuestas, manuales y documentos, formularios, chat, notificaciones, usuarios/roles, perfil propio, IT Desk, PWA.

### 1.2 Completar lo que falte (mínimos por módulo)
| Módulo | Mínimo |
|---|---|
| Feed y muro | Crear, editar y eliminar publicaciones propias (admin con todas); fijar; comentarios y reacciones que se puedan quitar |
| Cumpleaños | Editar fecha de nacimiento; enviar o quitar felicitación |
| Directorio | Editar datos (nombre, puesto, área, correo, teléfono, foto), desactivar o eliminar, búsqueda y filtro por área. Botón "Escribir correo" con `mailto:` más respaldo a Gmail web (`https://mail.google.com/mail/?view=cm&to=CORREO`) y botón "Copiar correo" |
| Usuarios y roles | Admin crea, edita, cambia rol, activa/desactiva y elimina; cada usuario edita su perfil, foto y contraseña |
| Notificaciones | Marcar como leída, marcar todas, eliminar |
| Manuales y documentos | Crear carpeta, subir, renombrar, mover, eliminar |

### 1.3 Errores conocidos
1. **El usuario registrado desaparece.** Revisar `DB_CONNECTION` y `DB_DATABASE`, confirmar que `phpunit.xml` usa base separada para tests, y verificar ruta, controlador, `fillable` y que se guarda. Causas probables: `migrate:fresh` o `db:seed` ejecutados por error, tests contra la base real, o registro que no guarda.
2. **Quitar datos de ejemplo visibles** (por ejemplo `test@example.com`) donde no correspondan.
3. Las fotos de perfil ya funcionan: no tocarlas.

### 1.4 PWA
- `manifest.webmanifest` con nombre, `short_name`, iconos 192/512 y *maskable*, `theme_color`, `background_color`, `display: standalone`, `start_url`.
- Service worker mínimo: caché de assets y página offline simple.
- Meta viewport correcta y respeto de *safe-area* en celulares con muesca.
- "Agregar a pantalla de inicio" debe funcionar. Probar con `npm run build` (no con Vite en modo desarrollo) y revisar la pestaña *Application* de las herramientas del navegador.

**Criterios de aceptación**
- [ ] Tabla de auditoría entregada y aprobada.
- [ ] Ningún botón sin acción, enlace muerto, 404 o 500 en los módulos auditados.
- [ ] Cada recurso permite crear, editar y eliminar con permisos correctos.
- [ ] Registro de usuario persiste y los tests no tocan la base de desarrollo.
- [ ] La app se puede instalar como PWA y se ve bien en 360, 768 y 1280 px.

---

## Fase 2: IT Desk completo

**Objetivo:** gestión de soporte técnico e inventario dentro de Daybridge. Proviene de un proyecto Java de escritorio (`it-desk-tracker`, Java + SQLite); se replica la **lógica**, no el código.

### Entidades y reglas

**Equipos (`equipos`)**
- Campos: tipo, marca, modelo, serie, cliente, teléfono, correo, problemática, diagnóstico, accesorios, notas, imagen, estado, fecha estimada de entrega, técnico que recibe (usuario).
- Código interno con formato `DSP-0001` (consecutivo, único).
- Crear, editar todos los campos, ver historial y eliminar (borrado lógico).

**Tickets (`tickets`)**
- Ligados a un equipo. Folio único con formato prefijo-año-consecutivo.
- Descripción de máximo 255 caracteres.
- Estados: `PENDIENTE`, `EN_PROCESO`, `COMPLETADO`. **No se permite cambiar al mismo estado en que ya está.**
- Técnico asignado, fecha de ingreso, fecha de completado (se registra al pasar a `COMPLETADO`).
- Control de impresión: `impreso` y fecha de impresión.

**Impresiones (`ticket_impresiones`)**
- Un registro por impresión: ticket, tipo (`EQUIPO` o `CLIENTE`), usuario que imprimió, fecha.

**Citas (`citas`)**
- Por técnico: título, descripción, fecha, hora, `completada`.
- Crear, editar, completar y eliminar. Vista de calendario (mes y semana) y contador de citas de hoy.

**Técnicos**
- Son usuarios de la app con el permiso `tecnico` y un indicador de activo. No hay tabla de login aparte.

### Pantallas
- Dashboard IT: contadores de equipos pendientes, en proceso, completados y citas de hoy, con accesos rápidos.
- Lista de equipos con búsqueda y filtros (estado, técnico, cliente, fechas). Ficha de equipo con sus tickets e historial.
- Lista y detalle de tickets con cambio de estado y asignación de técnico.
- Calendario de citas.

### Impresión
- **Ticket de equipo:** incluye el código de barras Code 128 del folio.
- **Comprobante de cliente:** datos del cliente, falla, accesorios, diagnóstico, técnico que recibe y leyenda de conservar el comprobante.
- Hoja carta horizontal con **dos tickets por hoja**, usando una vista Blade con CSS `@media print`. Evitar librerías pesadas; el código de barras puede generarse en SVG.
- Cada impresión se registra en `ticket_impresiones`.

### Referencia del proyecto Java
Para entender la lógica original sirven: `model/`, `service/`, el esquema en `DB/DatabaseConnection.java`, el método que genera el folio en `TicketRepository` y la maqueta y el Code 128 en `utils/TicketUtils.java`. Los controladores y los `.fxml` no sirven. Si existe `docs/it-desk-especificacion.md`, es la fuente preferida.

**Criterios de aceptación**
- [ ] Un equipo se crea, edita y elimina con todos sus campos y código `DSP-0001`.
- [ ] Un ticket genera folio único; el estado no se puede repetir y la fecha de completado se guarda sola.
- [ ] Un ticket se imprime en ambas versiones con código de barras legible, dos por hoja, y queda registrado.
- [ ] Las citas se crean, completan y se ven en el calendario por técnico.
- [ ] Solo `admin` y `tecnico` acceden al módulo; el resto de roles no lo ve.
- [ ] Dashboard con contadores correctos y utilizable en celular.

---

## Fase 3: Tareas y formularios

**Objetivo:** que el equipo pueda asignarse trabajo y recolectar datos con listas de verificación.

### Tareas
- `tareas`: título, descripción, prioridad (1 urgente, 2 importante, 3 rutinaria), fecha límite opcional, creador, adjuntos.
- `tarea_asignaciones`: tarea, usuario o grupo, estado, fecha de completado.
- Dos modalidades:
  - **Individual:** cada asignado debe completarla por su cuenta.
  - **De equipo:** basta con que una persona de los asignados la complete.
- Vista "mis tareas" con secciones: próximas, vencidas, sin fecha y completadas.
- Reasignar, cambiar estado, editar y eliminar. Comentarios en la tarea.
- Tareas recurrentes (diaria, semanal, mensual) generadas por el scheduler.
- Tareas ligadas opcionalmente a un ticket de IT Desk.

### Formularios y listas de verificación
- `formularios`: título, descripción, alcance, abierto o cerrado, anónimo o no.
- `formulario_campos`: tipo (texto, número, selección, casillas, fecha, archivo, firma simple), obligatorio, orden.
- `formulario_respuestas` y `formulario_respuesta_valores`.
- Constructor de campos (agregar, reordenar, eliminar) y vista de respuestas con filtros.
- Exportar respuestas a CSV.
- **Listas de verificación:** formulario especial de casillas asignable de forma recurrente, con registro de quién la completó y cuándo.

**Criterios de aceptación**
- [ ] Una tarea individual exige a todos; una de equipo se cierra al completarla una persona.
- [ ] "Mis tareas" muestra correctamente próximas, vencidas y completadas.
- [ ] Una tarea recurrente se genera sola según su regla.
- [ ] Un formulario se construye, se responde y sus respuestas se exportan.
- [ ] Una lista de verificación recurrente deja registro de cada cumplimiento.

---

## Fase 4: Motor de reglas y notificaciones

**Objetivo:** automatizar con reglas "cuando pasa X, si se cumple Y, haz Z" y notificar por varios canales.

### Motor de reglas
- `reglas`: nombre, evento disparador, condiciones (JSON), acciones (JSON), activa, creador.
- `regla_ejecuciones`: regla, evento, resultado, error, fecha (registro de auditoría de cada ejecución).

**Eventos disparadores**
- Usuario creado o agregado a un grupo.
- Cumpleaños o aniversario hoy.
- Ticket cambia de estado o es asignado.
- Tarea creada, asignada, completada o vencida.
- Formulario respondido o encuesta cerrada.
- Quiz completado o nivel alcanzado (cuando existan, Fase 5).
- Por tiempo (diario, semanal) vía scheduler.

**Condiciones:** comparaciones sobre campos del evento (grupo, rol, estado, prioridad, días de retraso).

**Acciones**
- Enviar notificación (interna, push o correo) a un usuario, un grupo o el responsable.
- Publicar en el feed.
- Crear y asignar una tarea.
- Agregar o quitar de un grupo.
- Sumar puntos o dar una insignia (Fase 5).
- Cambiar el estado de un registro.

**Reglas de ejemplo para dejar listas**
1. Usuario nuevo → agregar al grupo de bienvenida, asignar tareas de inicio y publicar el aviso.
2. Cumpleaños hoy → publicar en el feed y notificar al equipo.
3. Ticket pasa a `COMPLETADO` → notificar al responsable de entrega.
4. Tarea vencida 2 días → avisar al responsable y a su jefe directo.

**Seguridad del motor:** solo `admin` crea reglas; límite de ejecuciones por minuto para evitar bucles; una regla no puede disparar a sí misma de forma recursiva; toda ejecución queda registrada. Se ejecuta en cola.

### Notificaciones
- Centro de notificaciones interno (campana, no leídas, marcar todas).
- Preferencias por usuario: qué canales y qué tipos desea recibir.
- **Push web:** tabla `push_suscripciones`, claves VAPID y envío desde el servidor. Condiciones a respetar:
  - En iPhone solo funciona con iOS 16.4 o superior, con la app instalada en la pantalla de inicio y pidiendo el permiso tras una acción del usuario.
  - En Android y escritorio funciona también desde el navegador.
  - Si el envío falla porque la suscripción ya no es válida, eliminarla.
- Correo para lo importante (restablecer contraseña, recordatorios vencidos), siempre en cola.

**Criterios de aceptación**
- [ ] Un admin crea, activa, desactiva y elimina reglas desde una pantalla.
- [ ] Las cuatro reglas de ejemplo funcionan y dejan registro en `regla_ejecuciones`.
- [ ] Una regla defectuosa o recursiva no tumba la app.
- [ ] El usuario recibe notificaciones internas y push según sus preferencias.
- [ ] El push funciona en Android y en iPhone con la app instalada.

---

## Fase 5: Engagement

**Objetivo:** reconocimiento, puntos, niveles, quizzes y medición del clima del equipo.

### Reconocimientos
- `reconocimientos`: de, para, valor o categoría, mensaje, visibilidad (público en el feed o privado).
- Se publican en el feed y pueden otorgar puntos.

### Puntos, niveles e insignias
- `puntos_movimientos`: usuario, puntos (positivos o negativos), motivo, origen (tipo e id). Es un **libro de movimientos**: nunca se edita el saldo, se agregan movimientos.
- `niveles`: nombre, puntos mínimos. El nivel se calcula a partir del total.
- `insignias` y `usuario_insignias`: criterios y fecha de obtención.
- Ranking por periodo (semana, mes, total) con opción de ocultarlo.
- Reglas del motor (Fase 4) pueden sumar puntos o dar insignias.

### Quizzes
- `quizzes`, `quiz_preguntas`, `quiz_opciones`, `quiz_intentos`, `quiz_respuestas`.
- Puntos por respuesta correcta, número de reintentos configurable y espera entre reintentos.
- **Quiz de varios días:** una campaña con un quiz por día, ganador diario y ganador general por puntos acumulados.

### Encuestas y clima
- `encuestas`, `encuesta_preguntas`, `encuesta_opciones`, `encuesta_respuestas`.
- Tipos de pregunta: opción única, múltiple, escala, texto libre.
- **Pulse:** encuestas cortas y recurrentes.
- **eNPS:** una pregunta de 0 a 10 con cálculo automático (promotores menos detractores).
- Anonimato real: si es anónima, no guardar el usuario junto a la respuesta; solo quién ya respondió, en una tabla aparte.
- Se puede editar mientras no tenga respuestas; después, solo cerrar. Resultados agregados con gráficos.

### Cumpleaños y aniversarios
- Cumpleaños y aniversarios laborales se publican en el feed y alimentan reglas de la Fase 4.

**Criterios de aceptación**
- [ ] Un reconocimiento aparece en el feed y suma puntos al receptor.
- [ ] El saldo y el nivel se calculan solo desde `puntos_movimientos`.
- [ ] Un quiz de varios días calcula ganadores diarios y general.
- [ ] La encuesta anónima no permite identificar al respondiente.
- [ ] El eNPS se calcula correctamente y se ve por periodo.

---

## Fase 6: Academia y onboarding

**Objetivo:** capacitación guiada y onboarding automático para personal nuevo.

### Academia
- `cursos`: título, descripción, portada, fecha límite opcional, alcance.
- `lecciones`: curso, orden, tipo (texto, video, imagen, archivo, quiz, confirmación de lectura), contenido.
- `curso_progreso`: usuario, lección, estado, fecha.
- **Rutas de aprendizaje:** varios cursos en orden, con requisito de completar el anterior.
- Reglas de reintento de quizzes (por defecto, ilimitados con espera corta).
- Certificado al completar un curso (PDF generado con nombre, curso y fecha).
- Progreso visible para el usuario y para los admins (quién va, quién no, quién está retrasado).

### Onboarding
- **Plan de onboarding** asignable a un grupo o a una persona: lista ordenada de pasos (leer un manual, completar un curso, responder un formulario, hacer una tarea, conocer a alguien del directorio).
- Se inicia automáticamente al crear el usuario o en su fecha de inicio (regla de la Fase 4), incluso antes del primer día si se configura.
- Seguimiento del avance con aviso al jefe directo.

**Criterios de aceptación**
- [ ] Un curso se crea con lecciones de varios tipos y el progreso se guarda.
- [ ] La ruta de aprendizaje respeta el orden y el certificado se genera al terminar.
- [ ] Un usuario nuevo recibe su plan de onboarding sin intervención manual.
- [ ] El admin ve el avance y los retrasos de cada persona.

---

## Fase 7: Chat en tiempo real

**Objetivo:** mensajería directa y por grupo, sin servicios externos de pago.

### Tecnología
- **Laravel Reverb** (servidor WebSocket oficial) más **Laravel Echo** en el cliente. Canales privados para conversaciones y canales de presencia para saber quién está conectado.
- Alternativa más simple si hay límites de recursos: *polling* cada pocos segundos, con la misma estructura de datos para migrar después.
- Requiere dejar un proceso `reverb:start` corriendo (agregarlo al comando de desarrollo).

### Datos
- `conversaciones`: tipo (directa o grupo), nombre, creador.
- `conversacion_usuarios`: conversación, usuario, último mensaje leído, silenciada.
- `mensajes`: conversación, autor, contenido, adjuntos, `deleted_at`.

### Funciones
- Conversaciones directas y por grupo; crear grupo y agregar o quitar personas.
- Enviar y eliminar mensajes propios; adjuntar imágenes y archivos con límite.
- Indicador de escribiendo, de conectado y contador de no leídos.
- Notificación push cuando hay mensaje nuevo y la persona no está en la conversación.
- Autorización estricta: solo los participantes leen una conversación.

**Criterios de aceptación**
- [ ] Dos usuarios chatean y los mensajes llegan sin recargar.
- [ ] Un usuario ajeno a la conversación no puede leerla, ni por URL ni por el canal.
- [ ] El contador de no leídos y la presencia funcionan.
- [ ] El chat sigue siendo usable en celular.

---

## Fase 8: Analítica

**Objetivo:** medir qué se lee, qué se cumple y cómo está el equipo.

- **Lecturas:** `publicacion_lecturas` (publicación, usuario, fecha). Tasa de apertura por publicación y lista de quién no la ha visto.
- **Dashboard de administración:**
  - Participación: usuarios activos por semana, publicaciones, reacciones y comentarios.
  - Tareas: completadas, vencidas y tiempo medio de cierre por área.
  - IT Desk: tickets por estado, tiempo medio de resolución, carga por técnico.
  - Capacitación: avance por curso y por persona.
  - Clima: eNPS y resultados de encuestas pulse en el tiempo.
- Filtros por periodo y por grupo.
- Exportar a CSV.
- **Privacidad:** los datos de encuestas anónimas nunca se desglosan por persona, y la analítica individual solo la ve el propio usuario y los admins.

**Criterios de aceptación**
- [ ] La tasa de apertura de una publicación coincide con sus lecturas reales.
- [ ] Los números del dashboard coinciden con los registros de cada módulo.
- [ ] Las encuestas anónimas no se pueden desglosar.

---

## Fase 9: IA (opcional)

**Objetivo:** ahorrar trabajo editorial y de búsqueda. No bloquea ninguna otra fase.

- Resumir publicaciones largas y sugerir título.
- Redactar un borrador de anuncio a partir de unas notas.
- Traducir publicaciones al idioma del lector.
- Buscar en manuales y documentos con preguntas en lenguaje natural.
- Proveedor configurable: modelo local (Ollama) o API externa, activable por un ajuste. Nunca enviar datos de encuestas anónimas, chats ni datos de clientes del IT Desk a un servicio externo.
- Toda salida de IA es un **borrador** que una persona revisa antes de publicar.

---

## Orden y dependencias

| Fase | Depende de | Nota |
|---|---|---|
| 1. Base y brechas | n/a | Obligatoria primero |
| 2. IT Desk | 1 | Es el diferenciador del proyecto |
| 3. Tareas y formularios | 1 | Puede ir en paralelo a la 2 si hay tiempo |
| 4. Motor de reglas y notificaciones | 1, 2, 3 | Sin esto, la automatización es manual |
| 5. Engagement | 4 | Aprovecha reglas y notificaciones |
| 6. Academia y onboarding | 3, 4, 5 | Usa tareas, quizzes y reglas |
| 7. Chat | 4 | Lo más pesado de mantener |
| 8. Analítica | 1 a 6 | Mide lo que ya existe |
| 9. IA | 1 | Opcional |

## Fuera de alcance (a propósito)

- Sincronización con sistemas de RR. HH. empresariales (Workday, Azure AD) y SCIM.
- Señalización digital y boletines complejos.
- Compatibilidad con paquetes SCORM.
- Aplicación nativa o de escritorio: el PWA cubre PC y celular.

## Lista de mejoras (anotar aquí lo que surja y no sea de la fase)

- _(vacío)_
