<?php

use App\Enums\ServiceStatus;
use App\Models\Appointment;
use App\Models\Equipment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('IT Desk · Daybridge')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', Equipment::class), 403);
    }

    #[Computed]
    public function equipmentCounts(): array
    {
        $counts = Equipment::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($counts[ServiceStatus::Pending->value] ?? 0),
            'in_progress' => (int) ($counts[ServiceStatus::InProgress->value] ?? 0),
            'completed' => (int) ($counts[ServiceStatus::Completed->value] ?? 0),
            'total' => (int) $counts->sum(),
        ];
    }

    #[Computed]
    public function openTickets(): int
    {
        return Ticket::query()
            ->whereIn('status', [ServiceStatus::Pending, ServiceStatus::InProgress])
            ->count();
    }

    #[Computed]
    public function recentTickets(): Collection
    {
        return Ticket::query()
            ->with(['equipment', 'assignedTo'])
            ->orderByDesc('opened_at')
            ->orderByDesc('id')
            ->take(8)
            ->get();
    }

    #[Computed]
    public function todayAppointments(): Collection
    {
        $user = auth()->user();

        return Appointment::query()
            ->with('technician')
            ->whereDate('scheduled_date', today())
            ->when(
                ! ($user->isAdmin() || $user->isItDeskAdmin()),
                fn ($query) => $query->where('technician_id', $user->id),
            )
            ->orderBy('scheduled_time')
            ->get();
    }

    #[Computed]
    public function technicians(): Collection
    {
        return User::query()
            ->whereNotNull('it_desk_role')
            ->orderBy('name')
            ->get();
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-5 border-b border-line pb-6">
        <div class="flex items-center gap-4">
            <img
                src="{{ asset('media/it-desk/01_logo_horizontal_transparente.png') }}"
                alt="IT Desk"
                class="h-9 w-auto dark:brightness-0 dark:invert"
            >

            <div>
                <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Mesa de servicio</p>
                <h1 class="mt-0.5 font-expanded text-3xl font-black tracking-display sm:text-4xl">IT Desk</h1>
                <p class="mt-1 text-sm text-ink-muted">Equipos, tickets y agenda del taller.</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('it-desk.agenda') }}"
                wire:navigate
                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3.5 text-sm font-medium text-ink transition duration-fast ease-signal hover:bg-sunken"
            >
                <x-icon name="calendar" class="h-4 w-4" />
                Agenda
            </a>

            <a
                href="{{ route('it-desk.equipment.index') }}"
                wire:navigate
                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3.5 text-sm font-medium text-ink transition duration-fast ease-signal hover:bg-sunken"
            >
                <x-icon name="monitor" class="h-4 w-4" />
                Equipos
            </a>

            <a
                href="{{ route('it-desk.inventory.index') }}"
                wire:navigate
                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3.5 text-sm font-medium text-ink transition duration-fast ease-signal hover:bg-sunken"
            >
                <x-icon name="clipboard" class="h-4 w-4" />
                Inventario
            </a>

            <a
                href="{{ route('it-desk.equipment.create') }}"
                wire:navigate
                class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Registrar equipo
            </a>
        </div>
    </header>

    <section class="mt-6 grid grid-cols-2 divide-line bubble-glass sm:grid-cols-4 sm:divide-x" aria-label="Resumen del taller">
        <div class="border-b border-line px-4 py-4 sm:border-b-0">
            <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                <span class="h-1.5 w-1.5 rounded-full {{ ServiceStatus::Pending->dotClasses() }}"></span>
                Pendientes
            </p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->equipmentCounts['pending'] }}</p>
        </div>

        <div class="border-b border-line px-4 py-4 sm:border-b-0">
            <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                <span class="h-1.5 w-1.5 rounded-full {{ ServiceStatus::InProgress->dotClasses() }}"></span>
                En proceso
            </p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->equipmentCounts['in_progress'] }}</p>
        </div>

        <div class="px-4 py-4">
            <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                <span class="h-1.5 w-1.5 rounded-full {{ ServiceStatus::Completed->dotClasses() }}"></span>
                Completados
            </p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->equipmentCounts['completed'] }}</p>
        </div>

        <div class="px-4 py-4">
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Equipos</p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->equipmentCounts['total'] }}</p>
            <p class="mt-1 text-3xs text-ink-muted">{{ $this->openTickets }} tickets abiertos</p>
        </div>
    </section>

    <div class="mt-8 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <section class="lg:col-span-8">
            <div class="flex items-center gap-3 border-t border-line pt-4">
                <span class="text-3xs font-bold tracking-label text-ink-muted uppercase">Actividad reciente</span>
                <span class="h-px flex-1 bg-line"></span>
                <a href="{{ route('it-desk.equipment.index') }}" wire:navigate class="text-3xs font-medium text-ink-muted transition duration-fast ease-signal hover:text-accent-strong">
                    Ver equipos
                </a>
            </div>

            @if ($this->recentTickets->isNotEmpty())
                <ul class="divide-y divide-line">
                    @foreach ($this->recentTickets as $ticket)
                        <li wire:key="recent-ticket-{{ $ticket->id }}" class="bg-neutral-900/70 backdrop-blur-md border border-white/10 rounded-xl p-4 shadow-lg text-white">
                            <a
                                href="{{ route('it-desk.equipment.show', $ticket->equipment) }}"
                                wire:navigate
                                class="group flex flex-col gap-2 transition duration-fast ease-signal sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-condensed text-sm font-bold tabular-nums text-ink">{{ $ticket->folio }}</span>
                                        <span class="inline-flex items-center gap-1.5 rounded-sm border px-1.5 py-0.5 text-3xs font-semibold tracking-label uppercase {{ $ticket->status->badgeClasses() }}">
                                            <span class="h-1 w-1 rounded-full {{ $ticket->status->dotClasses() }}"></span>
                                            {{ $ticket->status->label() }}
                                        </span>
                                    </div>

                                    <p class="mt-1 truncate text-sm font-medium group-hover:text-accent-strong">
                                        {{ $ticket->equipment->displayName() }}
                                    </p>
                                    <p class="truncate text-xs text-ink-muted">
                                        {{ $ticket->equipment->client_name }} · {{ $ticket->equipment->code }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-3 text-xs text-ink-muted">
                                    <span>{{ $ticket->assignedTo?->name ?? 'Sin técnico' }}</span>
                                    <span class="font-condensed tabular-nums">{{ $ticket->opened_at->locale('es')->translatedFormat('d M · H:i') }}</span>
                                    <x-icon name="chevron-right" class="h-4 w-4 transition duration-fast ease-signal group-hover:translate-x-0.5" />
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-start gap-4 py-10 sm:flex-row sm:items-center">
                    <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-28 w-auto">
                    <div>
                        <p class="font-expanded text-xl font-bold">El taller está en calma.</p>
                        <p class="mt-1 text-sm text-ink-muted">Registra el primer equipo para abrir un ticket.</p>
                        <a href="{{ route('it-desk.equipment.create') }}" wire:navigate class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-strong">
                            Registrar equipo
                            <x-icon name="arrow-right" class="h-4 w-4" />
                        </a>
                    </div>
                </div>
            @endif
        </section>

        <aside class="lg:col-span-4">
            <div class="bubble-glass">
                <header class="flex items-baseline justify-between gap-3 border-b border-line px-4 py-3">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Agenda de hoy</h2>
                    <a href="{{ route('it-desk.agenda') }}" wire:navigate class="text-3xs font-medium text-ink-muted transition duration-fast ease-signal hover:text-accent-strong">
                        Ver agenda
                    </a>
                </header>

                @forelse ($this->todayAppointments as $appointment)
                    <div class="border-b border-line px-4 py-3 last:border-b-0" wire:key="today-appointment-{{ $appointment->id }}">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="font-condensed text-lg font-black tabular-nums">{{ $appointment->timeLabel() }}</p>
                            <span @class([
                                'text-3xs font-semibold tracking-label uppercase',
                                'text-success' => $appointment->completed,
                                'text-ink-muted' => ! $appointment->completed,
                            ])>
                                {{ $appointment->completed ? 'Completada' : 'Pendiente' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm font-medium">{{ $appointment->title }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ $appointment->technician->name }}</p>
                    </div>
                @empty
                    <p class="px-4 py-6 text-sm text-ink-muted">Sin citas para hoy.</p>
                @endforelse
            </div>

            <div class="mt-4 bubble-glass px-4 py-4">
                <h2 class="text-3xs font-bold tracking-label text-ink-muted uppercase">Técnicos</h2>

                @forelse ($this->technicians as $technician)
                    <div class="mt-3 flex items-center gap-3" wire:key="technician-{{ $technician->id }}">
                        <x-avatar :user="$technician" size="sm" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $technician->name }}</p>
                            <p class="text-xs text-ink-muted">{{ $technician->it_desk_role?->label() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-ink-muted">Aún no hay técnicos asignados.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
