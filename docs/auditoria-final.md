# Daybridge: revisión completa, cierre de brechas y Liquid Glass

## 0. Cómo usar este documento

1. Léelo completo antes de tocar nada. Trabaja por partes, en el orden de este documento, y no pases a la siguiente sin mi OK.
2. Este documento dice QUÉ debe cumplirse. El cómo lo decides tú, dentro de la estructura que ya existe.
3. Vuelve a leer el código real del proyecto: rutas, componentes Livewire y Volt, vistas Blade, migraciones, modelos, policies, tests, configuración, tailwind.config.js, resources/css y resources/js. No asumas que algo funciona porque su archivo existe: pruébalo.
4. Contexto técnico: Laravel, Breeze con Livewire (Volt), Tailwind 3, Vite, SQLite y Laravel Boost. El diseño base es «Señal» (docs/direccion-visual.md). La referencia del módulo de soporte es docs/it-desk-especificacion.md.
5. Estados permitidos en tus reportes:
   - OK: lo ejecutaste y funciona (navegador, test o tinker). Leer el código no cuenta.
   - INCOMPLETO: existe, pero le faltan acciones, validaciones, casos o permisos.
   - ROTO: falla, da error o no hace lo que dice.
   - FALTA: no existe.
6. Prohibido, salvo que yo lo pida:
   - Rediseñar pantallas o cambiar el sistema de diseño (la única excepción es la Parte A).
   - Ejecutar migrate:fresh, db:seed, db:wipe o cualquier comando que borre datos.
   - Instalar paquetes nuevos sin preguntarme.
   - Escribir comentarios en el código.
   - Dejar clases, componentes, vistas o archivos vacíos o sin uso: se borran.
   - Marcar algo como terminado sin haberlo probado.
7. Si encuentras algo que es una mejora y no una brecha, no lo hagas: anótalo en una lista aparte llamada «Ideas».

---

## Parte A. Liquid Glass (bloqueante, va primero)

### A1. Situación

El efecto Liquid Glass se ha intentado implementar varias veces y no se ve. Tampoco existe un fondo detrás de la interfaz. Sin un fondo con color y contenido, el vidrio no tiene qué desenfocar ni refractar y la pantalla se ve plana y opaca, por mucho CSS que se añada. Esta parte debe resolverse primero y demostrarse.

Esta parte reemplaza la regla anterior del prompt de diseño que decía «sin efectos de vidrio innecesarios». El vidrio ahora es un requisito explícito, pero solo en las superficies del apartado A3. El resto del sistema «Señal» (tokens, tipografía, colores, modo claro y oscuro) se conserva.

### A2. Qué es el efecto (referencias)

Las imágenes están en docs/referencias/. Mira las cuatro. Rasgos que se deben reconocer en el resultado:

- liquid-glass-1-kit-ios26.png y liquid-glass-2-panel-controles.png: paneles y botones translúcidos con esquinas muy redondeadas, desenfoque fuerte de lo que hay detrás, borde fino y brillante, botones circulares, controles en forma de cápsula y un selector segmentado (Years, Months, All) dentro de una cápsula de vidrio. Todo flota sobre un fondo de color intenso y degradado.
- liquid-glass-3-formas-flotantes.png: formas de vidrio flotando con el borde luminoso, que parecen una lente sobre el fondo.
- liquid-glass-4-barra-navegacion.png: barra de navegación inferior como una cápsula de vidrio, con una botonera flotante encima y, detrás del elemento activo, una mancha de color con forma líquida que indica la selección.

Rasgos obligatorios, en términos de resultado visible:

1. Hay un campo de color vivo detrás del vidrio, y se nota a través de él.
2. El vidrio es translúcido: se ve el fondo desenfocado y con los colores más saturados, no un gris plano.
3. Tiene un borde de 1 px claro y un reflejo suave en el borde superior o en una esquina, para dar sensación de grosor.
4. Los radios son grandes y las formas son cápsulas o rectángulos muy redondeados.
5. La sombra es suave y corta, sin halos pesados.
6. El texto y los iconos sobre el vidrio se leen bien en cualquier punto del fondo.
7. El elemento activo de la navegación se distingue con una forma de color que se mueve de un ítem a otro.

