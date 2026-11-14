<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Tareas · Daybridge')] class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'estado')]
    public string $statusFilter = '';

    #[Url(as: 'asignado')]
    public string $assigneeFilter = '';

    #[Url(as: 'mias')]
    public bool $onlyMine = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $status = 'PENDIENTE';

    public string $priority = 'MEDIA';

    public ?int $assignedTo = null;

    public string $dueDate = '';

    public string $statusMessage = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', Task::class), 403);

        $this->status = TaskStatus::Pending->value;
        $this->priority = TaskPriority::Medium->value;
        $this->assignedTo = auth()->id();
    }

    #[Computed]
    public function people(): Collection
    {
        return User::query()
            ->active()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function tasks(): Collection
    {
        return Task::query()
            ->with(['assignee', 'creator'])
            ->when($this->search !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query
                    ->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%"),
            ))
            ->when($this->statusFilter !== '', fn (Builder $query): Builder => $query->where('status', $this->statusFilter))
            ->when($this->assigneeFilter !== '', fn (Builder $query): Builder => $query->where('assigned_to', (int) $this->assigneeFilter))
            ->when($this->onlyMine, fn (Builder $query): Builder => $query->where('assigned_to', auth()->id()))
            ->orderByRaw("case status when 'PENDIENTE' then 0 when 'EN_PROCESO' then 1 else 2 end")
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function counts(): array
    {
        $counts = Task::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($counts[TaskStatus::Pending->value] ?? 0),
            'in_progress' => (int) ($counts[TaskStatus::InProgress->value] ?? 0),
            'completed' => (int) ($counts[TaskStatus::Completed->value] ?? 0),
            'overdue' => Task::query()->overdue()->count(),
        ];
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->assigneeFilter = '';
        $this->onlyMine = false;

        $this->forgetTasks();
    }

    public function edit(int $taskId): void
    {
        $task = Task::query()->findOrFail($taskId);

        abort_unless(auth()->user()->can('update', $task), 403);

        $this->editingId = $task->id;
        $this->title = $task->title;
        $this->description = $task->description ?? '';
        $this->status = $task->status->value;
        $this->priority = $task->priority->value;
        $this->assignedTo = $task->assigned_to;
        $this->dueDate = $task->due_date?->toDateString() ?? '';
        $this->statusMessage = '';
        $this->resetValidation();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->description = '';
        $this->status = TaskStatus::Pending->value;
        $this->priority = TaskPriority::Medium->value;
        $this->assignedTo = auth()->id();
        $this->dueDate = '';
        $this->statusMessage = '';
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'assignedTo' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'dueDate' => ['nullable', 'date'],
        ], [], [
            'title' => 'título',
            'description' => 'descripción',
            'status' => 'estado',
            'priority' => 'prioridad',
            'assignedTo' => 'responsable',
            'dueDate' => 'fecha límite',
        ]);

        $attributes = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assignedTo'],
            'due_date' => $validated['dueDate'] ?: null,
            'completed_at' => $validated['status'] === TaskStatus::Completed->value ? now() : null,
        ];

        if ($this->editingId !== null) {
            $task = Task::query()->findOrFail($this->editingId);

            abort_unless(auth()->user()->can('update', $task), 403);

            $task->update($attributes);
            $message = "Tarea «{$task->title}» actualizada.";

            if ($task->wasChanged('assigned_to')) {
                $this->notifyAssignment($task);
            }
        } else {
            abort_unless(auth()->user()->can('create', Task::class), 403);

            $task = Task::query()->create($attributes + ['created_by' => auth()->id()]);
            $message = "Tarea «{$task->title}» creada.";

            $this->notifyAssignment($task);
        }

        $this->resetForm();
        $this->statusMessage = $message;
        $this->forgetTasks();
    }

    public function changeStatus(int $taskId, string $status): void
    {
        $task = Task::query()->findOrFail($taskId);

        abort_unless(auth()->user()->can('updateStatus', $task), 403);

        $validated = validator(
            ['status' => $status],
            ['status' => ['required', Rule::enum(TaskStatus::class)]],
        )->validate();

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === TaskStatus::Completed->value ? now() : null,
        ]);

        $this->notifyCompletion($task);

        $this->statusMessage = "Tarea «{$task->title}» actualizada a {$task->fresh()->status->label()}.";
        $this->forgetTasks();
    }

    public function toggleCompleted(int $taskId): void
    {
        $task = Task::query()->findOrFail($taskId);

        abort_unless(auth()->user()->can('updateStatus', $task), 403);

        $completed = ! $task->status->isCompleted();

        $task->update([
            'status' => $completed ? TaskStatus::Completed : TaskStatus::Pending,
            'completed_at' => $completed ? now() : null,
        ]);

        $this->notifyCompletion($task);

        $this->statusMessage = $completed
            ? "Tarea «{$task->title}» completada."
            : "Tarea «{$task->title}» reabierta.";

        $this->forgetTasks();
    }

    public function delete(int $taskId): void
    {
        $task = Task::query()->findOrFail($taskId);

        abort_unless(auth()->user()->can('delete', $task), 403);

        $title = $task->title;

        $task->delete();

        if ($this->editingId === $taskId) {
            $this->resetForm();
        }

        $this->statusMessage = "Tarea «{$title}» eliminada.";
        $this->forgetTasks();
    }

    private function notifyAssignment(Task $task): void
    {
        if ($task->assigned_to === auth()->id()) {
            return;
        }

        Notification::query()->create([
            'type' => 'task_assigned',
            'notifiable_type' => User::class,
            'notifiable_id' => $task->assigned_to,
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'actor_id' => auth()->id(),
            'data' => ['message' => "Te asignaron la tarea «{$task->title}»."],
        ]);
    }

    private function notifyCompletion(Task $task): void
    {
        if (! $task->status->isCompleted() || $task->created_by === auth()->id()) {
            return;
        }

        Notification::query()->create([
            'type' => 'task_completed',
            'notifiable_type' => User::class,
            'notifiable_id' => $task->created_by,
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'actor_id' => auth()->id(),
            'data' => ['message' => "«{$task->title}» se completó."],
        ]);
    }

    private function forgetTasks(): void
    {
        unset($this->tasks, $this->counts);
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Equipo</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Tareas</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Lo que el equipo necesita mover esta semana, con responsables y fechas claras.
            </p>
        </div>

        <button
            type="button"
            wire:click="resetForm"
            class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
        >
            <x-icon name="plus" class="h-4 w-4" />
            Nueva tarea
        </button>
    </header>

    @if ($statusMessage !== '')
        <p class="mt-4 border-l-2 border-brand bg-brand-soft px-3 py-2 text-sm text-brand-strong" role="status">
            {{ $statusMessage }}
        </p>
    @endif

    <section class="mt-6 grid grid-cols-2 divide-line bubble-glass sm:grid-cols-4 sm:divide-x" aria-label="Resumen de tareas">
        <div class="border-b border-line px-4 py-4 sm:border-b-0">
            <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                <span class="h-1.5 w-1.5 rounded-full {{ TaskStatus::Pending->dotClasses() }}"></span>
                Pendientes
            </p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->counts['pending'] }}</p>
        </div>

        <div class="border-b border-line px-4 py-4 sm:border-b-0">
            <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                <span class="h-1.5 w-1.5 rounded-full {{ TaskStatus::InProgress->dotClasses() }}"></span>
                En proceso
            </p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->counts['in_progress'] }}</p>
        </div>

        <div class="px-4 py-4">
            <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                <span class="h-1.5 w-1.5 rounded-full {{ TaskStatus::Completed->dotClasses() }}"></span>
                Completadas
            </p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->counts['completed'] }}</p>
        </div>

        <div class="px-4 py-4">
            <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                <span class="h-1.5 w-1.5 rounded-full bg-danger"></span>
                Vencidas
            </p>
            <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->counts['overdue'] }}</p>
        </div>
    </section>

    <div class="mt-6 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="lg:col-span-8">
            <div class="flex flex-col gap-3 bubble-glass p-4 sm:flex-row sm:items-center">
                <label class="relative flex-1">
                    <span class="sr-only">Buscar tareas</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por título o descripción"
                        class="w-full rounded-md border-line bg-canvas py-2.5 pl-9 pr-3 text-sm placeholder:text-ink-disabled focus:border-brand focus:ring-brand"
                    >
                </label>

                <label class="sm:w-40">
                    <span class="sr-only">Filtrar por estado</span>
                    <select
                        wire:model.live="statusFilter"
                        class="w-full rounded-md border-line bg-canvas py-2.5 text-sm focus:border-brand focus:ring-brand"
                    >
                        <option value="">Todos los estados</option>
                        @foreach (TaskStatus::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="sm:w-48">
                    <span class="sr-only">Filtrar por responsable</span>
                    <select
                        wire:model.live="assigneeFilter"
                        class="w-full rounded-md border-line bg-canvas py-2.5 text-sm focus:border-brand focus:ring-brand"
                    >
                        <option value="">Todos los responsables</option>
                        @foreach ($this->people as $person)
                            <option value="{{ $person->id }}">{{ $person->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <label class="inline-flex min-h-10 items-center gap-2 text-sm text-ink-muted">
                    <input
                        type="checkbox"
                        wire:model.live="onlyMine"
                        class="rounded border-line text-brand focus:ring-brand"
                    >
                    Solo mis tareas
                </label>

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
                >
                    Limpiar filtros
                </button>
            </div>

            <div class="mt-3 space-y-3">
                @forelse ($this->tasks as $task)
                    <article class="border border-line bg-surface p-4" wire:key="task-{{ $task->id }}">
                        <div class="flex gap-3">
                            @can('updateStatus', $task)
                                <button
                                    type="button"
                                    wire:click="toggleCompleted({{ $task->id }})"
                                    aria-label="{{ $task->status->isCompleted() ? 'Reabrir tarea' : 'Completar tarea' }}"
                                    class="mt-0.5 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border transition duration-fast ease-signal {{ $task->status->isCompleted() ? 'border-success/40 bg-success-soft text-success hover:bg-success/15' : 'border-line text-ink-muted hover:border-success/40 hover:bg-success-soft hover:text-success' }}"
                                >
                                    <x-icon name="{{ $task->status->isCompleted() ? 'check-circle' : 'circle' }}" class="h-5 w-5" />
                                </button>
                            @endcan

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-sm border px-1.5 py-0.5 text-3xs font-semibold tracking-label uppercase {{ $task->priority->badgeClasses() }}">
                                        <span class="h-1 w-1 rounded-full {{ $task->priority->dotClasses() }}"></span>
                                        {{ $task->priority->label() }}
                                    </span>

                                    <span class="inline-flex items-center gap-1.5 rounded-sm border px-1.5 py-0.5 text-3xs font-semibold tracking-label uppercase {{ $task->status->badgeClasses() }}">
                                        <span class="h-1 w-1 rounded-full {{ $task->status->dotClasses() }}"></span>
                                        {{ $task->status->label() }}
                                    </span>

                                    @if ($task->isOverdue())
                                        <span class="inline-flex items-center gap-1 rounded-sm border border-danger/30 bg-danger-soft px-1.5 py-0.5 text-3xs font-semibold tracking-label text-danger uppercase">
                                            <x-icon name="alert" class="h-3 w-3" />
                                            Vencida
                                        </span>
                                    @endif
                                </div>

                                <h2 @class([
                                    'mt-1.5 text-sm font-semibold',
                                    'text-ink-muted line-through' => $task->status->isCompleted(),
                                ])>
                                    {{ $task->title }}
                                </h2>

                                @if ($task->description)
                                    <p class="mt-1 text-sm leading-relaxed text-ink-muted">{{ $task->description }}</p>
                                @endif

                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-ink-muted">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-avatar :user="$task->assignee" size="2xs" />
                                        {{ $task->assignee->name }}
                                    </span>

                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="calendar" class="h-3.5 w-3.5" />
                                        {{ $task->due_date?->locale('es')->translatedFormat('d M Y') ?? 'Sin fecha límite' }}
                                    </span>

                                    @if ($task->creator)
                                        <span>Creada por {{ $task->creator->name }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-col items-end gap-1.5">
                                @can('updateStatus', $task)
                                    <label class="w-36">
                                        <span class="sr-only">Cambiar estado de {{ $task->title }}</span>
                                        <select
                                            wire:change="changeStatus({{ $task->id }}, $event.target.value)"
                                            class="w-full rounded-md border-line bg-canvas py-2 text-xs focus:border-accent focus:ring-accent"
                                        >
                                            @foreach (TaskStatus::options() as $value => $label)
                                                <option value="{{ $value }}" @selected($task->status->value === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endcan

                                <div class="flex gap-1.5">
                                    @can('update', $task)
                                        <button
                                            type="button"
                                            wire:click="edit({{ $task->id }})"
                                            aria-label="Editar tarea {{ $task->title }}"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink transition duration-fast ease-signal hover:bg-sunken"
                                        >
                                            <x-icon name="edit" class="h-4 w-4" />
                                        </button>
                                    @endcan

                                    @can('delete', $task)
                                        <button
                                            type="button"
                                            wire:click="delete({{ $task->id }})"
                                            wire:confirm="¿Eliminar la tarea «{{ $task->title }}»?"
                                            aria-label="Eliminar tarea {{ $task->title }}"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-danger/30 text-danger transition duration-fast ease-signal hover:bg-danger-soft"
                                        >
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="flex flex-col items-start gap-4 border border-line bg-surface p-6 sm:flex-row sm:items-center">
                        <img src="{{ asset('media/rem/rem_feliz.png') }}" alt="" class="h-24 w-auto">
                        <div>
                            <p class="font-expanded text-xl font-bold">No hay tareas con estos filtros.</p>
                            <p class="mt-1 text-sm text-ink-muted">Crea una nueva tarea o limpia los filtros para ver todo.</p>
                            <button
                                type="button"
                                wire:click="clearFilters"
                                class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-strong"
                            >
                                Limpiar filtros
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="lg:col-span-4">
            <form wire:submit="save" class="border border-line bg-surface p-5 lg:sticky lg:top-20">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">
                        {{ $editingId ? 'Editar tarea' : 'Nueva tarea' }}
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
                        <x-text-input id="title" wire:model="title" class="mt-1.5" maxlength="120" placeholder="Qué hay que hacer" />
                        <x-input-error :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Descripción" />
                        <textarea
                            id="description"
                            wire:model="description"
                            rows="3"
                            maxlength="2000"
                            placeholder="Opcional: contexto o criterio de cierre"
                            class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
                        ></textarea>
                        <x-input-error :messages="$errors->get('description')" />
                    </div>

                    <div>
                        <x-input-label for="assignedTo" value="Responsable" />
                        <select
                            id="assignedTo"
                            wire:model="assignedTo"
                            class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                        >
                            <option value="">Selecciona a una persona</option>
                            @foreach ($this->people as $person)
                                <option value="{{ $person->id }}">{{ $person->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('assignedTo')" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="priority" value="Prioridad" />
                            <select
                                id="priority"
                                wire:model="priority"
                                class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                            >
                                @foreach (TaskPriority::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('priority')" />
                        </div>

                        <div>
                            <x-input-label for="dueDate" value="Fecha límite" />
                            <x-text-input id="dueDate" type="date" wire:model="dueDate" class="mt-1.5" />
                            <x-input-error :messages="$errors->get('dueDate')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="status" value="Estado" />
                        <select
                            id="status"
                            wire:model="status"
                            class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                        >
                            @foreach (TaskStatus::options() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" />
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-2">
                    <x-primary-button>
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $editingId ? 'Guardar cambios' : 'Crear tarea' }}
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
