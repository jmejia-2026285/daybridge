<?php

use App\Enums\ServiceStatus;
use App\Models\Equipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Equipos · IT Desk')] class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'estado')]
    public string $status = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', Equipment::class), 403);
    }

    #[Computed]
    public function equipment(): Collection
    {
        return Equipment::query()
            ->with(['latestTicket.assignedTo', 'receivedBy'])
            ->search($this->search)
            ->when($this->status !== '', fn (Builder $query): Builder => $query->where('status', $this->status))
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->get();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';

        unset($this->equipment);
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">IT Desk</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Equipos</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Cada ingreso genera un código interno y un ticket de seguimiento.
            </p>
        </div>

        <a
            href="{{ route('it-desk.equipment.create') }}"
            wire:navigate
            class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
        >
            <x-icon name="plus" class="h-4 w-4" />
            Registrar equipo
        </a>
    </header>

    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
        <label class="relative flex-1">
            <span class="sr-only">Buscar equipos</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" />
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por código, serie, cliente o equipo"
                class="w-full rounded-md border-line bg-surface py-2.5 pl-9 pr-3 text-sm placeholder:text-ink-disabled focus:border-brand focus:ring-brand"
            >
        </label>

        <label class="sm:w-52">
            <span class="sr-only">Filtrar por estado</span>
            <select wire:model.live="status" class="w-full rounded-md border-line bg-surface py-2.5 text-sm focus:border-brand focus:ring-brand">
                <option value="">Todos los estados</option>
                @foreach (ServiceStatus::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>

        @if ($search !== '' || $status !== '')
            <button
                type="button"
                wire:click="clearFilters"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-3 text-xs font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
            >
                <x-icon name="x" class="h-4 w-4" />
                Limpiar
            </button>
        @endif
    </div>

    <div class="mt-6 hidden grid-cols-12 gap-4 border-b border-line pb-2 text-3xs font-semibold tracking-label text-ink-muted uppercase md:grid">
        <span class="col-span-4">Equipo</span>
        <span class="col-span-3">Ticket</span>
        <span class="col-span-3">Cliente</span>
        <span class="col-span-2 text-right">Estado</span>
    </div>

    @if ($this->equipment->isNotEmpty())
        <ul class="divide-y divide-line">
            @foreach ($this->equipment as $equipment)
                <li wire:key="equipment-{{ $equipment->id }}">
                    <a
                        href="{{ route('it-desk.equipment.show', $equipment) }}"
                        wire:navigate
                        class="group grid grid-cols-1 gap-3 py-4 transition duration-fast ease-signal md:grid-cols-12 md:items-center md:gap-4"
                    >
                        <div class="col-span-4 flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-line bg-sunken text-ink-muted">
                                <x-icon name="monitor" class="h-4 w-4" />
                            </span>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-condensed text-sm font-bold tabular-nums">{{ $equipment->code }}</span>
                                    <span class="truncate text-sm font-medium group-hover:text-accent-strong">{{ $equipment->displayName() }}</span>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-ink-muted">
                                    Serie {{ $equipment->serial ?: 'sin registrar' }}
                                </p>
                                <p class="mt-0.5 text-xs text-ink-muted">
                                    Ingresó {{ $equipment->received_at->locale('es')->translatedFormat('d M Y') }}
                                </p>
                            </div>
                        </div>

                        <div class="col-span-3 min-w-0">
                            <p class="font-condensed text-sm font-bold tabular-nums">
                                {{ $equipment->latestTicket?->folio ?? 'Sin ticket' }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-ink-muted">
                                {{ $equipment->latestTicket?->assignedTo?->name ?? 'Sin técnico' }}
                            </p>
                        </div>

                        <div class="col-span-3 min-w-0">
                            <p class="truncate text-sm">{{ $equipment->client_name }}</p>
                            <p class="mt-0.5 truncate text-xs text-ink-muted">{{ $equipment->client_phone }}</p>
                        </div>

                        <div class="col-span-2 flex items-center justify-between gap-3 md:justify-end">
                            <span class="inline-flex items-center gap-1.5 rounded-sm border px-2 py-1 text-3xs font-semibold tracking-label uppercase {{ $equipment->status->badgeClasses() }}">
                                <span class="h-1 w-1 rounded-full {{ $equipment->status->dotClasses() }}"></span>
                                {{ $equipment->status->label() }}
                            </span>
                            <x-icon name="chevron-right" class="h-4 w-4 text-ink-muted transition duration-fast ease-signal group-hover:translate-x-0.5" />
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <div class="flex flex-col items-start gap-5 py-14 sm:flex-row sm:items-center">
            <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-32 w-auto">
            <div>
                <p class="font-expanded text-2xl font-bold">No hay equipos con ese filtro.</p>
                <p class="mt-1 max-w-prose text-sm text-ink-muted">
                    Registra un ingreso o limpia los filtros para ver todo el taller.
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('it-desk.equipment.create') }}" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground">
                        <x-icon name="plus" class="h-4 w-4" />
                        Registrar equipo
                    </a>
                    <button type="button" wire:click="clearFilters" class="inline-flex min-h-10 items-center rounded-md border border-line px-4 text-sm font-medium">
                        Limpiar filtros
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