CORRECCIÓN A LA PARTE A (Liquid Glass). Reemplaza las secciones A3 y A4 de docs/auditoria-final.md. El resto de la Parte A sigue vigente.

Situación actual (mira las capturas): el efecto solo existe en el modal «Módulos» y se ve mal, porque desenfoca una página crema plana. No hay fondo. Todo lo demás es sólido.

1. FONDO CON FOTO
- Lista la carpeta media/media/background del proyecto (C:\laragon\www\comunicados\media\media\background) y dime qué fotos hay: nombre, dimensiones, peso y una línea sobre cómo es (colores dominantes, nivel de detalle).
- Elige la que mejor sirva para vidrio: colores variados, sin zonas demasiado claras ni oscuras, sin detalle muy fino. Si dudas entre dos, quédate con la mejor y deja la otra disponible con un único ajuste.
- Esa carpeta no está dentro de public, así que el navegador no puede cargarla. La foto elegida debe servirse desde la app.
- Debe estar optimizada: formato moderno, una versión para móvil y otra para escritorio, cada una por debajo de 300 KB. No modifiques las originales.
- Capa fija detrás de todo, a pantalla completa, sin interceptar clics, visible en todas las pantallas (invitado y autenticado).
- El tema claro y el oscuro tienen cada uno su tratamiento de la misma foto (velo de color y brillo distintos), para que el contraste del texto se cumpla en ambos.
- Con movimiento reducido no hay animación. Un movimiento lento de la foto es opcional.
- Mientras carga, se ve un color de respaldo, y la foto no retrasa la primera pantalla.

2. VIDRIO EN TODA LA INTERFAZ
Quiero que todas las superficies sean de vidrio: navegación, cabecera, tarjetas, bloques de estadísticas, barras de filtros, campos de formulario, botones secundarios, chips, pestañas, menús, modales, notificaciones, tablas, listas, tickets en pantalla y chat. Excepciones: lo que se imprime (tickets imprimibles) y el interior de los editores de texto largo.
- Dos densidades, definidas como tokens con versión clara y oscura:
  a) Ligero: navegación, cabecera, chips, controles, menús y modales. Se ve mucho fondo.
  b) Denso: todo lo que contiene texto para leer (publicaciones, tablas, formularios, chat, tarjetas de datos). Más opaco y más desenfocado, de modo que el texto se lea siempre.
- Las superficies repetidas (cada fila de una lista, cada tarjeta de un feed) no llevan desenfoque individual: van sobre un panel de vidrio contenedor, y las filas usan translucidez sin desenfoque. Máximo cuatro superficies con desenfoque a la vez en móvil.
- Se mantiene el sistema «Señal»: tipografía, colores de texto, acento naranja, tokens y jerarquía. Solo cambian las superficies y el fondo.
- Un único componente o conjunto de clases reutilizable. Prohibido repetir estilos de vidrio en cada vista.
- Los modales y menús ya no llevan tarjetas blancas sólidas dentro: todo su interior es de vidrio, con botones de la misma familia.

3. CONTRASTE Y RESPALDO (obligatorio)
- 4.5 a 1 para texto normal y 3 a 1 para texto grande, medido sobre las zonas más claras y más oscuras de la foto, en ambos temas.
- Sin soporte de desenfoque, o con reducir transparencia activado, las superficies pasan a sólido semitransparente legible.

4. DEFECTOS QUE YA SE VEN EN LAS CAPTURAS (corrígelos)
- El logotipo de Daybridge, arriba en la barra lateral, aparece como imagen rota con el texto alternativo. Encuentra por qué (ruta, archivo ausente o nombre distinto) y arréglalo en escritorio, móvil, login y favicon.
- En el modal «Módulos» las primeras filas de tarjetas quedan cortadas bajo la cabecera, las tarjetas son blancas sólidas y se ve la barra de desplazamiento del sistema. Debe verse limpio, respetando el área segura, y la barra de scroll con el estilo de la app.
- IT Desk: la tarjeta de equipos dice «6 tickets abiertos», pero hay 2 completados entre 6 tickets en total. Verifica la lógica y la etiqueta: «abiertos» deben ser los pendientes más los en proceso.
- IT Desk: el logotipo «IT desk tracker» y el texto «agenda del taller» vienen del proyecto de escritorio. Propón una versión coherente con Daybridge y espera mi decisión antes de cambiarlo.
- Línea de tiempo del día: el marcador «ahora» debe coincidir con la hora local real. Verifica la zona horaria de la aplicación.

