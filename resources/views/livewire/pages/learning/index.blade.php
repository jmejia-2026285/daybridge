<?php

use App\Services\Moodle\MoodleCourse;
use App\Services\Moodle\MoodleException;
use App\Services\Moodle\MoodleService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Capacitación · Daybridge')] class extends Component
{
    #[Url(as: 'estado')]
    public string $status = '';

    public string $statusMessage = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->is_active, 403);
    }

    /**
     * @return array{state: 'disabled'|'unlinked'|'error'|'ready', courses: Collection<int, MoodleCourse>, message: ?string}
     */
    #[Computed]
    public function overview(): array
    {
        $moodle = app(MoodleService::class);

        if (! $moodle->isAvailable()) {
            return ['state' => 'disabled', 'courses' => collect(), 'message' => null];
        }

        try {
            if ($moodle->linkAccount(auth()->user()) === null) {
                return ['state' => 'unlinked', 'courses' => collect(), 'message' => null];
            }

            return ['state' => 'ready', 'courses' => $moodle->coursesFor(auth()->user()), 'message' => null];
        } catch (MoodleException $exception) {
            report($exception);

            return ['state' => 'error', 'courses' => collect(), 'message' => $exception->friendlyMessage()];
        }
    }

    /**
     * @return Collection<int, MoodleCourse>
     */
    #[Computed]
    public function courses(): Collection
    {
        return $this->overview['courses']
            ->when($this->status !== '', fn (Collection $courses): Collection => $courses
                ->filter(fn (MoodleCourse $course): bool => $course->status() === $this->status)
                ->values());
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return [
            '' => 'Todos',
            'in_progress' => 'En curso',
            'not_started' => 'Sin iniciar',
            'completed' => 'Completados',
        ];
    }

    #[Computed]
    public function moodleSsoUrl(): string
    {
        return route('learning.sso');
    }

    #[Computed]
    public function averageProgress(): ?int
    {
        $tracked = $this->overview['courses']->filter(fn (MoodleCourse $course): bool => $course->progress !== null);

        return $tracked->isEmpty() ? null : (int) round($tracked->avg('progress'));
    }

    public function countByStatus(string $status): int
    {
        return $this->overview['courses']
            ->filter(fn (MoodleCourse $course): bool => $course->status() === $status)
            ->count();
    }

    public function setStatus(string $status): void
    {
        $this->status = array_key_exists($status, $this->statusOptions) ? $status : '';
        unset($this->courses);
    }

    public function refresh(): void
    {
        app(MoodleService::class)->forgetCachedCourses(auth()->user());

        unset($this->overview, $this->courses, $this->averageProgress);

        $this->statusMessage = $this->overview['state'] === 'ready'
            ? 'Cursos actualizados desde Moodle.'
            : '';
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Aprendizaje</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Capacitación</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Tus cursos de Moodle, tu avance y tus calificaciones sin salir de Daybridge.
            </p>
        </div>

        @if (in_array($this->overview['state'], ['ready', 'error'], true))
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="refresh"
                    wire:loading.attr="disabled"
                    class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-4 text-sm font-semibold text-ink transition duration-fast ease-signal hover:border-line-strong hover:bg-sunken disabled:opacity-60"
                >
                    <x-icon name="refresh" class="h-4 w-4" wire:loading.class="animate-spin" wire:target="refresh" />
                    Actualizar
                </button>

                <a
                    href="{{ $this->moodleSsoUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                >
                    Ir a Moodle
                    <x-icon name="external" class="h-4 w-4" />
                </a>
            </div>
        @endif
    </header>

    @if ($statusMessage !== '')
        <p class="mt-4 border-l-2 border-brand bg-brand-soft px-3 py-2 text-sm text-brand-strong" role="status">
            {{ $statusMessage }}
        </p>
    @endif

    @switch($this->overview['state'])
        @case('disabled')
            <div class="mt-8 flex flex-col items-start gap-5 border border-line bg-surface p-6 sm:flex-row sm:items-center">
                <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-28 w-auto">

                <div>
                    <p class="font-expanded text-xl font-bold">La plataforma de capacitación todavía no está conectada.</p>
                    <p class="mt-1 max-w-prose text-sm text-ink-muted">
                        Cuando Administración conecte Moodle, aquí verás tus cursos, tu avance y tus calificaciones.
                    </p>
                </div>
            </div>
            @break

        @case('unlinked')
            <div class="mt-8 flex flex-col items-start gap-5 border border-line bg-surface p-6 sm:flex-row sm:items-center">
                <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-28 w-auto">

                <div>
                    <p class="font-expanded text-xl font-bold">No encontramos tu cuenta de Moodle.</p>
                    <p class="mt-1 max-w-prose text-sm text-ink-muted">
                        Buscamos una cuenta con tu correo y no hay ninguna. Pide a Administración que te dé de alta en la plataforma de capacitación.
                    </p>
                    <p class="mt-2 text-xs text-ink-muted">
                        Correo buscado: <span class="font-medium text-ink">{{ auth()->user()->email }}</span>
                    </p>
                </div>
            </div>
            @break

        @case('error')
            <div class="mt-8 flex flex-col items-start gap-5 border border-danger/30 bg-danger-soft p-6 sm:flex-row sm:items-center" role="alert">
                <img src="{{ asset('media/rem/rem_asustada.png') }}" alt="" class="h-28 w-auto">

                <div>
                    <p class="font-expanded text-xl font-bold">No pudimos traer tus cursos.</p>
                    <p class="mt-1 max-w-prose text-sm text-ink-secondary">{{ $this->overview['message'] }}</p>
                </div>
            </div>
            @break

        @default
            <section class="mt-6 grid grid-cols-2 divide-line border border-line bg-surface sm:grid-cols-4 sm:divide-x" aria-label="Resumen de capacitación">
                <div class="border-b border-line px-4 py-4 sm:border-b-0">
                    <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Cursos</p>
                    <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->overview['courses']->count() }}</p>
                </div>

                <div class="border-b border-l border-line px-4 py-4 sm:border-b-0">
                    <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                        <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                        En curso
                    </p>
                    <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->countByStatus('in_progress') }}</p>
                </div>

                <div class="px-4 py-4">
                    <p class="flex items-center gap-2 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                        <span class="h-1.5 w-1.5 rounded-full bg-success"></span>
                        Completados
                    </p>
                    <p class="mt-1 font-condensed text-4xl font-black tabular-nums">{{ $this->countByStatus('completed') }}</p>
                </div>

                <div class="border-l border-line px-4 py-4">
                    <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Avance promedio</p>
                    <p class="mt-1 font-condensed text-4xl font-black tabular-nums">
                        {{ $this->averageProgress === null ? '—' : $this->averageProgress.'%' }}
                    </p>
                </div>
            </section>

            @if ($this->overview['courses']->isNotEmpty())
                <nav class="mt-6 flex flex-wrap items-center gap-2" aria-label="Filtrar cursos">
                    @foreach ($this->statusOptions as $value => $label)
                        <button
                            type="button"
                            wire:click="setStatus('{{ $value }}')"
                            wire:key="learning-filter-{{ $value ?: 'all' }}"
                            @class([
                                'inline-flex min-h-10 items-center rounded-md border px-3 text-xs font-semibold tracking-label uppercase transition duration-fast ease-signal',
                                'border-accent bg-accent-soft text-accent-strong' => $status === $value,
                                'border-line text-ink-muted hover:border-line-strong hover:bg-sunken hover:text-ink' => $status !== $value,
                            ])
                            aria-pressed="{{ $status === $value ? 'true' : 'false' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </nav>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @forelse ($this->courses as $course)
                        <article class="flex flex-col border border-line bg-surface" wire:key="moodle-course-{{ $course->id }}">
                            <header class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($course->shortName !== '')
                                            <span class="font-condensed text-sm font-bold tabular-nums text-ink-muted">{{ $course->shortName }}</span>
                                        @endif
                                        <span class="inline-flex items-center gap-1.5 rounded-sm border px-2 py-1 text-3xs font-semibold tracking-label uppercase {{ $course->badgeClasses() }}">
                                            <span class="h-1 w-1 rounded-full {{ $course->dotClasses() }}"></span>
                                            {{ $course->statusLabel() }}
                                        </span>
                                    </div>

                                    <h2 class="mt-1 font-expanded text-lg font-bold leading-tight tracking-tight">{{ $course->name }}</h2>
                                </div>

                                @if ($course->grade !== null)
                                    <div class="shrink-0 text-right">
                                        <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Calificación</p>
                                        <p class="mt-0.5 font-condensed text-2xl font-black tabular-nums">{{ $course->grade }}</p>
                                    </div>
                                @endif
                            </header>

                            <div class="flex flex-1 flex-col gap-4 px-5 py-4">
                                @if ($course->summary !== '')
                                    <p class="text-sm leading-relaxed text-ink-secondary">{{ $course->summary }}</p>
                                @endif

                                <div class="mt-auto">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Avance</p>
                                        <p class="font-condensed text-sm font-bold tabular-nums">
                                            {{ $course->progress === null ? 'Sin seguimiento' : $course->progress.'%' }}
                                        </p>
                                    </div>

                                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-sunken" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $course->progress ?? 0 }}" aria-label="Avance en {{ $course->name }}">
                                        <div
                                            @class(['h-full rounded-full transition-all duration-slow ease-signal', 'bg-success' => $course->completed, 'bg-brand' => ! $course->completed])
                                            style="width: {{ $course->progress ?? 0 }}%"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3">
                                <p class="text-xs text-ink-muted">
                                    @if ($course->lastAccessAt)
                                        Último acceso {{ $course->lastAccessAt->locale('es')->diffForHumans() }}
                                    @else
                                        Aún no has entrado
                                    @endif
                                    @if ($course->endsAt)
                                        · Cierra {{ $course->endsAt->locale('es')->translatedFormat('d M Y') }}
                                    @endif
                                </p>

                                <a
                                    href="{{ route('learning.sso', ['to' => $course->url]) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-accent transition duration-fast ease-signal hover:text-accent-strong"
                                >
                                    {{ $course->completed ? 'Repasar' : 'Continuar' }}
                                    <x-icon name="arrow-up-right" class="h-4 w-4" />
                                </a>
                            </footer>
                        </article>
                    @empty
                        <p class="border border-line bg-surface px-5 py-6 text-sm text-ink-muted lg:col-span-2">
                            No tienes cursos en este estado.
                        </p>
                    @endforelse
                </div>
            @else
                <div class="mt-8 flex flex-col items-start gap-5 border border-line bg-surface p-6 sm:flex-row sm:items-center">
                    <img src="{{ asset('media/rem/rem_feliz.png') }}" alt="" class="h-28 w-auto">

                    <div>
                        <p class="font-expanded text-xl font-bold">Todavía no tienes cursos asignados.</p>
                        <p class="mt-1 max-w-prose text-sm text-ink-muted">
                            Cuando te matriculen en un curso de Moodle aparecerá aquí con tu avance.
                        </p>
                    </div>
                </div>
            @endif
    @endswitch

    <p class="mt-6 flex items-start gap-2 text-xs leading-relaxed text-ink-muted">
        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
        Tu cuenta de Moodle se vincula automáticamente por correo. Los datos se actualizan cada pocos minutos; usa «Actualizar» para traerlos al instante.
    </p>
</div>
