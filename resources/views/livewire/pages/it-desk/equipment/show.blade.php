<?php

use App\Enums\ServiceStatus;
use App\Models\Equipment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ItDesk\TicketService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Detalle de equipo · IT Desk')] class extends Component
{
    public Equipment $equipment;

    public string $newTicketDescription = '';

    public ?int $newTicketAssignedTo = null;

    public string $statusMessage = '';

    public function mount(Equipment $equipment): void
    {
        abort_unless(auth()->user()->can('view', $equipment), 403);

        $this->equipment = $equipment;
        $this->newTicketAssignedTo = $this->technicians->firstWhere('id', auth()->id())?->id
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

    #[Computed]
    public function tickets(): Collection
    {
        return $this->equipment->tickets()
            ->with('assignedTo')
            ->withCount('prints')
            ->orderByDesc('opened_at')
            ->orderByDesc('id')
            ->get();
    }

    public function changeStatus(int $ticketId, string $status, TicketService $ticketService): void
    {
        $ticket = $this->equipment->tickets()->findOrFail($ticketId);

        abort_unless(auth()->user()->can('changeStatus', $ticket), 403);

        $validated = validator(
            ['status' => $status],
            ['status' => ['required', Rule::enum(ServiceStatus::class)]],
        )->validate();

        try {
            $ticketService->changeStatus($ticket, ServiceStatus::from($validated['status']));
        } catch (ValidationException $exception) {
            $this->addError("status-{$ticketId}", $exception->getMessage());

            return;
        }

        $this->equipment->refresh();
        unset($this->tickets);

        $this->statusMessage = "Ticket {$ticket->folio} actualizado a {$ticket->fresh()->status->label()}.";
    }

    public function openTicket(TicketService $ticketService): void
    {
        abort_unless(auth()->user()->can('create', Ticket::class), 403);

        $validated = $this->validate([
            'newTicketDescription' => ['required', 'string', 'max:255'],
            'newTicketAssignedTo' => ['required', Rule::exists('users', 'id')->where(fn ($q) => $q->where('is_active', true)->whereNotNull('it_desk_role'))],
        ], [], [
            'newTicketDescription' => 'descripción del ticket',
            'newTicketAssignedTo' => 'técnico asignado',
        ]);

        $ticket = $ticketService->open($this->equipment, [
            'description' => $validated['newTicketDescription'],
            'assigned_to' => $validated['newTicketAssignedTo'],
        ], auth()->user());

        $this->equipment->refresh();
        $this->newTicketDescription = '';
        unset($this->tickets);

        $this->statusMessage = "Ticket {$ticket->folio} abierto para {$this->equipment->code}.";
    }

    public function destroy(): void
    {
        abort_unless(auth()->user()->can('delete', $this->equipment), 403);

        $code = $this->equipment->code;

        $this->equipment->delete();

        session()->flash('status', "El equipo {$code} y sus tickets se eliminaron.");

        $this->redirectRoute('it-desk.equipment.index', navigate: true);
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

        <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="font-condensed text-2xl font-black tabular-nums">{{ $equipment->code }}</span>
                    <span class="inline-flex items-center gap-1.5 rounded-sm border px-2 py-1 text-3xs font-semibold tracking-label uppercase {{ $equipment->status->badgeClasses() }}">
                        <span class="h-1 w-1 rounded-full {{ $equipment->status->dotClasses() }}"></span>
                        {{ $equipment->status->label() }}
                    </span>
                </div>

                <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">
                    {{ $equipment->displayName() }}
                </h1>
                <p class="mt-1 text-sm text-ink-muted">
                    {{ $equipment->client_name }} · Ingresó {{ $equipment->received_at->locale('es')->translatedFormat('d M Y, H:i') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('it-desk.equipment.edit', $equipment) }}"
                    wire:navigate
                    class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3.5 text-sm font-medium text-ink transition duration-fast ease-signal hover:bg-sunken"
                >
                    <x-icon name="edit" class="h-4 w-4" />
                    Editar
                </a>

                @can('delete', $equipment)
                    <button
                        type="button"
                        wire:click="destroy"
                        wire:confirm="¿Eliminar el equipo {{ $equipment->code }} y todos sus tickets?"
                        class="inline-flex min-h-10 items-center gap-2 rounded-md border border-danger/30 px-3.5 text-sm font-medium text-danger transition duration-fast ease-signal hover:bg-danger-soft"
                    >
                        <x-icon name="trash" class="h-4 w-4" />
                        Eliminar
                    </button>
                @endcan
            </div>
        </div>
    </header>

    @if ($statusMessage !== '')
        <p class="mt-4 border-l-2 border-brand bg-brand-soft px-3 py-2 text-sm text-brand-strong" role="status">
            {{ $statusMessage }}
        </p>
    @endif

    <div class="mt-6 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="space-y-8 lg:col-span-8">
            <section>
                <h2 class="text-3xs font-bold tracking-label text-ink-muted uppercase">Equipo</h2>

                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-3 border border-line bg-surface p-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-3xs tracking-label text-ink-muted uppercase">Tipo</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $equipment->type }}</dd>
                    </div>
                    <div>
                        <dt class="text-3xs tracking-label text-ink-muted uppercase">Marca</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $equipment->brand }}</dd>
                    </div>
                    <div>
                        <dt class="text-3xs tracking-label text-ink-muted uppercase">Modelo</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $equipment->model }}</dd>
                    </div>
                    <div>
                        <dt class="text-3xs tracking-label text-ink-muted uppercase">Serie</dt>
                        <dd class="mt-0.5 font-condensed text-sm font-bold tabular-nums">{{ $equipment->serial ?: 'Sin registrar' }}</dd>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <dt class="text-3xs tracking-label text-ink-muted uppercase">Accesorios</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $equipment->accessories ?: 'Sin accesorios' }}</dd>
                    </div>
                    <div>
                        <dt class="text-3xs tracking-label text-ink-muted uppercase">Entrega estimada</dt>
                        <dd class="mt-0.5 font-condensed text-sm font-bold tabular-nums">
                            {{ $equipment->estimated_delivery_date?->locale('es')->translatedFormat('d M Y') ?? 'Por confirmar' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section>
                <h2 class="text-3xs font-bold tracking-label text-ink-muted uppercase">Cliente</h2>

                <dl class="mt-3 grid gap-3 border border-line bg-surface p-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-3xs tracking-label text-ink-muted uppercase">Nombre</dt>
                        <dd class="mt-0.5 text-sm font-medium">{{ $equipment->client_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-3xs tracking-label text-ink-muted uppercase">Teléfono</dt>
                        <dd class="mt-0.5 font-condensed text-sm font-bold tabular-nums">{{ $equipment->client_phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-3xs tracking-label text-ink-muted uppercase">Correo</dt>
                        <dd class="mt-0.5 truncate text-sm font-medium">{{ $equipment->client_email ?: 'Sin registrar' }}</dd>
                    </div>
                </dl>
            </section>

            <section>
                <h2 class="text-3xs font-bold tracking-label text-ink-muted uppercase">Servicio</h2>

                <div class="mt-3 divide-y divide-line border border-line bg-surface">
                    <div class="p-4">
                        <p class="text-3xs tracking-label text-ink-muted uppercase">Motivo de la falla</p>
                        <p class="mt-1 text-sm leading-relaxed">{{ $equipment->issue }}</p>
                    </div>
                    <div class="p-4">
                        <p class="text-3xs tracking-label text-ink-muted uppercase">Diagnóstico</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed">{{ $equipment->diagnosis ?: 'Sin diagnóstico registrado.' }}</p>
                    </div>
                    @if ($equipment->notes)
                        <div class="p-4">
                            <p class="text-3xs tracking-label text-ink-muted uppercase">Notas internas</p>
                            <p class="mt-1 whitespace-pre-line text-sm leading-relaxed">{{ $equipment->notes }}</p>
                        </div>
                    @endif
                </div>
            </section>

            <section>
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-3xs font-bold tracking-label text-ink-muted uppercase">Tickets</h2>
                    <span class="text-3xs text-ink-muted">{{ $this->tickets->count() }} en total</span>
                </div>

                <ul class="mt-3 divide-y divide-line border border-line bg-surface">
                    @forelse ($this->tickets as $ticket)
                        <li class="p-4" wire:key="ticket-{{ $ticket->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-condensed text-sm font-bold tabular-nums">{{ $ticket->folio }}</span>
                                        <span class="inline-flex items-center gap-1.5 rounded-sm border px-1.5 py-0.5 text-3xs font-semibold tracking-label uppercase {{ $ticket->status->badgeClasses() }}">
                                            <span class="h-1 w-1 rounded-full {{ $ticket->status->dotClasses() }}"></span>
                                            {{ $ticket->status->label() }}
                                        </span>
                                        @if ($ticket->isPrinted())
                                            <span class="inline-flex items-center gap-1 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                                                <x-icon name="printer" class="h-3.5 w-3.5" />
                                                Impreso
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-1.5 text-sm leading-relaxed">{{ $ticket->description }}</p>
                                    <p class="mt-1 text-xs text-ink-muted">
                                        {{ $ticket->assignedTo?->name ?? 'Sin técnico' }} ·
                                        Abierto {{ $ticket->opened_at->locale('es')->translatedFormat('d M Y, H:i') }}
                                        @if ($ticket->completed_at)
                                            · Completado {{ $ticket->completed_at->locale('es')->translatedFormat('d M Y, H:i') }}
                                        @endif
                                    </p>
                                    @if ($ticket->prints_count > 0)
                                        <p class="mt-1 text-xs text-ink-muted">
                                            {{ $ticket->prints_count }} {{ $ticket->prints_count === 1 ? 'copia registrada' : 'copias registradas' }}
                                            @if ($ticket->printed_at)
                                                · última {{ $ticket->printed_at->locale('es')->translatedFormat('d M Y, H:i') }}
                                            @endif
                                        </p>
                                    @endif
                                </div>

                                <div class="w-full sm:w-44">
                                    <label class="block">
                                        <span class="mb-1 block text-3xs tracking-label text-ink-muted uppercase">Cambiar estado</span>
                                        <select
                                            wire:change="changeStatus({{ $ticket->id }}, $event.target.value)"
                                            class="w-full rounded-md border-line bg-canvas py-2 text-xs focus:border-accent focus:ring-accent"
                                        >
                                            @foreach (ServiceStatus::options() as $value => $label)
                                                <option value="{{ $value }}" @selected($ticket->status->value === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("status-{$ticket->id}")
                                            <span class="mt-1 block text-xs text-danger">{{ $message }}</span>
                                        @enderror
                                    </label>

                                    @can('print', $ticket)
                                        <a
                                            href="{{ route('it-desk.equipment.print', [$equipment, $ticket]) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="mt-2 inline-flex min-h-9 w-full items-center justify-center gap-2 rounded-md border border-line px-3 text-xs font-semibold text-ink transition duration-fast ease-signal hover:bg-sunken"
                                        >
                                            <x-icon name="printer" class="h-4 w-4" />
                                            Imprimir tickets
                                        </a>
                                    @endcan
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="p-6 text-sm text-ink-muted">Este equipo todavía no tiene tickets.</li>
                    @endforelse
                </ul>

                <form wire:submit="openTicket" class="mt-4 border border-line bg-surface p-4">
                    <h3 class="text-3xs font-bold tracking-label text-ink-muted uppercase">Abrir otro ticket</h3>

                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <x-input-label for="newTicketDescription" value="Descripción" />
                            <textarea
                                id="newTicketDescription"
                                wire:model="newTicketDescription"
                                rows="2"
                                maxlength="255"
                                placeholder="Qué se va a revisar o corregir"
                                class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
                            ></textarea>
                            <x-input-error :messages="$errors->get('newTicketDescription')" />
                        </div>

                        <div>
                            <x-input-label for="newTicketAssignedTo" value="Técnico" />
                            <select
                                id="newTicketAssignedTo"
                                wire:model="newTicketAssignedTo"
                                class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                            >
                                <option value="">Selecciona un técnico</option>
                                @foreach ($this->technicians as $technician)
                                    <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('newTicketAssignedTo')" />
                        </div>
                    </div>

                    <x-primary-button class="mt-3">
                        <x-icon name="plus" class="h-4 w-4" />
                        Abrir ticket
                    </x-primary-button>
                </form>
            </section>
        </div>

        <aside class="lg:col-span-4">
            <div class="border border-line bg-surface p-5">
                <h2 class="text-3xs font-bold tracking-label text-ink-muted uppercase">Resumen</h2>

                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-ink-muted">Código</dt>
                        <dd class="font-condensed font-bold tabular-nums">{{ $equipment->code }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-ink-muted">Recibió</dt>
                        <dd class="text-right font-medium">{{ $equipment->receivedBy?->name ?? 'Sin registrar' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-ink-muted">Ingreso</dt>
                        <dd class="text-right font-condensed font-bold tabular-nums">{{ $equipment->received_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-ink-muted">Último ticket</dt>
                        <dd class="font-condensed font-bold tabular-nums">{{ $this->tickets->first()?->folio ?? 'Sin tickets' }}</dd>
                    </div>
                </dl>

                <div class="mt-4 border-t border-line pt-4">
                    <h3 class="text-3xs font-bold tracking-label text-ink-muted uppercase">Técnicos del taller</h3>

                    @forelse ($this->technicians as $technician)
                        <div class="mt-3 flex items-center gap-3" wire:key="technician-{{ $technician->id }}">
                            <x-avatar :user="$technician" size="sm" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $technician->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $technician->it_desk_role?->label() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="mt-2 text-sm text-ink-muted">Aún no hay técnicos asignados.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</div>