5. ENTREGA
- Antes de tocar nada, entrega la lista de fotos del punto 1 con tu recomendación, y una tabla de todas las superficies de la app (vista, componente y densidad que le toca). Espera mi OK.
- Después implementa y reporta las 10 pruebas de aceptación de A10, ahora aplicadas a toda la app, con capturas o con los estilos calculados si no puedes ver el navegador.
- Un commit por paso, y no ejecutes ningún comando que borre datos.

### A3. Alcance: dónde sí y dónde no

El vidrio se aplica a la capa de control y de superposición. El contenido que se lee sigue sobre superficies sólidas del sistema «Señal».

Sí lleva vidrio:
- Navegación de escritorio (rail) y de móvil (barra inferior).
- Cabecera fija o barra superior, si existe.
- Botón de acción flotante o píldora de acción principal.
- Modales y diálogos.
- Menús desplegables, selectores y popovers.
- Notificaciones emergentes (toasts).
- Chips, selectores segmentados y pestañas.
- Controles flotantes del chat (caja de escribir) y del calendario.

No lleva vidrio:
- Texto corrido, publicaciones, comentarios, mensajes del chat, tablas de datos, formularios largos y documentos. Se mantienen en superficies sólidas para que se lean sin esfuerzo.
- Pantallas de impresión y tickets imprimibles.

### A4. El fondo («escenario»)

Debe existir una capa de fondo propia, visible en todas las pantallas de la app: invitado (login, registro, recuperar contraseña) y autenticado.

- Está detrás de todo el contenido, ocupa toda la ventana, no se desplaza con el scroll y no intercepta clics ni toques.
- Contiene manchas grandes y difusas de los colores de marca (verde azulado profundo, naranja de señal y tonos crema o azul noche según el tema), mezcladas entre sí. No usar morado o rosa genéricos: los colores salen de los tokens de «Señal».
- Existe en tema claro y en tema oscuro, con versiones propias de cada uno. En claro es luminoso y suave; en oscuro es profundo con zonas de color.
- Tiene un movimiento lento y discreto. Con prefers-reduced-motion queda quieto.
- No tapa ni baja el contraste del contenido sólido (publicaciones, tablas, formularios).
- No puede ser un color plano ni una imagen pesada. Debe pesar poco y no tirar el rendimiento en un celular.

### A5. Requisitos del vidrio

- Cada superficie de vidrio muestra el fondo desenfocado a través de ella, con color más intenso que el original.
- El nivel de translucidez, desenfoque, borde y brillo sale de tokens del sistema (variables CSS y nombres semánticos en Tailwind), con versión clara y oscura, no de valores sueltos repetidos en cada archivo.
- Debe haber al menos tres intensidades definidas: ligera (chips, controles pequeños), media (barras de navegación, cabecera) y fuerte (modales y menús).
- Un único componente o conjunto de clases reutilizable aplica el vidrio. Prohibido copiar y pegar los mismos estilos en cada vista.
- Las superficies de vidrio funcionan encima de otras superficies de vidrio sin quedar ilegibles (por ejemplo, un menú sobre la barra).

### A6. Elemento activo líquido en la navegación

- En la navegación de escritorio y de móvil, el ítem activo tiene detrás una forma de color de acento que se desplaza de un ítem al siguiente al navegar.
- El indicador sigue funcionando al recargar la página y al cambiar de tamaño de pantalla.
- Con movimiento reducido, el indicador cambia de posición sin animación.

### A7. Accesibilidad y respaldo

- Contraste de texto e iconos sobre vidrio de al menos 4.5 a 1 (3 a 1 en texto grande) sobre cualquier zona del fondo, en tema claro y oscuro. Si no se cumple en algún punto, se refuerza la translucidez de esa superficie.
- Si el navegador no soporta el desenfoque de fondo, o la persona activó reducir transparencia, las superficies pasan a un color sólido semitransparente legible. La app nunca queda ilegible.
- Foco visible en todos los controles de vidrio.
- El desenfoque y el movimiento respetan las preferencias del sistema.

