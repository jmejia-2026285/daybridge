<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $areas = Area::query()->pluck('id', 'slug');
        $authors = User::query()->pluck('id', 'email');

        $posts = [
            [
                'title' => 'El puente que faltaba para comunicarnos mejor',
                'highlight' => 'puente',
                'area' => 'operaciones',
                'author' => 'diego.salazar@daybridge.test',
                'type' => Post::TYPE_NEWS,
                'excerpt' => 'Daybridge abre como un solo lugar para que todas las áreas cuenten lo que hacen y las personas encuentren lo que necesitan.',
                'body' => "Durante los últimos meses escuchamos al equipo. La información importante llegaba por correo, se perdía en hilos interminables o vivía en la cabeza de quien la había escrito. Hoy abrimos Daybridge, un espacio para que cada área cuente lo que hace y para que las personas encuentren lo que necesitan sin depender de una cadena de mensajes.\n\nLa idea es simple: una sola puerta de entrada a la comunicación interna. Operaciones, Personas, Tecnología, Finanzas y Crecimiento publican aquí. Tú decides qué leer y cuándo.\n\nEsta semana iremos sumando módulos: cumpleaños, directorio, eventos y formación. Si algo no se entiende, si falta una sección o si tienes una idea, escríbenos. Daybridge se construye entre todos.",
                'featured' => true,
                'urgent' => false,
                'published_at' => now()->subHours(2),
            ],
            [
                'title' => 'Mantenimiento del sistema de tickets este sábado',
                'highlight' => 'sábado',
                'area' => 'tecnologia',
                'author' => 'maria.castillo@daybridge.test',
                'type' => Post::TYPE_NOTICE,
                'excerpt' => 'El sábado, de 8 a 12, no se podrán crear ni actualizar tickets. Los tickets abiertos se migran solos.',
                'body' => "El sábado, entre las 8 y las 12, migramos el sistema de tickets a la nueva plataforma. Durante esa ventana no se podrán crear ni actualizar tickets.\n\nLos tickets abiertos se migran automáticamente, incluidos los adjuntos y el historial. Si tienes algo urgente que no puede esperar, usa el canal de guardia. El lunes revisamos juntos que no se haya perdido nada.",
                'featured' => false,
                'urgent' => true,
                'published_at' => now()->subHours(4),
            ],
            [
                'title' => 'Gracias, equipo de Operaciones',
                'highlight' => 'Gracias',
                'area' => 'personas',
                'author' => 'test@example.com',
                'type' => Post::TYPE_RECOGNITION,
                'excerpt' => 'La semana pasada Operaciones reorganizó tres turnos para cubrir una entrega que no podía esperar. Nadie tuvo que pedirlo dos veces.',
                'body' => 'La semana pasada Operaciones reorganizó tres turnos para cubrir una entrega que no podía esperar. Nadie tuvo que pedirlo dos veces. Gracias por cuidar el trabajo de todos, incluso cuando nadie está mirando.',
                'featured' => false,
                'urgent' => false,
                'published_at' => now()->subDay()->setTime(8, 30),
            ],
            [
                'title' => 'Horario flexible con un núcleo común',
                'highlight' => 'flexible',
                'area' => 'tecnologia',
                'author' => 'jose.barrios@daybridge.test',
                'type' => Post::TYPE_NEWS,
                'excerpt' => 'Plataforma prueba un esquema de horario flexible con un tramo compartido de 10 a 15 horas.',
                'body' => "A partir del próximo lunes, el equipo de Plataforma trabajará con un esquema de horario flexible. El único tramo compartido será de 10 a 15 horas, cuando concentramos las revisiones, las decisiones y las sesiones de diseño.\n\nFuera de ese núcleo, cada persona organiza su jornada según su energía y sus responsabilidades. Los acuerdos de respuesta no cambian: lo urgente se avisa por el canal de guardia y el resto puede esperar al siguiente día hábil.\n\nDurante el primer mes mediremos cómo se siente el equipo y ajustaremos lo que haga falta.",
                'featured' => false,
                'urgent' => false,
                'published_at' => now()->subDay()->setTime(11, 0),
            ],
            [
                'title' => 'Nuevo protocolo de viajes: menos pasos, mismos límites',
                'highlight' => 'protocolo',
                'area' => 'operaciones',
                'author' => 'estuardo.lemus@daybridge.test',
                'type' => Post::TYPE_NOTICE,
                'excerpt' => 'Los viajes de menos de tres días ya no necesitan una segunda aprobación si están dentro del presupuesto del área.',
                'body' => "Desde este mes, los viajes de menos de tres días no necesitan una segunda aprobación si están dentro del presupuesto del área. La solicitud se hace en el mismo formulario de siempre y Operaciones confirma en menos de 48 horas.\n\nTambién unificamos los límites de alojamiento: todas las áreas usan la misma tabla. Si tienes un viaje ya aprobado, no necesitas hacer nada.",
                'featured' => false,
                'urgent' => false,
                'published_at' => now()->subDays(2)->setTime(9, 15),
            ],
            [
                'title' => 'El trimestre cierra por encima del objetivo',
                'highlight' => 'objetivo',
                'area' => 'finanzas',
                'author' => 'luis.arevalo@daybridge.test',
                'type' => Post::TYPE_NEWS,
                'excerpt' => 'Cerramos con un crecimiento del 12 % en ingresos y una mejora de dos puntos en el margen operativo.',
                'body' => "Cerramos el trimestre con un crecimiento del 12 % en ingresos y una mejora de dos puntos en el margen operativo. El resultado no viene de una sola palanca: viene de muchas decisiones pequeñas tomadas por equipos distintos.\n\nOperaciones redujo los tiempos de entrega, Crecimiento mejoró la conversión de las campañas y Tecnología eliminó tres procesos manuales que consumían horas cada semana.\n\nEl próximo martes compartiremos el detalle completo en una sesión abierta.",
                'featured' => false,
                'urgent' => false,
                'published_at' => now()->subDays(2)->setTime(16, 0),
            ],
            [
                'title' => 'Karla convirtió una duda en un programa completo',
                'highlight' => 'programa',
                'area' => 'personas',
                'author' => 'test@example.com',
                'type' => Post::TYPE_RECOGNITION,
                'excerpt' => 'Karla preguntó por qué no teníamos un espacio para compartir lo que aprendemos. Dos semanas después, el primer taller ya tiene lista de espera.',
                'body' => 'Karla preguntó por qué no teníamos un espacio para compartir lo que aprendemos. Dos semanas después, el primer taller ya tiene lista de espera. Así se empieza un programa: con una pregunta honesta y alguien que se anima a responderla.',
                'featured' => false,
                'urgent' => false,
                'published_at' => now()->subDays(3)->setTime(10, 0),
            ],
            [
                'title' => 'Mentoría interna: seis sesiones para crecer',
                'highlight' => 'Mentoría',
                'area' => 'personas',
                'author' => 'sofia.marroquin@daybridge.test',
                'type' => Post::TYPE_NEWS,
                'excerpt' => 'Buscamos mentores y personas que quieran aprender. Seis sesiones, objetivos claros y acompañamiento real.',
                'body' => "La mentoría no tiene que ser un programa formal para funcionar, pero un poco de estructura ayuda. Abrimos una primera edición con seis sesiones repartidas en tres meses.\n\nPuedes participar como mentor si llevas más de un año en la empresa y quieres compartir lo que sabes. También puedes apuntarte como mentee si tienes un objetivo concreto: cambiar de disciplina, preparar un rol nuevo o ganar confianza en una responsabilidad que ya tienes.\n\nLas inscripciones están abiertas hasta el viernes.",
                'featured' => false,
                'urgent' => false,
                'published_at' => now()->subDays(3)->setTime(14, 30),
            ],
            [
                'title' => 'Campaña de fin de año: necesitamos tu historia',
                'highlight' => 'historia',
                'area' => 'crecimiento',
                'author' => 'andrea.figueroa@daybridge.test',
                'type' => Post::TYPE_NOTICE,
                'excerpt' => 'Este año la campaña se construye con historias reales de personas y equipos. Comparte la tuya antes del miércoles.',
                'body' => "La campaña de fin de año no va de productos ni de descuentos. Va de las personas que hacen posible lo que hacemos. Por eso queremos contar historias reales: un turno que salió bien, una idea que mejoró un proceso, un equipo que se apoyó en un momento difícil.\n\nSi tienes una historia que merece contarse, compártela en el formulario de Crecimiento. Nosotros ayudamos con la edición. El plazo termina el miércoles y participar es siempre opcional.",
                'featured' => false,
                'urgent' => false,
                'published_at' => now()->subDays(4)->setTime(9, 45),
            ],
            [
                'title' => 'Presupuesto 2027: preguntas antes de cerrar',
                'highlight' => 'preguntas',
                'area' => 'finanzas',
                'author' => 'ricardo.quinonez@daybridge.test',
                'type' => Post::TYPE_NEWS,
                'excerpt' => 'Compartimos las prioridades del próximo año y abrimos una sesión para responder dudas antes de cerrar el presupuesto.',
                'body' => "El presupuesto de 2027 se está construyendo con las prioridades que cada área compartió en octubre. Antes de cerrarlo, queremos explicar los criterios y escuchar dudas.\n\nLa sesión será el jueves a las 16 horas y durará una hora. No hace falta preparar nada; si tienes una pregunta, llévala. También puedes enviarla antes por el canal de Finanzas.\n\nLas decisiones finales se comunicarán la semana siguiente junto con el calendario de inversión.",
                'featured' => false,
                'urgent' => false,
                'published_at' => now()->subDays(5)->setTime(8, 0),
            ],
        ];

        foreach ($posts as $post) {
            Post::query()->updateOrCreate(['title' => $post['title']], [
                'area_id' => $areas[$post['area']],
                'user_id' => $authors[$post['author']],
                'highlight' => $post['highlight'],
                'excerpt' => $post['excerpt'],
                'body' => $post['body'],
                'type' => $post['type'],
                'is_featured' => $post['featured'],
                'is_urgent' => $post['urgent'],
                'published_at' => $post['published_at'],
            ]);
        }
    }
}
