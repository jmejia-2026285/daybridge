<?php

use App\Models\Equipment;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Mi equipo · IT Desk')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->is_active, 403);
    }

    #[Computed]
    public function equipment(): Collection
    {
        return Equipment::query()
            ->with([
                'tickets' => fn ($query) => $query
                    ->with('assignedTo')
                    ->orderByDesc('opened_at')
                    ->orderByDesc('id'),
            ])
            ->where('client_email', auth()->user()->email)
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function inServiceCount(): int
    {
        return $this->equipment
            ->reject(fn (Equipment $equipment): bool => $equipment->status->isCompleted())
            ->count();
    }

    #[Computed]
    public function completedCount(): int
    {
        return $this->equipment
            ->filter(fn (Equipment $equipment): bool => $equipment->status->isCompleted())
            ->count();
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
                <h1 class="mt-0.5 font-expanded text-3xl font-black tracking-display sm:text-4xl">Mi equipo</h1>
                <p class="mt-1 text-sm text-ink-muted">
                    Consulta el estado de los equipos registrados con tu correo.
                </p>
            </div>
        </div>
    </header>

    <section class="mt-6 grid grid-cols-3 divide-x divide-line border border-line bg-surface" aria-label="Resumen de mi equipo">
        <div class="px-4 py-4">
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Equipos</p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->equipment->count() }}</p>
        </div>

        <div class="px-4 py-4">
            <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                <span class="h-1.5 w-1.5 rounded-full bg-info"></span>
                En servicio
            </p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->inServiceCount }}</p>
        </div>

        <div class="px-4 py-4">
            <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                <span class="h-1.5 w-1.5 rounded-full bg-success"></span>
                Listos
            </p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->completedCount }}</p>
        </div>
    </section>

    @if ($this->equipment->isNotEmpty())
        <div class="mt-8 space-y-6">
            @foreach ($this->equipment as $item)
                <article class="border border-line bg-surface" wire:key="my-equipment-{{ $item->id }}">
                    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line px-5 py-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-condensed text-xl font-black tabular-nums">{{ $item->code }}</span>
                                <span class="inline-flex items-center gap-1.5 rounded-sm border px-2 py-1 text-3xs font-semibold tracking-label uppercase {{ $item->status->badgeClasses() }}">
                                    <span class="h-1 w-1 rounded-full {{ $item->status->dotClasses() }}"></span>
                                    {{ $item->status->label() }}
                                </span>
                            </div>

                            <h2 class="mt-1 font-expanded text-xl font-bold leading-tight tracking-tight">
                                {{ $item->displayName() }}
                            </h2>

                            <p class="mt-1 text-xs text-ink-muted">
                                Serie {{ $item->serial ?: 'sin registrar' }}
                                · Ingresó {{ $item->received_at->locale('es')->translatedFormat('d M Y, H:i') }}
                            </p>
                        </div>

                        <div class="text-left sm:text-right">
                            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Entrega estimada</p>
                            <p class="mt-0.5 font-condensed text-sm font-bold tabular-nums">
                                {{ $item->estimated_delivery_date?->locale('es')->translatedFormat('d M Y') ?? 'Por confirmar' }}
                            </p>
                        </div>
                    </header>

                    <div class="grid gap-6 px-5 py-5 lg:grid-cols-12">
                        <div class="space-y-4 lg:col-span-5">
                            <div>
                                <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Motivo de la falla</p>
                                <p class="mt-1 text-sm leading-relaxed">{{ $item->issue }}</p>
                            </div>

                            @if ($item->diagnosis)
                                <div>
                                    <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Diagnóstico</p>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink-secondary">
                                        {{ $item->diagnosis }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        <div class="lg:col-span-7">
                            <div class="flex items-baseline justify-between gap-3">
                                <h3 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Tickets</h3>
                                <span class="text-3xs text-ink-muted">
                                    {{ $item->tickets->count() }} {{ $item->tickets->count() === 1 ? 'ticket' : 'tickets' }}
                                </span>
                            </div>

                            <ul class="mt-2 divide-y divide-line border border-line">
                                @forelse ($item->tickets as $ticket)
                                    <li class="px-4 py-3" wire:key="my-ticket-{{ $ticket->id }}">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-condensed text-sm font-bold tabular-nums">{{ $ticket->folio }}</span>
                                                <span class="inline-flex items-center gap-1.5 rounded-sm border px-1.5 py-0.5 text-3xs font-semibold tracking-label uppercase {{ $ticket->status->badgeClasses() }}">
                                                    <span class="h-1 w-1 rounded-full {{ $ticket->status->dotClasses() }}"></span>
                                                    {{ $ticket->status->label() }}
                                                </span>
                                            </div>

                                            <span class="text-xs text-ink-muted">
                                                {{ $ticket->assignedTo?->name ?? 'Sin técnico asignado' }}
                                            </span>
                                        </div>

                                        <p class="mt-1 text-sm leading-relaxed text-ink-secondary">{{ $ticket->description }}</p>

                                        <p class="mt-1 text-xs text-ink-muted">
                                            Abierto {{ $ticket->opened_at->locale('es')->translatedFormat('d M Y, H:i') }}
                                            @if ($ticket->completed_at)
                                                · Completado {{ $ticket->completed_at->locale('es')->translatedFormat('d M Y, H:i') }}
                                            @endif
                                        </p>
                                    </li>
                                @empty
                                    <li class="px-4 py-4 text-sm text-ink-muted">
                                        Este equipo todavía no tiene tickets.
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="mt-8 flex flex-col items-start gap-5 border border-line bg-surface p-6 sm:flex-row sm:items-center">
            <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-28 w-auto">

            <div>
                <p class="font-expanded text-xl font-bold">No hay equipos registrados con tu correo.</p>
                <p class="mt-1 max-w-prose text-sm text-ink-muted">
                    Cuando IT Desk reciba un equipo, registrará tu correo como contacto y podrás seguir aquí su estado, diagnóstico y tickets.
                </p>
                <p class="mt-2 text-xs text-ink-muted">
                    Correo registrado: <span class="font-medium text-ink">{{ auth()->user()->email }}</span>
                </p>
            </div>
        </div>
    @endif

    <p class="mt-6 flex items-start gap-2 text-xs leading-relaxed text-ink-muted">
        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
        Solo se muestran equipos cuyo correo de contacto coincide con el de tu cuenta. Si falta alguno, pide a IT Desk que lo actualice.
    </p>
</div>