### A8. Rendimiento

- Máximo tres superficies de vidrio grandes visibles a la vez en móvil.
- El scroll del feed, del chat y de las listas se mantiene fluido en un celular de gama media.
- El fondo animado no consume CPU de forma perceptible con la pestaña inactiva.

### A9. Diagnóstico obligatorio si el efecto no se ve

Antes de escribir más estilos, descubre la causa y repórtala. Causas típicas a descartar, una por una:

1. No hay fondo con color detrás: el desenfoque sobre un color plano no se nota.
2. Un contenedor padre tiene fondo opaco, o el propio elemento tiene un fondo de opacidad 1.
3. El desenfoque queda anulado por la estructura de los contenedores (por ejemplo, un ancestro que crea un contexto de apilamiento o recorta el contenido, o el vidrio queda dentro de un elemento con fondo propio).
4. Las clases de Tailwind no se están generando: las rutas de content de tailwind.config.js no incluyen las vistas, componentes Volt o archivos donde se usan.
5. Vite no está recompilando, o el navegador carga un CSS viejo (comprobar public/build, el servidor de Vite y la caché).
6. Los tokens existen pero los componentes siguen usando los colores sólidos antiguos (bg-surface, bg-canvas) en vez de los de vidrio.
7. Un modo (claro u oscuro) tiene definidos los tokens y el otro no.
8. Se confundió el efecto con una simple opacidad: bajar la opacidad sin desenfoque no es vidrio.

En el reporte, escribe cuál de estas causas aplicaba, con el archivo y la línea.

### A10. Pruebas de aceptación (obligatorias, con evidencia)

Reporta cada una como OK o ROTO con la evidencia que corresponda. Si no puedes abrir un navegador, dilo y entrega el resultado de inspeccionar los estilos calculados en el código compilado; yo haré la comprobación visual.

1. En login, registro y dashboard existe la capa de fondo y se ve color en los cuatro bordes de la pantalla.
2. En el dashboard, la barra de navegación muestra el fondo desenfocado a través de ella.
3. Al abrir un modal, el contenido de atrás se ve desenfocado a través del vidrio del modal.
4. Al cambiar de tema, el fondo y el vidrio cambian de aspecto y siguen siendo legibles.
5. El indicador activo de la navegación se mueve al cambiar de módulo.
6. En 360 px de ancho, la barra inferior es de vidrio, no tapa el contenido y respeta el área segura del celular.
7. Con reducir movimiento activado, nada se anima.
8. Con reducir transparencia activado o sin soporte de desenfoque, todo sigue legible.
9. El texto sobre vidrio cumple el contraste en cinco puntos distintos del fondo, en ambos temas.
10. npm run build termina sin errores y las clases del vidrio aparecen en el CSS generado.

Detente al terminar la Parte A y espera mi OK con capturas o mi confirmación.

---

## Parte B. Reglas que se auditan en TODAS las pantallas

Para cada módulo de la Parte C, verifica además estos puntos transversales y repórtalos en la tabla.

### B1. Autenticación y autorización
- Toda ruta de la app exige sesión iniciada, salvo las de invitado.
- Cada acción de crear, editar y eliminar está protegida en el servidor con Policies o Gates, no solo escondiendo botones.
- Solo el autor o un administrador puede editar o eliminar un contenido. Un usuario sin permiso recibe una respuesta 403, y la prueba lo demuestra.
- Un usuario no puede ver, editar ni eliminar datos de otro cambiando un id en la dirección.
- Los usuarios inactivos no pueden iniciar sesión ni mantener la sesión activa.

### B2. Validación y mensajes
- Todo formulario valida en el servidor con reglas explícitas y mensajes en español.
- Los errores aparecen junto a cada campo y el formulario conserva lo que el usuario escribió.
- Longitudes máximas y tipos coherentes con la base de datos.
- Los campos de correo, teléfono, fecha y hora validan formato.

### B3. Acciones destructivas y retroalimentación
- Eliminar siempre pide confirmación en un modal del sistema.
- Toda acción exitosa muestra un mensaje de confirmación; toda acción fallida muestra un error comprensible, nunca una pantalla técnica.
- Los botones de envío quedan deshabilitados mientras se procesa, para evitar duplicados.

