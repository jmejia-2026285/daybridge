# Daybridge: dirección creativa y sistema visual

## Contexto técnico
- Proyecto Laravel con starter kit Livewire (componentes en archivos separados, sin Volt), autenticación integrada de Laravel y Tailwind CSS.
- Los estilos y el JS se compilan con Vite (`npm run dev`).
- Respeta la estructura del proyecto. No instales librerías de UI adicionales sin consultarme.
- Puedes reemplazar o personalizar los componentes por defecto del starter kit para lograr una identidad propia.
- El código no lleva comentarios explicativos. Si una clase o archivo no se usa, bórralo, no lo dejes vacío.

## Qué es Daybridge
Plataforma de comunicación y experiencia del empleado para toda la empresa, no solo para Recursos Humanos. Cada área (operaciones, TI, administración, RRHH, etc.) puede comunicar y compartir información. Es un proyecto de portfolio y no debe copiar la identidad visual de ninguna otra aplicación, incluida la app de calendario llamada Daybridge.

Los módulos pueden crecer con el tiempo. Diseña un sistema que escale a nuevos módulos sin rehacerse.

Módulos previstos:
- Inicio y feed de comunicados
- Publicaciones por área o departamento
- Cumpleaños y celebraciones
- Directorio de personas
- Eventos y calendario
- Cursos y aprendizaje
- Encuestas
- Documentos compartidos
- Notificaciones
- Administración y analíticas

Roles: admin y empleado, como mínimo.

## 1. Filosofía de diseño
Un producto tecnológico premium, moderno, humano y cuidado. Debe transmitir:
- Profesionalismo sin parecer una herramienta corporativa anticuada.
- Cercanía y sentido de comunidad.
- Claridad visual con mucha información.
- Personalidad sin sacrificar usabilidad.
- Buena experiencia en escritorio y móvil.

Que se sienta como un producto real con identidad propia, no como un dashboard genérico con otro logotipo.

## 2. Prohibido: diseño SaaS genérico
- Sidebars enormes y convencionales.
- Dashboards de tarjetas blancas idénticas.
- Cuadrículas repetitivas de estadísticas sin jerarquía.
- Gradientes morados y azules por defecto.
- Sombras excesivas, bordes redondeados en todo y efectos de vidrio innecesarios.
- Iconografía inconsistente.
- Tablas de aspecto administrativo antiguo.
- Botones, formularios y modales con apariencia predeterminada.
- Interfaces que parezcan generadas automáticamente.

## 3. Lenguaje visual
- Composición editorial y jerarquía tipográfica marcada.
- Espacios negativos bien usados.
- Contraste entre superficies y niveles de información.
- Distribuciones asimétricas cuando aporten valor.
- Elementos distintivos para noticias, eventos y comunicados.
- Microinteracciones discretas y transiciones fluidas.
- Paleta reducida, consistente y con personalidad. Justifica la elección en pocas líneas. Sin colores aleatorios por módulo.

Define tokens (variables CSS / tema de Tailwind) para:
- Colores primario, secundario y de acento.
- Fondos y superficies.
- Texto principal, secundario y deshabilitado.
- Bordes y separadores.
- Estados: éxito, advertencia, error e información.
- Tipografía (escalas y pesos), espaciado, radios, elevaciones, transiciones.

Soporte para modo claro y oscuro.

## 4. Experiencias principales
Cada módulo tiene una estructura adaptada a su propósito. No repitas el mismo patrón.

**Inicio / Feed:** espacio de comunicación vivo, con jerarquía editorial, contenido destacado, actividad reciente sin saturar y accesos rápidos integrados. Sin cuadrícula de estadísticas.

**Cumpleaños y celebraciones:** experiencia cálida. Destacar los del día y los próximos, con foto, nombre y fecha, y permitir felicitar. No es una tabla de empleados.

**Directorio:** búsqueda rápida, filtros intuitivos, perfiles visuales, jerarquía clara entre nombre, puesto, departamento y contacto. No solo tablas.

**Eventos:** próximos eventos con jerarquía, calendario limpio, categorías diferenciadas, inscripción y detalles sin recorridos largos.

**Cursos y aprendizaje:** presentación visual, progreso discreto, estados pendiente, en progreso y completado.

**Administración y analíticas:** interfaz profesional y densa cuando haga falta. Visualizaciones solo si aportan información real. Separada claramente de la experiencia diaria del empleado.

## 5. Componentes reutilizables con identidad propia
Botones, campos de formulario, selectores y filtros, menús contextuales, modales, notificaciones, avatares, indicadores de estado, listas de actividad, tarjetas de contenido especializadas, navegación, y estados de carga, error y vacío. No reutilices el mismo componente visual donde se necesitan jerarquías distintas.

## 6. Responsive
Pensado para empleados que usan sobre todo el teléfono. La versión móvil no es el escritorio comprimido:
- Navegación cómoda con una mano.
- Jerarquía optimizada para pantalla pequeña.
- Formularios cómodos, feed fluido, directorio accesible.
- Sin desplazamiento horizontal accidental.

## 7. Validación antes de dar un módulo por terminado
1. Jerarquía visual.
2. Que no parezca plantilla genérica.
3. Consistencia con el sistema de diseño.
4. Estados vacíos, errores, carga y contenido real.
5. Escritorio y móvil.
6. Eliminar lo decorativo que no aporte.
7. Accesibilidad y funcionamiento.

## 8. Proceso obligatorio, por fases
Detente al final de cada fase y espera mi respuesta.

**Fase 1:** propón tres direcciones visuales claramente distintas, con su personalidad y lenguaje visual. No escribas código todavía.

**Fase 2:** con la dirección que elija, define el sistema de diseño y los tokens.

**Fase 3:** construye solo la pantalla de inicio y el módulo de cumpleaños, con datos de ejemplo (seeders). Valida ambos.

**Fase 4:** extiende el lenguaje visual al resto de módulos, uno por uno.