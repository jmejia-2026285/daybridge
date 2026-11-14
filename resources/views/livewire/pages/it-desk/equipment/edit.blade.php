<?php

use App\Livewire\Forms\EquipmentForm;
use App\Models\Equipment;
use App\Models\User;
use App\Services\ItDesk\EquipmentService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Editar equipo · IT Desk')] class extends Component
{
    public Equipment $equipment;

    public EquipmentForm $form;

    public function mount(Equipment $equipment): void
    {
        abort_unless(auth()->user()->can('update', $equipment), 403);

        $this->equipment = $equipment;
        $this->form->fillFrom($equipment);
    }

    #[Computed]
    public function technicians(): Collection
    {
        return User::query()
            ->active()
            ->whereNotNull('it_desk_role')
            ->orderBy('name')
            ->get();
    }

    public function save(EquipmentService $equipmentService): void
    {
        abort_unless(auth()->user()->can('update', $this->equipment), 403);

        $this->form->validate();

        $equipmentService->update($this->equipment, $this->form->payload());

        session()->flash('status', "Los datos de {$this->equipment->code} se actualizaron.");

        $this->redirectRoute('it-desk.equipment.show', $this->equipment, navigate: true);
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="border-b border-line pb-5">
        <a
            href="{{ route('it-desk.equipment.show', $equipment) }}"
            wire:navigate
            class="inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
        >
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ $equipment->code }}
        </a>

        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="font-expanded text-3xl font-black tracking-display sm:text-4xl">Editar equipo</h1>
            <span class="inline-flex items-center gap-1.5 rounded-sm border px-2 py-1 text-3xs font-semibold tracking-label uppercase {{ $equipment->status->badgeClasses() }}">
                <span class="h-1 w-1 rounded-full {{ $equipment->status->dotClasses() }}"></span>
                {{ $equipment->status->label() }}
            </span>
        </div>

        <p class="mt-2 text-sm text-ink-muted">{{ $equipment->displayName() }} · {{ $equipment->client_name }}</p>
    </header>

    <form wire:submit="save" class="mt-6 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="space-y-8 lg:col-span-8">
            @include('livewire.pages.it-desk.equipment.partials.form-fields')
        </div>

        <aside class="lg:col-span-4">
            <div class="border border-line bg-surface p-5">
                <h2 class="text-3xs font-bold tracking-label text-ink-muted uppercase">Recepción</h2>

                <div class="mt-3">
                    <x-input-label for="received_by" value="Técnico que recibe" />
                    <select
                        id="received_by"
                        wire:model="form.received_by"
                        class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                    >
                        <option value="">Selecciona un técnico</option>
                        @foreach ($this->technicians as $technician)
                            <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('form.received_by')" />
                </div>

                <dl class="mt-4 space-y-2 border-t border-line pt-3 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-muted">Código</dt>
                        <dd class="font-condensed font-bold tabular-nums">{{ $equipment->code }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-muted">Ingreso</dt>
                        <dd class="font-condensed font-bold tabular-nums">{{ $equipment->received_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-ink-muted">Tickets</dt>
                        <dd class="font-condensed font-bold tabular-nums">{{ $equipment->tickets()->count() }}</dd>
                    </div>
                </dl>

                <div class="mt-5 flex flex-col gap-2">
                    <x-primary-button>
                        <x-icon name="check" class="h-4 w-4" />
                        Guardar cambios
                    </x-primary-button>

                    <a
                        href="{{ route('it-desk.equipment.show', $equipment) }}"
                        wire:navigate
                        class="inline-flex min-h-10 items-center justify-center rounded-md border border-line px-4 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                    >
                        Cancelar
                    </a>
                </div>
            </div>
        </aside>
    </form>
</div>