### B4. Persistencia y datos
- Todo lo que se crea o edita se guarda en la base de datos y sigue ahí al recargar y al cerrar sesión.
- Las eliminaciones son coherentes con las relaciones: no quedan registros huérfanos ni errores por llaves foráneas.
- Tablas y columnas en minúsculas y snake_case.
- Nada visible con datos de ejemplo, como test@example.com, Usuario 1 o lorem ipsum, salvo en seeders claramente marcados para desarrollo.

### B5. Estados de interfaz
- Cada lista tiene estado vacío con mensaje útil y la acción siguiente.
- Cada carga o petición lenta tiene indicador de carga.
- Cada error de red o de servidor tiene un mensaje.
- Las páginas 403, 404, 419 y 500 usan el diseño de la app.
- Las listas largas tienen paginación o carga progresiva.

### B6. Responsive y uso en celular
- Probar en 360, 768 y 1280 px.
- Sin desplazamiento horizontal accidental.
- Los botones y áreas táctiles miden al menos 44 px.
- Las tablas se convierten en tarjetas o listas en móvil.
- Los formularios se pueden llenar cómodamente con una mano y el teclado no tapa el campo activo.
- Se respeta el área segura de los celulares con muesca y barra inferior.

### B7. Temas, idioma y accesibilidad
- Todas las pantallas se ven bien y se leen en tema claro y oscuro.
- La interfaz, los mensajes, las fechas y los números están en español. La zona horaria y el idioma están configurados en la aplicación.
- Todos los controles tienen etiqueta, foco visible y se pueden usar con teclado.
- Los iconos que actúan como botones tienen texto alternativo.
- Se respeta prefers-reduced-motion.

### B8. Seguridad
- CSRF activo en todos los formularios y acciones.
- El contenido que escriben los usuarios se muestra escapado, sin permitir HTML o scripts.
- Las subidas de archivos validan tipo y tamaño, se guardan fuera de rutas ejecutables y los archivos privados no son accesibles sin permiso.
- El inicio de sesión tiene límite de intentos.
- El archivo .env no está en Git y no hay claves ni contraseñas en el repositorio.
- La contraseña mínima es de 8 caracteres y se guarda con hash.

### B9. Calidad de código
- Ninguna lista hace una consulta por cada fila.
- No hay comentarios en el código.
- No hay clases, componentes, vistas, rutas ni archivos sin uso. Se borran.
- No hay enlaces href="#", botones sin acción, TODO ni textos de relleno.
- No hay errores en la consola del navegador ni en storage/logs/laravel.log durante un recorrido completo.

---

## Parte C. Qué debe poder hacerse en cada módulo

Para cada módulo: ejecuta cada acción, aplica la Parte B y marca cada punto como OK, INCOMPLETO, ROTO o FALTA.

### C1. Inicio
- Muestra la fecha de hoy, el saludo con el nombre de la persona y la línea de tiempo del día con el marcador «ahora».
- Muestra el comunicado destacado, las publicaciones recientes, lo de «hoy» (cumpleaños, eventos, avisos) y accesos rápidos reales.
- Los datos salen de la base de datos, no están escritos a mano.
- En móvil se reordena con criterio: lo de hoy aparece arriba.

### C2. Feed de noticias y publicaciones
- Un usuario con permiso puede crear una publicación con título, contenido, categoría o área, imagen opcional y tipo (noticia, aviso corto, reconocimiento).
- Puede editar y eliminar sus propias publicaciones; el administrador puede con todas.
- Puede fijar y desfijar una publicación destacada.
- Los comunicados urgentes se distinguen visualmente.
- Se puede filtrar por categoría o área.
- Cada publicación admite comentarios y reacciones: crear comentario, editar y eliminar el propio, poner y quitar reacción, ver quién reaccionó.
- Se muestra el autor con foto, el área y la fecha relativa.

### C3. Muro social
- Cualquier usuario puede publicar un mensaje corto con imagen opcional.
- Puede editar y eliminar lo suyo; el administrador modera.
- Admite comentarios y reacciones igual que el feed.
- Las publicaciones aparecen en orden cronológico inverso con paginación o carga progresiva.

