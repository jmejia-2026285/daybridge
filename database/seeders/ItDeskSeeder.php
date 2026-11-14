<?php

namespace Database\Seeders;

use App\Enums\ServiceStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Services\ItDesk\EquipmentService;
use App\Services\ItDesk\TicketService;
use Illuminate\Database\Seeder;

class ItDeskSeeder extends Seeder
{
    public function run(): void
    {
        $technicians = User::query()
            ->whereNotNull('it_desk_role')
            ->orderBy('name')
            ->get();

        if ($technicians->isEmpty()) {
            return;
        }

        $equipmentService = app(EquipmentService::class);
        $ticketService = app(TicketService::class);

        $entries = [
            [
                'type' => 'Laptop',
                'brand' => 'Dell',
                'model' => 'Latitude 5440',
                'serial' => 'SN-8841-AX',
                'client_name' => 'Mariana Ordóñez',
                'client_phone' => '5555-1201',
                'client_email' => 'mariana.ordonez@daybridge.test',
                'issue' => 'No enciende después de una actualización del sistema.',
                'diagnosis' => 'Se revisará la batería y el cargador antes de reinstalar el firmware.',
                'accessories' => 'Cargador original y funda',
                'status' => ServiceStatus::InProgress,
                'days' => 2,
            ],
            [
                'type' => 'PC de escritorio',
                'brand' => 'HP',
                'model' => 'ProDesk 400 G7',
                'serial' => 'SN-2290-KP',
                'client_name' => 'Rodrigo Estrada',
                'client_phone' => '5555-3388',
                'client_email' => 'rodrigo.estrada@daybridge.test',
                'issue' => 'La pantalla se congela al abrir el sistema de facturación.',
                'diagnosis' => null,
                'accessories' => 'Cable de poder y mouse',
                'status' => ServiceStatus::Pending,
                'days' => 4,
            ],
            [
                'type' => 'Impresora',
                'brand' => 'Epson',
                'model' => 'EcoTank L3250',
                'serial' => 'SN-7712-QM',
                'client_name' => 'Lucía Cordero',
                'client_phone' => '5555-7744',
                'client_email' => 'lucia.cordero@daybridge.test',
                'issue' => 'Imprime con líneas y no reconoce el tanque de color.',
                'diagnosis' => 'Se limpiaron los cabezales y se reemplazó el tanque cian.',
                'accessories' => 'Cable USB y fuente',
                'status' => ServiceStatus::Completed,
                'days' => 0,
            ],
            [
                'type' => 'Monitor',
                'brand' => 'Lenovo',
                'model' => 'ThinkVision T24i',
                'serial' => 'SN-1155-BR',
                'client_name' => 'Esteban Rivas',
                'client_phone' => '5555-6612',
                'client_email' => 'esteban.rivas@daybridge.test',
                'issue' => 'Enciende intermitentemente y parpadea en el puerto HDMI.',
                'diagnosis' => null,
                'accessories' => 'Cable HDMI y base',
                'status' => ServiceStatus::Pending,
                'days' => 3,
            ],
            [
                'type' => 'Tablet',
                'brand' => 'Apple',
                'model' => 'iPad 9a generación',
                'serial' => 'SN-9043-TX',
                'client_name' => 'Camila Solares',
                'client_phone' => '5555-2090',
                'client_email' => 'camila.solares@daybridge.test',
                'issue' => 'La batería se agota en menos de dos horas.',
                'diagnosis' => 'Diagnóstico de batería completado; se solicita reemplazo.',
                'accessories' => 'Cargador y funda con teclado',
                'status' => ServiceStatus::Completed,
                'days' => 1,
            ],
            [
                'type' => 'Router',
                'brand' => 'TP-Link',
                'model' => 'Archer AX55',
                'serial' => 'SN-5521-LN',
                'client_name' => 'Sala de capacitación',
                'client_phone' => '5555-0001',
                'client_email' => 'capacitacion@daybridge.test',
                'issue' => 'La red inalámbrica se cae cuando hay más de diez equipos conectados.',
                'diagnosis' => null,
                'accessories' => 'Fuente y cable de red',
                'status' => ServiceStatus::InProgress,
                'days' => 2,
            ],
        ];

        foreach ($entries as $index => $entry) {
            $technician = $technicians[$index % $technicians->count()];

            $equipment = $equipmentService->register([
                'type' => $entry['type'],
                'brand' => $entry['brand'],
                'model' => $entry['model'],
                'serial' => $entry['serial'],
                'client_name' => $entry['client_name'],
                'client_phone' => $entry['client_phone'],
                'client_email' => $entry['client_email'],
                'issue' => $entry['issue'],
                'diagnosis' => $entry['diagnosis'],
                'accessories' => $entry['accessories'],
                'notes' => null,
                'received_by' => $technician->id,
                'estimated_delivery_date' => today()->addDays($entry['days'])->toDateString(),
            ], $technician);

            $ticket = $equipment->latestTicket;

            if ($entry['status'] !== ServiceStatus::Pending) {
                $ticketService->changeStatus($ticket, $entry['status']);
            }

            if ($index === 1 || $index === 5) {
                $ticketService->open($equipment->fresh(), [
                    'description' => 'Seguimiento: revisar el rendimiento después de la reparación.',
                    'assigned_to' => $technicians[($index + 1) % $technicians->count()]->id,
                ], $technician);
            }
        }

        $appointments = [
            ['title' => 'Diagnóstico de laptop Dell', 'description' => 'Revisión de batería y firmware.', 'time' => '09:00', 'day' => 0, 'completed' => true],
            ['title' => 'Entrega de impresora Epson', 'description' => 'Entrega a Lucía Cordero en recepción.', 'time' => '11:30', 'day' => 0, 'completed' => false],
            ['title' => 'Mantenimiento de red', 'description' => 'Revisión de access points en sala de capacitación.', 'time' => '15:00', 'day' => 1, 'completed' => false],
            ['title' => 'Revisión de tablet', 'description' => 'Cambio de batería de iPad.', 'time' => '10:00', 'day' => 2, 'completed' => false],
            ['title' => 'Inventario de equipos', 'description' => 'Conteo mensual del taller.', 'time' => '16:30', 'day' => 3, 'completed' => false],
        ];

        foreach ($appointments as $index => $appointment) {
            Appointment::query()->firstOrCreate(
                [
                    'technician_id' => $technicians[$index % $technicians->count()]->id,
                    'title' => $appointment['title'],
                    'scheduled_date' => today()->addDays($appointment['day'])->toDateString(),
                ],
                [
                    'description' => $appointment['description'],
                    'scheduled_time' => $appointment['time'],
                    'completed' => $appointment['completed'],
                ],
            );
        }
    }
}
