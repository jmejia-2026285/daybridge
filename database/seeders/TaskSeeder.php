<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $people = User::query()->get()->keyBy('email');

        if ($people->isEmpty()) {
            return;
        }

        $creator = $people->get('test@example.com') ?? $people->first();

        $entries = [
            ['Revisar el inventario de equipo de cómputo', 'Confirmar existencias del taller y actualizar la hoja de control.', 'jose.barrios@daybridge.test', TaskStatus::InProgress, TaskPriority::High, 2, $creator],
            ['Preparar el reporte mensual de personas', 'Consolidar altas, bajas y cumpleaños del mes para la dirección.', 'sofia.marroquin@daybridge.test', TaskStatus::Pending, TaskPriority::High, -1, $creator],
            ['Actualizar el manual de bienvenida', 'Incluir el nuevo flujo de acceso a IT Desk y la agenda del taller.', 'karla.solis@daybridge.test', TaskStatus::Pending, TaskPriority::Medium, 5, $creator],
            ['Revisar accesos de la sala de capacitación', 'Validar que las cuentas temporales se desactiven al terminar el taller.', 'maria.castillo@daybridge.test', TaskStatus::InProgress, TaskPriority::Medium, 3, $creator],
            ['Ordenar facturas del proveedor de red', 'Clasificar las facturas pendientes antes del cierre contable.', 'ricardo.quinonez@daybridge.test', TaskStatus::Pending, TaskPriority::Low, -3, $creator],
            ['Publicar el resumen del mes en el muro', 'Preparar tres imágenes y el texto de cierre de mes.', 'gabriela.monzon@daybridge.test', TaskStatus::Completed, TaskPriority::Medium, -2, $creator],
            ['Programar mantenimiento de impresoras', 'Coordinar con el proveedor la visita trimestral.', 'fernanda.aguilar@daybridge.test', TaskStatus::Pending, TaskPriority::High, 1, $creator],
            ['Documentar el proceso de recepción de equipos', 'Dejar por escrito el flujo de tickets y comprobantes impresos.', 'luis.arevalo@daybridge.test', TaskStatus::Completed, TaskPriority::Low, -4, $creator],
        ];

        foreach ($entries as [$title, $description, $email, $status, $priority, $days, $createdBy]) {
            $assignee = $people->get($email) ?? $people->first();

            Task::query()->firstOrCreate(
                [
                    'title' => $title,
                    'assigned_to' => $assignee->id,
                ],
                [
                    'description' => $description,
                    'status' => $status,
                    'priority' => $priority,
                    'created_by' => $createdBy->id,
                    'due_date' => today()->addDays($days)->toDateString(),
                    'completed_at' => $status === TaskStatus::Completed ? now() : null,
                ],
            );
        }
    }
}
