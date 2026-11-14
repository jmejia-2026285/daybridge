<?php

namespace App\Services\ItDesk;

use App\Enums\ServiceStatus;
use App\Enums\TicketPrintType;
use App\Models\Equipment;
use App\Models\Ticket;
use App\Models\TicketPrint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public function __construct(private readonly EquipmentService $equipmentService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function open(Equipment $equipment, array $data, User $actor): Ticket
    {
        return DB::transaction(function () use ($equipment, $data, $actor): Ticket {
            $ticket = Ticket::query()->create([
                'equipment_id' => $equipment->id,
                'assigned_to' => $data['assigned_to'] ?? $actor->id,
                'description' => $data['description'],
                'status' => ServiceStatus::Pending,
                'opened_at' => now(),
            ]);

            $this->equipmentService->syncStatus($equipment);

            return $ticket->fresh(['equipment', 'assignedTo']);
        });
    }

    public function changeStatus(Ticket $ticket, ServiceStatus $status): Ticket
    {
        if ($ticket->status === $status) {
            throw ValidationException::withMessages([
                'status' => "El ticket ya está en estado «{$status->label()}».",
            ]);
        }

        return DB::transaction(function () use ($ticket, $status): Ticket {
            $ticket->update([
                'status' => $status,
                'completed_at' => $status->isCompleted() ? now() : null,
            ]);

            $this->equipmentService->syncStatus($ticket->equipment);

            return $ticket->fresh(['equipment', 'assignedTo']);
        });
    }

    public function markPrinted(Ticket $ticket, User $actor): void
    {
        DB::transaction(function () use ($ticket, $actor): void {
            $ticket->update(['printed_at' => now()]);

            foreach (TicketPrintType::cases() as $type) {
                TicketPrint::query()->create([
                    'ticket_id' => $ticket->id,
                    'type' => $type,
                    'printed_by' => $actor->id,
                    'printed_at' => now(),
                ]);
            }
        });
    }
}
