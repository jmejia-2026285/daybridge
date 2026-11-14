<?php

use App\Models\Survey;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Encuestas · Daybridge')] class extends Component
{
    #[Url(as: 'estado')]
    public string $status = 'abiertas';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', Survey::class), 403);
    }

    #[Computed]
    public function surveys(): Collection
    {
        return Survey::query()
            ->with(['creator.area', 'votes'])
            ->withCount(['questions', 'votes'])
            ->when($this->status === 'abiertas', fn ($query) => $query->open())
            ->when($this->status === 'cerradas', fn ($query) => $query->closed())
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Participación</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Encuestas</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Preguntas cortas para escuchar al equipo y tomar mejores decisiones con contexto.
            </p>
        </div>

        @can('create', Survey::class)
            <a
                href="{{ route('surveys.create') }}"
                wire:navigate
                class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Nueva encuesta
            </a>
        @endcan
    </header>

    <nav class="mt-5 flex gap-5 overflow-x-auto pb-1" aria-label="Filtrar encuestas">
        @foreach (['abiertas' => 'Abiertas', 'cerradas' => 'Cerradas', 'todas' => 'Todas'] as $value => $label)
            <button
                type="button"
                wire:click="$set('status', '{{ $value }}')"
                @class([
                    'shrink-0 border-b pb-1.5 text-xs font-medium transition duration-fast ease-signal',
                    'border-ink text-ink' => $status === $value,
                    'border-transparent text-ink-muted hover:border-line-strong hover:text-ink' => $status !== $value,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </nav>

    @if ($this->surveys->isNotEmpty())
        <div class="mt-6 space-y-4">
            @foreach ($this->surveys as $survey)
                @php
                    $hasVoted = $survey->hasVotedBy(auth()->user());
                @endphp

                <article class="border border-line bg-surface p-5" wire:key="survey-{{ $survey->id }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-sm border px-1.5 py-0.5 text-3xs font-semibold tracking-label uppercase {{ $survey->statusClasses() }}">
                                    {{ $survey->statusLabel() }}
                                </span>

                                @if ($hasVoted)
                                    <span class="inline-flex items-center gap-1 rounded-sm border border-success/30 bg-success-soft px-1.5 py-0.5 text-3xs font-semibold tracking-label text-success uppercase">
                                        <x-icon name="check" class="h-3 w-3" />
                                        Ya votaste
                                    </span>
                                @endif

                                @if ($survey->closes_at)
                                    <span class="font-condensed text-3xs tabular-nums text-ink-muted">
                                        {{ $survey->isOpen() ? 'Cierra' : 'Cerró' }} {{ $survey->closes_at->locale('es')->translatedFormat('j M Y') }}
                                    </span>
                                @endif
                            </div>

                            <h2 class="mt-2 font-expanded text-xl font-bold leading-tight tracking-tight">
                                {{ $survey->title }}
                            </h2>

                            @if ($survey->description)
                                <p class="mt-1.5 max-w-prose text-sm text-ink-secondary">{{ $survey->description }}</p>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-ink-muted">
                                <span class="inline-flex items-center gap-1.5">
                                    <x-avatar :user="$survey->creator" size="2xs" />
                                    {{ $survey->creator->name }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <x-icon name="poll" class="h-3.5 w-3.5" />
                                    {{ $survey->questions_count }} {{ $survey->questions_count === 1 ? 'pregunta' : 'preguntas' }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <x-icon name="users" class="h-3.5 w-3.5" />
                                    {{ $survey->votes_count }} {{ $survey->votes_count === 1 ? 'respuesta' : 'respuestas' }}
                                </span>
                            </div>
                        </div>

                        <a
                            href="{{ route('surveys.show', $survey) }}"
                            wire:navigate
                            class="inline-flex min-h-10 shrink-0 items-center gap-1.5 self-start rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                        >
                            {{ $survey->isOpen() && ! $hasVoted ? 'Responder' : 'Ver resultados' }}
                            <x-icon name="arrow-right" class="h-4 w-4" />
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="mt-6 flex flex-col items-start gap-4 border border-line bg-surface p-6 sm:flex-row sm:items-center">
            <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-24 w-auto">
            <div>
                <p class="font-expanded text-xl font-bold">
                    {{ $status === 'cerradas' ? 'No hay encuestas cerradas.' : 'No hay encuestas abiertas por ahora.' }}
                </p>
                <p class="mt-1 text-sm text-ink-muted">
                    @can('create', Survey::class)
                        Crea una nueva encuesta o revisa las que ya cerraron.
                    @else
                        Cuando el equipo publique una encuesta, aparecerá aquí.
                    @endcan
                </p>
            </div>
        </div>
    @endif
</div>