### C4. Cumpleaños y reconocimientos
- Se puede registrar y editar la fecha de nacimiento de cada persona, desde su perfil o por el administrador.
- Se muestran los cumpleaños de hoy y los próximos, agrupados por semana.
- Cualquier usuario puede felicitar con un clic y quitar su felicitación; se ve quién ya felicitó.
- Se pueden crear y eliminar reconocimientos entre personas con un mensaje.
- El estado vacío tiene mensaje propio.
- El año de nacimiento no se expone si no corresponde.
- El cálculo de «hoy» usa la zona horaria de la aplicación.

### C5. Directorio
- Lista todas las personas activas con foto, nombre, puesto, área, correo y teléfono.
- Búsqueda por nombre, puesto o área y filtro por área.
- Al abrir una persona se ve su perfil completo.
- El administrador puede editar los datos (nombre, puesto, área, correo, teléfono, foto) y desactivar o eliminar a la persona.
- El botón «Escribir correo» funciona: abre el cliente de correo del dispositivo y ofrece como alternativa abrir Gmail en el navegador con el destinatario ya escrito. Además hay un botón «Copiar correo» que confirma que se copió.
- El teléfono se puede tocar para llamar desde el celular.
- Las personas inactivas no aparecen en el directorio.

### C6. Tareas
- Crear tarea con título, descripción, responsable, fecha límite, prioridad y estado.
- Editar, reasignar, cambiar de estado (pendiente, en curso, hecha), cambiar la fecha límite y eliminar.
- Vista «Mis tareas» con las asignadas a la persona.
- Las tareas vencidas se distinguen.
- Cada persona solo modifica el estado de sus tareas; quien las creó y el administrador pueden editarlas.
- Notificación al asignar una tarea.

### C7. Encuestas y pulse surveys
- Crear una encuesta con título, descripción y preguntas de varios tipos (opción única, opción múltiple, escala y texto libre), con fecha de cierre opcional y audiencia.
- Editar una encuesta mientras no tenga respuestas; después solo se puede cerrar.
- Cerrar, reabrir y eliminar una encuesta.
- Cada persona responde una sola vez y no puede cambiar su respuesta, salvo que se permita expresamente.
- Resultados agregados con porcentajes y conteos, visibles para quien creó la encuesta y los administradores.
- Si la encuesta es anónima, nadie puede ver quién respondió qué.
- Una encuesta cerrada no acepta respuestas.

### C8. Manuales y documentos
- Crear carpetas y subcarpetas, renombrarlas, moverlas y eliminarlas.
- Subir archivos (PDF, imágenes y documentos de oficina) con validación de tipo y tamaño, renombrar, mover, descargar y eliminar.
- Los permisos de lectura y de edición dependen del rol.
- Búsqueda por nombre.
- Se muestran el tipo, el tamaño y quién lo subió.
- Eliminar una carpeta con contenido pide una confirmación reforzada.

### C9. Formularios
- Crear formularios con campos de distintos tipos (texto, número, fecha, opción, casillas y archivo) y marcarlos como obligatorios.
- Editar campos mientras no haya respuestas, y reordenarlos.
- Publicar, cerrar y eliminar un formulario.
- Las personas completan y envían; ven una confirmación del envío.
- Quien administra ve todas las respuestas, puede filtrarlas, verlas una por una y exportarlas.
- Se valida cada campo obligatorio en el servidor.

### C10. Chat
- Iniciar una conversación directa con cualquier persona activa.
- Crear grupos con nombre, agregar y quitar miembros, salir de un grupo.
- Enviar mensajes de texto; editar y eliminar los propios.
- Las conversaciones se ordenan por actividad reciente y muestran el último mensaje y los no leídos.
- Los mensajes se marcan como leídos al abrir la conversación.
- Nadie puede leer conversaciones en las que no participa.
- En celular, la caja de escribir queda visible sobre el teclado.
- El chat se actualiza sin recargar la página.

### C11. Notificaciones
- Se generan al recibir una tarea, una mención, una felicitación, una respuesta o comentario en lo propio, un mensaje nuevo, una publicación destacada y un cambio de estado de un ticket propio.
- Hay un indicador de no leídas en la navegación.
- Se puede marcar una como leída, marcar todas como leídas y eliminarlas.
- Tocar una notificación lleva a lo que la originó.
- Cada persona solo ve las suyas.

