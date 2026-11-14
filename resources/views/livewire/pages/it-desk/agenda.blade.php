<?php

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Agenda · IT Desk')] class extends Component
{
    public string $view = 'day';

    public string $anchorDate = '';

    public string $technicianFilter = '';

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $scheduledDate = '';

    public string $scheduledTime = '09:00';

    public ?int $technicianId = null;

    public string $statusMessage = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', Appointment::class), 403);

        $this->anchorDate = today()->toDateString();
        $this->scheduledDate = $this->anchorDate;
        $this->technicianId = $this->defaultTechnicianId();
        $this->technicianFilter = $this->canManageOthers() ? '' : (string) auth()->id();
    }

    public function canManageOthers(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isItDeskAdmin();
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

    #[Computed]
    public function rangeStart(): Carbon
    {
        $anchor = Carbon::parse($this->anchorDate)->startOfDay();

        return $this->view === 'week' ? $anchor->startOfWeek(Carbon::MONDAY) : $anchor;
    }

    #[Computed]
    public function rangeEnd(): Carbon
    {
        return $this->view === 'week'
            ? $this->rangeStart->copy()->endOfWeek(Carbon::SUNDAY)
            : $this->rangeStart->copy()->endOfDay();
    }

    #[Computed]
    public function rangeLabel(): string
    {
        if ($this->view === 'week') {
            return 'Semana del '
                .$this->rangeStart->locale('es')->translatedFormat('d M')
                .' al '.$this->rangeEnd->locale('es')->translatedFormat('d M Y');
        }

        return $this->capitalize($this->rangeStart->locale('es')->translatedFormat('l d \d\e F Y'));
    }

    #[Computed]
    public function appointments(): Collection
    {
        $filter = $this->canManageOthers() ? $this->technicianFilter : (string) auth()->id();

        return Appointment::query()
            ->with('technician')
            ->whereDate('scheduled_date', '>=', $this->rangeStart->toDateString())
            ->whereDate('scheduled_date', '<=', $this->rangeEnd->toDateString())
            ->when($filter !== '', fn ($query) => $query->where('technician_id', (int) $filter))
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, array{date: Carbon, appointments: Collection<int, Appointment>}>
     */
    #[Computed]
    public function days(): Collection
    {
        $days = collect();
        $cursor = $this->rangeStart->copy();
        $last = $this->rangeEnd->copy();

        while ($cursor->lessThanOrEqualTo($last)) {
            $date = $cursor->copy();

            $days->push([
                'date' => $date,
                'appointments' => $this->appointments
                    ->filter(fn (Appointment $appointment): bool => $appointment->scheduled_date->isSameDay($date))
                    ->values(),
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    #[Computed]
    public function todayAppointmentsCount(): int
    {
        $filter = $this->canManageOthers() ? $this->technicianFilter : (string) auth()->id();

        return Appointment::query()
            ->whereDate('scheduled_date', today())
            ->when($filter !== '', fn ($query) => $query->where('technician_id', (int) $filter))
            ->count();
    }

    public function setView(string $view): void
    {
        $this->view = $view === 'week' ? 'week' : 'day';
        $this->statusMessage = '';
        $this->forgetAgenda();
    }

    public function goToToday(): void
    {
        $this->anchorDate = today()->toDateString();
        $this->statusMessage = '';
        $this->forgetAgenda();
    }

    public function previous(): void
    {
        $this->shiftAnchor(-1);
    }

    public function next(): void
    {
        $this->shiftAnchor(1);
    }

    public function selectAppointment(int $appointmentId): void
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);

        abort_unless(auth()->user()->can('view', $appointment), 403);
        abort_unless($this->canManageOthers() || auth()->user()->is($appointment->technician), 403);

        $this->editingId = $appointment->id;
        $this->title = $appointment->title;
        $this->description = $appointment->description ?? '';
        $this->scheduledDate = $appointment->scheduled_date->toDateString();
        $this->scheduledTime = $appointment->timeLabel();
        $this->technicianId = $appointment->technician_id;
        $this->statusMessage = '';
        $this->resetValidation();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->description = '';
        $this->scheduledDate = $this->anchorDate !== '' ? $this->anchorDate : today()->toDateString();
        $this->scheduledTime = '09:00';
        $this->technicianId = $this->defaultTechnicianId();
        $this->statusMessage = '';
        $this->resetValidation();
    }

    public function save(): void
    {
        if (! $this->canManageOthers()) {
            $this->technicianId = auth()->id();
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'scheduledDate' => ['required', 'date'],
            'scheduledTime' => ['required', 'date_format:H:i'],
            'technicianId' => [
                'required',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereNotNull('it_desk_role')->where('is_active', true),
                ),
            ],
        ], [], [
            'title' => 'título',
            'description' => 'descripción',
            'scheduledDate' => 'fecha',
            'scheduledTime' => 'hora',
            'technicianId' => 'técnico',
        ]);

        $attributes = [
            'technician_id' => $validated['technicianId'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'scheduled_date' => $validated['scheduledDate'],
            'scheduled_time' => $validated['scheduledTime'],
        ];

        if ($this->editingId !== null) {
            $appointment = Appointment::query()->findOrFail($this->editingId);

            abort_unless(auth()->user()->can('update', $appointment), 403);

            $appointment->update($attributes);
            $message = "Cita «{$appointment->title}» actualizada.";
        } else {
            abort_unless(auth()->user()->can('create', Appointment::class), 403);

            $appointment = Appointment::query()->create($attributes);
            $message = "Cita «{$appointment->title}» creada.";
        }

        $this->anchorDate = $appointment->scheduled_date->toDateString();
        $this->resetForm();
        $this->statusMessage = $message;
        $this->forgetAgenda();
    }

    public function toggleCompleted(int $appointmentId): void
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);

        abort_unless(auth()->user()->can('update', $appointment), 403);

        $appointment->update(['completed' => ! $appointment->completed]);

        $this->statusMessage = $appointment->completed
            ? "Cita «{$appointment->title}» marcada como completada."
            : "Cita «{$appointment->title}» reabierta.";

        $this->forgetAgenda();
    }

    public function delete(int $appointmentId): void
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);

        abort_unless(auth()->user()->can('delete', $appointment), 403);

        $title = $appointment->title;

        $appointment->delete();

        if ($this->editingId === $appointmentId) {
            $this->resetForm();
        }

        $this->statusMessage = "Cita «{$title}» eliminada.";
        $this->forgetAgenda();
    }

    private function shiftAnchor(int $direction): void
    {
        $this->anchorDate = Carbon::parse($this->anchorDate)
            ->addDays($this->view === 'week' ? 7 * $direction : $direction)
            ->toDateString();

        $this->statusMessage = '';
        $this->forgetAgenda();
    }

    private function defaultTechnicianId(): ?int
    {
        if (! $this->canManageOthers()) {
            return auth()->id();
        }

        return $this->technicians->first()?->id;
    }

    private function forgetAgenda(): void
    {
        unset(
            $this->appointments,
            $this->days,
            $this->rangeStart,
            $this->rangeEnd,
            $this->rangeLabel,
            $this->todayAppointmentsCount,
        );
    }

    private function capitalize(string $value): string
    {
        return mb_strtoupper(mb_substr($value, 0, 1)).mb_substr($value, 1);
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <a
                href="{{ route('it-desk.dashboard') }}"
                wire:navigate
                class="inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
            >
                <x-icon name="arrow-left" class="h-4 w-4" />
                IT Desk
            </a>

            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Agenda</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Citas del taller por técnico, con vista de día o de semana.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <span class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink-muted uppercase">
                <x-icon name="calendar" class="h-4 w-4" />
                {{ $this->todayAppointmentsCount }} hoy
            </span>

            <button
                type="button"
                wire:click="resetForm"
                class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Nueva cita
            </button>
        </div>
    </header>

    @if ($statusMessage !== '')
        <p class="mt-4 border-l-2 border-brand bg-brand-soft px-3 py-2 text-sm text-brand-strong" role="status">
            {{ $statusMessage }}
        </p>
    @endif

    <div class="mt-6 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="lg:col-span-8">
            <div class="flex flex-col gap-3 border border-line bg-surface px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        wire:click="previous"
                        aria-label="{{ $view === 'week' ? 'Semana anterior' : 'Día anterior' }}"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-md border border-line text-ink transition duration-fast ease-signal hover:bg-sunken"
                    >
                        <x-icon name="arrow-left" class="h-4 w-4" />
                    </button>

                    <p class="min-w-0 flex-1 px-1 text-center font-condensed text-sm font-bold tabular-nums sm:min-w-56">
                        {{ $this->rangeLabel }}
                    </p>

                    <button
                        type="button"
                        wire:click="next"
                        aria-label="{{ $view === 'week' ? 'Semana siguiente' : 'Día siguiente' }}"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-md border border-line text-ink transition duration-fast ease-signal hover:bg-sunken"
                    >
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        wire:click="goToToday"
                        class="inline-flex min-h-10 items-center rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                    >
                        Hoy
                    </button>

                    <div class="inline-flex rounded-md border border-line p-0.5" role="group" aria-label="Vista de la agenda">
                        <button
                            type="button"
                            wire:click="setView('day')"
                            @class([
                                'min-h-9 rounded-sm px-3 text-xs font-semibold tracking-label uppercase transition duration-fast ease-signal',
                                'bg-ink text-canvas' => $view === 'day',
                                'text-ink-muted hover:text-ink' => $view !== 'day',
                            ])
                        >
                            Día
                        </button>
                        <button
                            type="button"
                            wire:click="setView('week')"
                            @class([
                                'min-h-9 rounded-sm px-3 text-xs font-semibold tracking-label uppercase transition duration-fast ease-signal',
                                'bg-ink text-canvas' => $view === 'week',
                                'text-ink-muted hover:text-ink' => $view !== 'week',
                            ])
                        >
                            Semana
                        </button>
                    </div>
                </div>
            </div>

            @if ($this->canManageOthers())
                <label class="mt-3 block sm:w-64">
                    <span class="sr-only">Filtrar por técnico</span>
                    <select
                        wire:model.live="technicianFilter"
                        class="w-full rounded-md border-line bg-surface py-2.5 text-sm focus:border-brand focus:ring-brand"
                    >
                        <option value="">Todos los técnicos</option>
                        @foreach ($this->technicians as $technician)
                            <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <div class="mt-4 space-y-3">
                @foreach ($this->days as $day)
                    <section
                        class="border border-line bg-surface"
                        wire:key="agenda-day-{{ $day['date']->toDateString() }}"
                    >
                        <header class="flex flex-wrap items-baseline justify-between gap-2 border-b border-line px-4 py-2.5">
                            <h2 class="font-expanded text-sm font-bold capitalize">
                                {{ $day['date']->locale('es')->translatedFormat('l d \d\e F') }}
                            </h2>
                            <span class="text-3xs tracking-label text-ink-muted uppercase">
                                {{ $day['appointments']->count() }} {{ $day['appointments']->count() === 1 ? 'cita' : 'citas' }}
                            </span>
                        </header>

                        @forelse ($day['appointments'] as $appointment)
                            <article
                                class="border-b border-line px-4 py-3 last:border-b-0"
                                wire:key="appointment-{{ $appointment->id }}"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-condensed text-lg font-black tabular-nums">{{ $appointment->timeLabel() }}</span>
                                            <span @class([
                                                'inline-flex items-center gap-1.5 rounded-sm border px-1.5 py-0.5 text-3xs font-semibold tracking-label uppercase',
                                                'border-success/30 bg-success-soft text-success' => $appointment->completed,
                                                'border-warning/30 bg-warning-soft text-warning' => ! $appointment->completed,
                                            ])>
                                                <span @class([
                                                    'h-1 w-1 rounded-full',
                                                    'bg-success' => $appointment->completed,
                                                    'bg-warning' => ! $appointment->completed,
                                                ])></span>
                                                {{ $appointment->completed ? 'Completada' : 'Pendiente' }}
                                            </span>
                                        </div>

                                        <p class="mt-1 text-sm font-semibold">{{ $appointment->title }}</p>

                                        @if ($appointment->description)
                                            <p class="mt-0.5 text-sm leading-relaxed text-ink-muted">{{ $appointment->description }}</p>
                                        @endif

                                        <div class="mt-1.5 flex items-center gap-2">
                                            <x-avatar :user="$appointment->technician" size="xs" />
                                            <p class="text-xs text-ink-muted">{{ $appointment->technician->name }}</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-1.5 sm:shrink-0">
                                        <button
                                            type="button"
                                            wire:click="selectAppointment({{ $appointment->id }})"
                                            class="inline-flex min-h-10 items-center gap-1.5 rounded-md border border-line px-3 text-xs font-semibold text-ink transition duration-fast ease-signal hover:bg-sunken"
                                        >
                                            <x-icon name="edit" class="h-4 w-4" />
                                            Editar
                                        </button>

                                        @can('update', $appointment)
                                            <button
                                                type="button"
                                                wire:click="toggleCompleted({{ $appointment->id }})"
                                                class="inline-flex min-h-10 items-center gap-1.5 rounded-md border border-line px-3 text-xs font-semibold text-ink transition duration-fast ease-signal hover:bg-sunken"
                                            >
                                                <x-icon name="{{ $appointment->completed ? 'refresh' : 'check' }}" class="h-4 w-4" />
                                                {{ $appointment->completed ? 'Reabrir' : 'Completar' }}
                                            </button>
                                        @endcan

                                        @can('delete', $appointment)
                                            <button
                                                type="button"
                                                wire:click="delete({{ $appointment->id }})"
                                                wire:confirm="¿Eliminar la cita «{{ $appointment->title }}»?"
                                                aria-label="Eliminar cita {{ $appointment->title }}"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-danger/30 text-danger transition duration-fast ease-signal hover:bg-danger-soft"
                                            >
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="px-4 py-5 text-sm text-ink-muted">Sin citas para este día.</p>
                        @endforelse
                    </section>
                @endforeach
            </div>
        </div>

        <aside class="lg:col-span-4">
            <form wire:submit="save" class="border border-line bg-surface p-5 lg:sticky lg:top-20">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">
                        {{ $editingId ? 'Editar cita' : 'Nueva cita' }}
                    </h2>

                    @if ($editingId)
                        <button
                            type="button"
                            wire:click="resetForm"
                            class="text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
                        >
                            Cancelar
                        </button>
                    @endif
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="title" value="Título" />
                        <x-text-input id="title" wire:model="title" class="mt-1.5" maxlength="100" placeholder="Diagnóstico, entrega, mantenimiento" />
                        <x-input-error :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Descripción" />
                        <textarea
                            id="description"
                            wire:model="description"
                            rows="3"
                            maxlength="500"
                            placeholder="Opcional: detalles de la cita"
                            class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
                        ></textarea>
                        <x-input-error :messages="$errors->get('description')" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="scheduledDate" value="Fecha" />
                            <x-text-input id="scheduledDate" type="date" wire:model="scheduledDate" class="mt-1.5" />
                            <x-input-error :messages="$errors->get('scheduledDate')" />
                        </div>

                        <div>
                            <x-input-label for="scheduledTime" value="Hora" />
                            <x-text-input id="scheduledTime" type="time" wire:model="scheduledTime" class="mt-1.5" />
                            <x-input-error :messages="$errors->get('scheduledTime')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="technicianId" value="Técnico" />

                        @if ($this->canManageOthers())
                            <select
                                id="technicianId"
                                wire:model="technicianId"
                                class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                            >
                                <option value="">Selecciona un técnico</option>
                                @foreach ($this->technicians as $technician)
                                    <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <select
                                id="technicianId"
                                wire:model="technicianId"
                                disabled
                                class="mt-1.5 w-full rounded-md border-line bg-sunken py-2 text-sm text-ink-muted"
                            >
                                @foreach ($this->technicians as $technician)
                                    <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                                @endforeach
                            </select>
                        @endif

                        <x-input-error :messages="$errors->get('technicianId')" />
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-2">
                    <x-primary-button>
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $editingId ? 'Guardar cambios' : 'Crear cita' }}
                    </x-primary-button>

                    <button
                        type="button"
                        wire:click="resetForm"
                        class="inline-flex min-h-10 items-center justify-center rounded-md border border-line px-4 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                    >
                        Limpiar formulario
                    </button>
                </div>
            </form>
        </aside>
    </div>
</div>
