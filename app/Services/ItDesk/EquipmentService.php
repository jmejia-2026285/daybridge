<?php

namespace App\Services\ItDesk;

use App\Enums\ServiceStatus;
use App\Models\Equipment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EquipmentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data, User $actor): Equipment
    {
        return DB::transaction(function () use ($data, $actor): Equipment {
            $receivedBy = $data['received_by'] ?? $actor->id;

            $equipment = Equipment::query()->create([
                'type' => $data['type'],
                'brand' => $data['brand'],
                'model' => $data['model'],
                'serial' => $data['serial'] ?? null,
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'],
                'client_email' => $data['client_email'] ?? null,
                'issue' => $data['issue'],
                'diagnosis' => $data['diagnosis'] ?? null,
                'accessories' => $data['accessories'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => ServiceStatus::Pending,
                'received_by' => $receivedBy,
                'received_at' => now(),
                'estimated_delivery_date' => $data['estimated_delivery_date'] ?? null,
            ]);

            Ticket::query()->create([
                'equipment_id' => $equipment->id,
                'assigned_to' => $receivedBy,
                'description' => $data['issue'],
                'status' => ServiceStatus::Pending,
                'opened_at' => now(),
            ]);

            return $equipment->fresh(['tickets', 'receivedBy']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Equipment $equipment, array $data): Equipment
    {
        $equipment->update([
            'type' => $data['type'],
            'brand' => $data['brand'],
            'model' => $data['model'],
            'serial' => $data['serial'] ?? null,
            'client_name' => $data['client_name'],
            'client_phone' => $data['client_phone'],
            'client_email' => $data['client_email'] ?? null,
            'issue' => $data['issue'],
            'diagnosis' => $data['diagnosis'] ?? null,
            'accessories' => $data['accessories'] ?? null,
            'notes' => $data['notes'] ?? null,
            'estimated_delivery_date' => $data['estimated_delivery_date'] ?? null,
        ]);

        return $equipment->fresh(['tickets', 'receivedBy']);
    }

    public function syncStatus(Equipment $equipment): void
    {
        $statuses = $equipment->tickets()
            ->pluck('status')
            ->map(fn (mixed $status): ServiceStatus => $status instanceof ServiceStatus
                ? $status
                : ServiceStatus::from((string) $status));

        $status = match (true) {
            $statuses->contains(ServiceStatus::InProgress) => ServiceStatus::InProgress,
            $statuses->contains(ServiceStatus::Pending) => ServiceStatus::Pending,
            $statuses->isNotEmpty() => ServiceStatus::Completed,
            default => ServiceStatus::Pending,
        };

        if ($equipment->status !== $status) {
            $equipment->update(['status' => $status]);
        }
    }
}