### C12. Usuarios, roles y permisos
- Roles definidos y aplicados: administrador, editor y usuario, más los roles propios del módulo de soporte.
- El administrador crea usuarios, edita sus datos, cambia su rol, los activa o desactiva y los elimina.
- Siempre queda al menos un administrador activo; no se puede degradar ni desactivar al último ni a uno mismo.
- Las contraseñas del administrador al crear usuarios cumplen el mínimo y existe forma de restablecerlas.
- Los editores pueden publicar y moderar contenido, pero no gestionar usuarios.
- La matriz de permisos coincide con lo que realmente se puede hacer en cada pantalla.

### C13. Perfil propio
- Cada persona edita su nombre, puesto, teléfono, fecha de nacimiento y foto.
- Cambia su contraseña pidiendo la actual.
- Puede elegir el tema (claro, oscuro o el del sistema) y se conserva.
- La foto de perfil ya funciona: úsala donde corresponda, no la audites ni la cambies.

### C14. Búsqueda global (solo comprobar si existe)
- Si existe, busca en personas, publicaciones, documentos y tareas respetando los permisos. Si no existe, anótalo como FALTA y como idea, no la implementes.

---

## Parte D. IT Desk

La fuente es docs/it-desk-especificacion.md. Verifica cada regla de sus secciones 1 a 6 contra el código y el comportamiento real, y repórtalo con una fila por regla. Además, comprueba específicamente lo siguiente.

### D1. Equipos
- Registrar, ver, editar y eliminar un equipo con todos los campos y sus límites.
- Código interno con el formato DSP más cuatro dígitos.
- Foto del equipo opcional, validada y guardada de forma segura.
- Búsqueda por cliente, código o folio, y filtro por estado.
- Al registrar un equipo se crea en la misma operación un ticket de ingreso con su folio. Si algo falla, no queda nada a medias.
- La fecha estimada de entrega no puede ser anterior a hoy al registrar.

### D2. Tickets
- Folio único con el formato prefijo, año y consecutivo de cuatro dígitos, que se reinicia cada año y no se duplica aunque dos personas creen tickets a la vez.
- Descripción obligatoria de máximo 255 caracteres.
- Cambio de estado entre pendiente, en proceso y completado, con rechazo de un cambio al mismo estado.
- Al completar se guarda la fecha de completado; al reabrir se borra.
- El estado del equipo se recalcula según la regla de la especificación después de crear un ticket o cambiar su estado.
- Asignar y cambiar el técnico.
- Eliminar con confirmación.
- Notificación al técnico asignado y al solicitante cuando cambia el estado.

### D3. Impresión
- Vista previa en pantalla antes de imprimir.
- Una hoja carta en horizontal con dos tickets: el de equipo, con código de barras Code 128 del folio, y el comprobante de cliente, con todos los datos que indica la especificación.
- El código de barras es legible e impreso con buena definición.
- Se imprime solo con la información necesaria, sin menús, fondos ni efectos de vidrio.
- Al confirmar, el ticket queda marcado como impreso con fecha, y se registran dos impresiones (equipo y cliente) con la persona que imprimió.
- Se puede reimprimir y cada reimpresión queda registrada.

### D4. Calendario de citas
- Vista de mes y de día.
- Crear, editar, completar, revertir y eliminar citas con título, descripción, fecha, hora y técnico.
- Filtro por técnico.
- Las citas de hoy aparecen en el dashboard y en el inicio.
- En móvil, la agenda del día se ve y se maneja cómodamente.

### D5. Técnicos y personal de soporte
- Los técnicos son usuarios de la app con su rol. No existe una tabla ni un login aparte.
- Solo el administrador gestiona al personal de soporte.
- Se cumplen las reglas del último administrador.

### D6. Dashboard de soporte
- Contadores reales de pendientes, en proceso, completados y citas de hoy.
- Accesos rápidos funcionales: registrar equipo, equipos, tickets y calendario.

### D7. Pruebas
Existen y pasan las pruebas de la sección 6 de la especificación, todas con una base de datos separada de la real.

---

## Parte E. PWA y uso desde el celular

