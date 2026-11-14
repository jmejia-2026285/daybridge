<?php

namespace App\Livewire\Forms;

use App\Models\Equipment;
use Illuminate\Validation\Rule;
use Livewire\Form;

class EquipmentForm extends Form
{
    public string $type = '';

    public string $brand = '';

    public string $model = '';

    public string $serial = '';

    public string $client_name = '';

    public string $client_phone = '';

    public string $client_email = '';

    public string $issue = '';

    public string $diagnosis = '';

    public string $accessories = '';

    public string $notes = '';

    public ?int $received_by = null;

    public string $estimated_delivery_date = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:60'],
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:80'],
            'serial' => ['nullable', 'string', 'max:80'],
            'client_name' => ['required', 'string', 'max:100'],
            'client_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'client_email' => ['nullable', 'email', 'max:120'],
            'issue' => ['required', 'string', 'max:255'],
            'diagnosis' => ['nullable', 'string', 'max:1000'],
            'accessories' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            // Solo personal activo de IT Desk puede figurar como receptor.
            'received_by' => ['required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('is_active', true)->whereNotNull('it_desk_role'))],
            'estimated_delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'type' => 'tipo de equipo',
            'brand' => 'marca',
            'model' => 'modelo',
            'serial' => 'número de serie',
            'client_name' => 'nombre del cliente',
            'client_phone' => 'teléfono',
            'client_email' => 'correo',
            'issue' => 'motivo de la falla',
            'diagnosis' => 'diagnóstico',
            'accessories' => 'accesorios',
            'notes' => 'notas',
            'received_by' => 'técnico que recibe',
            'estimated_delivery_date' => 'fecha estimada de entrega',
        ];
    }

    public function fillFrom(Equipment $equipment): void
    {
        $this->type = $equipment->type;
        $this->brand = $equipment->brand;
        $this->model = $equipment->model;
        $this->serial = $equipment->serial ?? '';
        $this->client_name = $equipment->client_name;
        $this->client_phone = $equipment->client_phone;
        $this->client_email = $equipment->client_email ?? '';
        $this->issue = $equipment->issue;
        $this->diagnosis = $equipment->diagnosis ?? '';
        $this->accessories = $equipment->accessories ?? '';
        $this->notes = $equipment->notes ?? '';
        $this->received_by = $equipment->received_by;
        $this->estimated_delivery_date = $equipment->estimated_delivery_date?->format('Y-m-d') ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'type' => $this->type,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial' => $this->serial,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'client_email' => $this->client_email,
            'issue' => $this->issue,
            'diagnosis' => $this->diagnosis,
            'accessories' => $this->accessories,
            'notes' => $this->notes,
            'received_by' => $this->received_by,
            'estimated_delivery_date' => $this->estimated_delivery_date === '' ? null : $this->estimated_delivery_date,
        ];
    }
}
