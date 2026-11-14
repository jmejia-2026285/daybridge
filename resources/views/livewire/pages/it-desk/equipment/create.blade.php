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

new #[Layout('layouts.app')] #[Title('Registrar equipo · IT Desk')] class extends Component
{
    public EquipmentForm $form;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('create', Equipment::class), 403);

        $this->form->received_by = $this->technicians->firstWhere('id', auth()->id())?->id
            ?? $this->technicians->first()?->id;
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
        abort_unless(auth()->user()->can('create', Equipment::class), 403);

        $this->form->validate();

        $equipment = $equipmentService->register($this->form->payload(), auth()->user());

        session()->flash('status', "Equipo {$equipment->code} registrado con el ticket {$equipment->latestTicket?->folio}.");

        $this->redirectRoute('it-desk.equipment.show', $equipment, navigate: true);
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="border-b border-line pb-5">
        <a
            href="{{ route('it-desk.equipment.index') }}"
            wire:navigate
            class="inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
        >
            <x-icon name="arrow-left" class="h-4 w-4" />
            Equipos
        </a>

        <h1 class="mt-2 font-expanded text-3xl font-black tracking-display sm:text-4xl">Registrar equipo</h1>
        <p class="mt-2 max-w-prose text-sm text-ink-muted">
            Al guardar se genera el código interno y se abre un ticket de seguimiento en estado pendiente.
        </p>
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

                <p class="mt-4 border-t border-line pt-3 text-xs leading-relaxed text-ink-muted">
                    El ticket se abre a nombre del técnico que recibe. Podrás reasignarlo desde el detalle del equipo.
                </p>

                <div class="mt-5 flex flex-col gap-2">
                    <x-primary-button>
                        <x-icon name="check" class="h-4 w-4" />
                        Registrar equipo
                    </x-primary-button>

                    <a
                        href="{{ route('it-desk.equipment.index') }}"
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