- La app se puede instalar como acceso directo en el celular desde el navegador, con su nombre, su icono y su color de tema.
- Tiene manifiesto válido, iconos de 192 y 512 px, icono adaptable (maskable) y favicon.
- Tiene service worker y una página sin conexión con el diseño de la app, que se muestra cuando no hay internet.
- Al abrirla desde el acceso directo se ve sin la barra del navegador y respeta las áreas seguras.
- Las pantallas de login y las rutas protegidas se comportan bien al abrirse desde el acceso directo.
- El service worker no guarda en caché datos privados que otra persona pueda ver en el mismo dispositivo.
- Nota: la instalación y el service worker solo funcionan en HTTPS o en localhost. Si no se puede comprobar en el celular por la red local, repórtalo como no verificable en este entorno y verifica el manifiesto y el service worker en localhost.

---

## Parte F. Datos, base de datos y pruebas

### F1. El usuario que desaparece al registrarse (prioridad máxima)
Antes que cualquier otra cosa:
1. Muestra DB_CONNECTION y DB_DATABASE del .env, y la ruta real del archivo SQLite que está usando la aplicación.
2. Revisa phpunit.xml: los tests deben usar una base de datos separada (por ejemplo, en memoria) y nunca la real. Si no es así, repórtalo como ROTO.
3. Revisa qué tests usan RefreshDatabase o similares y contra qué base se ejecutan.
4. Revisa el historial de comandos recientes y los seeders: confirma si algo ejecutó migrate:fresh o db:seed sobre la base real.
5. Registra un usuario nuevo desde el navegador y confirma que existe en la base, que persiste tras recargar y que puede volver a iniciar sesión.
6. Confirma que el formulario de registro guarda todos los campos que muestra, y que la contraseña se guarda con hash.
7. Informa la causa exacta de la desaparición con evidencia.

No ejecutes ningún comando que borre datos mientras investigas.

### F2. Migraciones y modelos
- Todas las migraciones corren en una base vacía sin errores.
- Cada tabla tiene sus llaves foráneas, índices y valores por defecto razonables.
- Los modelos declaran las relaciones y los campos permitidos, y no hay campos editables que no debieran serlo.
- Hay seeders solo de desarrollo, claramente separados, y el primer administrador se puede crear de forma segura.

### F3. Pruebas automáticas
- php artisan test termina sin fallos.
- Cada módulo tiene al menos pruebas de crear, editar, eliminar y permiso denegado.
- Las pruebas no tocan la base real ni sus archivos subidos.

---

## Parte G. Limpieza final

- Eliminar clases, componentes Livewire y Volt, vistas, rutas, archivos de prueba y recursos sin uso.
- Eliminar código muerto, variables sin uso y consolas de depuración (dd, dump, console.log).
- Eliminar todo comentario del código.
- Confirmar que las imágenes y GIF en uso existen, que cada GIF tiene su PNG estático del primer cuadro y que los tamaños son múltiplos de 64 px.
- Confirmar que npm run build y php artisan test pasan al final.
- Confirmar que el repositorio no contiene .env, claves ni archivos subidos por los usuarios.

---

## Parte H. Entrega

### H1. Primera entrega (solo lectura y pruebas, sin modificar nada)
Después de la Parte A, entrega un informe con:

1. Una tabla por módulo, con una fila por punto de las partes B, C, D, E y F, con las columnas: punto, estado (OK, INCOMPLETO, ROTO o FALTA), evidencia y archivo implicado en una línea.
2. Una lista de prioridades:
   - P0: pérdida de datos, fallos de seguridad y permisos que no se aplican.
   - P1: funciones rotas.
   - P2: funciones incompletas.
   - P3: detalles de pulido.
3. La lista de «Ideas» (mejoras que no son brechas).
4. Las decisiones pendientes que necesitas que yo tome.

Detente y espera mi OK.

### H2. Cierre de brechas (después de mi OK)
- Un módulo a la vez, en orden de prioridad.
- Un commit por módulo con un mensaje claro.
- Al terminar cada módulo, un resumen de cinco líneas: qué agregaste, qué archivos tocaste, qué pruebas corriste y qué quedó pendiente.
- Si una decisión cambia el diseño o la estructura, pregunta antes.
- Si algo no se puede verificar en este entorno, dilo claramente en vez de marcarlo como OK.
