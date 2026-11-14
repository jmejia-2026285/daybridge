<?php

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyVote;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Encuesta · Daybridge')] class extends Component
{
    public Survey $survey;

    /** @var array<int, int|string> */
    public array $answers = [];

    public string $statusMessage = '';

    public function mount(Survey $survey): void
    {
        abort_unless(auth()->user()->can('view', $survey), 403);

        $this->survey = $survey->load(['creator.area']);
    }

    #[Computed]
    public function questions(): Collection
    {
        return $this->survey->questions()
            ->with([
                'options' => fn ($query) => $query->withCount('votes'),
                'votes',
            ])
            ->get();
    }

    #[Computed]
    public function participantCount(): int
    {
        return $this->survey->votes()->distinct()->count('user_id');
    }

    #[Computed]
    public function canSeeAllResults(): bool
    {
        return auth()->user()->can('update', $this->survey);
    }

    public function hasVotedQuestion(SurveyQuestion $question): bool
    {
        return $question->relationLoaded('votes')
            ? $question->votes->contains('user_id', auth()->id())
            : $question->votes()->where('user_id', auth()->id())->exists();
    }

    public function vote(int $questionId): void
    {
        $this->validate([
            'answers.'.$questionId => ['required', 'integer'],
        ], [
            'answers.'.$questionId.'.required' => 'Selecciona una opción antes de enviar.',
            'answers.'.$questionId.'.integer' => 'Selecciona una opción válida.',
        ]);

        $survey = $this->survey->fresh(['questions.options']);

        abort_unless(auth()->user()->can('vote', $survey), 403);

        $question = $survey->questions->firstWhere('id', $questionId);

        if ($question === null) {
            abort(404);
        }

        if ($question->votes()->where('user_id', auth()->id())->exists()) {
            $this->addError('answers.'.$questionId, 'Ya respondiste esta pregunta.');

            return;
        }

        $option = $question->options->firstWhere('id', (int) $this->answers[$questionId]);

        if ($option === null) {
            $this->addError('answers.'.$questionId, 'Selecciona una de las opciones disponibles.');

            return;
        }

        try {
            SurveyVote::query()->create([
                'survey_question_id' => $question->id,
                'survey_option_id' => $option->id,
                'user_id' => auth()->id(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Doble clic simultáneo: el voto ya quedó registrado.
            $this->addError('answers.'.$questionId, 'Ya respondiste esta pregunta.');

            return;
        }

        unset($this->answers[$questionId]);
        $this->statusMessage = 'Respuesta registrada.';
        unset($this->questions, $this->participantCount);
    }

    public function close(): void
    {
        abort_unless(auth()->user()->can('update', $this->survey), 403);

        $this->survey->update(['is_active' => false]);
        $this->survey->refresh();

        $this->statusMessage = 'La encuesta se cerró.';
        unset($this->questions, $this->canSeeAllResults);
    }

    public function reopen(): void
    {
        abort_unless(auth()->user()->can('update', $this->survey), 403);

        $closesAt = $this->survey->closes_at;

        if ($closesAt === null || $closesAt->isBefore(today())) {
            $closesAt = today()->addDays(7);
        }

        $this->survey->update([
            'is_active' => true,
            'closes_at' => $closesAt,
        ]);
        $this->survey->refresh();

        $this->statusMessage = 'La encuesta volvió a abrir.';
        unset($this->questions, $this->canSeeAllResults);
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->can('delete', $this->survey), 403);

        $this->survey->delete();

        $this->redirectRoute('surveys.index', navigate: true);
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <a
        href="{{ route('surveys.index') }}"
        wire:navigate
        class="inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
    >
        <x-icon name="arrow-left" class="h-4 w-4" />
        Encuestas
    </a>

    <header class="mt-5 border-b border-line pb-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-sm border px-1.5 py-0.5 text-3xs font-semibold tracking-label uppercase {{ $survey->statusClasses() }}">
                {{ $survey->statusLabel() }}
            </span>

            @if ($survey->closes_at)
                <span class="font-condensed text-3xs tabular-nums text-ink-muted">
                    {{ $survey->isOpen() ? 'Cierra' : 'Cerró' }} {{ $survey->closes_at->locale('es')->translatedFormat('j M Y') }}
                </span>
            @endif
        </div>

        <h1 class="mt-3 max-w-4xl font-expanded text-3xl font-black leading-tight tracking-display sm:text-4xl">
            {{ $survey->title }}
        </h1>

        @if ($survey->description)
            <p class="mt-3 max-w-prose text-sm leading-relaxed text-ink-secondary sm:text-base">
                {{ $survey->description }}
            </p>
        @endif

        <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-ink-muted">
            <span class="inline-flex items-center gap-1.5">
                <x-avatar :user="$survey->creator" size="2xs" />
                {{ $survey->creator->name }}
            </span>

            <span class="inline-flex items-center gap-1.5">
                <x-icon name="poll" class="h-3.5 w-3.5" />
                {{ $this->questions->count() }} {{ $this->questions->count() === 1 ? 'pregunta' : 'preguntas' }}
            </span>

            <span class="inline-flex items-center gap-1.5">
                <x-icon name="users" class="h-3.5 w-3.5" />
                {{ $this->participantCount }} {{ $this->participantCount === 1 ? 'participante' : 'participantes' }}
            </span>
        </div>
    </header>

    @if ($statusMessage !== '')
        <p class="mt-4 border-l-2 border-brand bg-brand-soft px-3 py-2 text-sm text-brand-strong" role="status">
            {{ $statusMessage }}
        </p>
    @endif

    <div class="mt-6 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="space-y-5 lg:col-span-8">
            @forelse ($this->questions as $question)
                @php
                    $hasVoted = $this->hasVotedQuestion($question);
                    $showForm = $survey->isOpen() && ! $hasVoted;
                    $showResults = $hasVoted || ! $survey->isOpen() || $this->canSeeAllResults;
                    $totalVotes = $question->votes->count();
                    $myOptionId = $question->votes->firstWhere('user_id', auth()->id())?->survey_option_id;
                @endphp

                <section class="border border-line bg-surface" wire:key="survey-question-{{ $question->id }}">
                    <header class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Pregunta {{ $loop->iteration }}</p>
                            <h2 class="mt-1 font-expanded text-lg font-bold leading-snug tracking-tight">
                                {{ $question->prompt }}
                            </h2>
                        </div>

                        <span class="inline-flex shrink-0 items-center gap-1 rounded-sm border border-line bg-canvas px-1.5 py-0.5 font-condensed text-3xs tabular-nums text-ink-muted">
                            {{ $totalVotes }} {{ $totalVotes === 1 ? 'respuesta' : 'respuestas' }}
                        </span>
                    </header>

                    <div class="px-5 py-5">
                        @if ($showForm)
                            <form wire:submit="vote({{ $question->id }})" class="space-y-2.5">
                                @foreach ($question->options as $option)
                                    <label
                                        wire:key="survey-option-{{ $option->id }}"
                                        class="flex cursor-pointer items-start gap-3 rounded-md border border-line bg-canvas px-4 py-3 transition duration-fast ease-signal hover:border-line-strong hover:bg-sunken"
                                    >
                                        <input
                                            type="radio"
                                            wire:model="answers.{{ $question->id }}"
                                            value="{{ $option->id }}"
                                            name="question-{{ $question->id }}"
                                            class="mt-0.5 h-4 w-4 shrink-0 border-line text-brand focus:ring-brand"
                                        >
                                        <span class="text-sm leading-snug text-ink">{{ $option->label }}</span>
                                    </label>
                                @endforeach

                                <x-input-error :messages="$errors->get('answers.'.$question->id)" />

                                <button
                                    type="submit"
                                    class="mt-2 inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                                >
                                    <x-icon name="check" class="h-4 w-4" />
                                    Enviar respuesta
                                </button>
                            </form>
                        @endif

                        @if ($showResults)
                            <div @class(['space-y-3', 'mt-5 border-t border-line pt-5' => $showForm])>
                                @forelse ($question->options as $option)
                                    @php
                                        $percentage = $totalVotes > 0 ? (int) round($option->votes_count / $totalVotes * 100) : 0;
                                        $isMine = $myOptionId === $option->id;
                                    @endphp

                                    <div wire:key="survey-result-{{ $option->id }}">
                                        <div class="flex items-baseline justify-between gap-3">
                                            <p @class(['text-sm', 'font-semibold' => $isMine, 'text-ink-secondary' => ! $isMine])>
                                                {{ $option->label }}

                                                @if ($isMine)
                                                    <span class="ml-1.5 inline-flex items-center gap-1 rounded-sm border border-accent/30 bg-accent-soft px-1.5 py-0.5 text-3xs font-semibold tracking-label text-accent-strong uppercase">
                                                        <x-icon name="check" class="h-3 w-3" />
                                                        Tu respuesta
                                                    </span>
                                                @endif
                                            </p>

                                            <p class="shrink-0 font-condensed text-xs tabular-nums text-ink-muted">
                                                {{ $option->votes_count }} · {{ $percentage }}%
                                            </p>
                                        </div>

                                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-sunken" role="presentation">
                                            <div
                                                @class(['h-full rounded-full transition-all duration-slow ease-signal', 'bg-brand' => $isMine, 'bg-line-strong' => ! $isMine])
                                                style="width: {{ $percentage }}%"
                                            ></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-ink-muted">Esta pregunta todavía no tiene opciones.</p>
                                @endforelse

                                @if ($totalVotes === 0)
                                    <p class="text-xs text-ink-muted">Todavía no hay respuestas para esta pregunta.</p>
                                @endif
                            </div>
                        @endif

                        @if (! $showForm && ! $showResults)
                            <p class="text-sm text-ink-muted">Esta pregunta ya no acepta respuestas.</p>
                        @endif
                    </div>
                </section>
            @empty
                <div class="flex flex-col items-start gap-4 border border-line bg-surface p-6 sm:flex-row sm:items-center">
                    <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-24 w-auto">
                    <div>
                        <p class="font-expanded text-xl font-bold">Esta encuesta todavía no tiene preguntas.</p>
                        <p class="mt-1 text-sm text-ink-muted">Vuelve más tarde o contacta a quien la creó.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <aside class="lg:col-span-4">
            <div class="border border-line bg-surface lg:sticky lg:top-20">
                <header class="border-b border-line px-5 py-4">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Detalles</h2>
                </header>

                <dl class="divide-y divide-line">
                    <div class="flex items-baseline justify-between gap-3 px-5 py-4">
                        <dt class="text-xs text-ink-muted">Estado</dt>
                        <dd class="text-xs font-semibold text-ink">{{ $survey->statusLabel() }}</dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3 px-5 py-4">
                        <dt class="text-xs text-ink-muted">Participantes</dt>
                        <dd class="font-condensed text-3xl font-black tabular-nums">{{ $this->participantCount }}</dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3 px-5 py-4">
                        <dt class="text-xs text-ink-muted">Preguntas</dt>
                        <dd class="font-condensed text-3xl font-black tabular-nums">{{ $this->questions->count() }}</dd>
                    </div>

                    @if ($survey->closes_at)
                        <div class="flex items-baseline justify-between gap-3 px-5 py-4">
                            <dt class="text-xs text-ink-muted">Fecha de cierre</dt>
                            <dd class="text-xs font-medium text-ink">
                                {{ $survey->closes_at->locale('es')->translatedFormat('j M Y') }}
                            </dd>
                        </div>
                    @endif

                    <div class="flex items-baseline justify-between gap-3 px-5 py-4">
                        <dt class="text-xs text-ink-muted">Creada por</dt>
                        <dd class="text-xs font-medium text-ink">{{ $survey->creator->name }}</dd>
                    </div>
                </dl>

                <section class="border-t border-line px-5 py-4">
                    <h3 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Cómo funciona</h3>
                    <ul class="mt-3 space-y-2 text-xs leading-relaxed text-ink-muted">
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                            Cada persona responde una sola vez por pregunta.
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                            Los resultados aparecen después de votar o al cerrar la encuesta.
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                            Las respuestas son anónimas para el resto del equipo.
                        </li>
                    </ul>
                </section>

                @can('update', $survey)
                    <section class="border-t border-line px-5 py-4">
                        <h3 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Administrar</h3>

                        <div class="mt-3 flex flex-col gap-2">
                            @if ($survey->is_active)
                                <button
                                    type="button"
                                    wire:click="close"
                                    wire:confirm="¿Cerrar la encuesta? Dejará de aceptar respuestas."
                                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                                >
                                    <x-icon name="lock" class="h-4 w-4" />
                                    Cerrar encuesta
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="reopen"
                                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                                >
                                    <x-icon name="refresh" class="h-4 w-4" />
                                    Reabrir encuesta
                                </button>
                            @endif

                            @can('delete', $survey)
                                <button
                                    type="button"
                                    wire:click="delete"
                                    wire:confirm="¿Eliminar esta encuesta y todas sus respuestas?"
                                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-danger/30 px-3 text-xs font-semibold tracking-label text-danger uppercase transition duration-fast ease-signal hover:bg-danger-soft"
                                >
                                    <x-icon name="trash" class="h-4 w-4" />
                                    Eliminar encuesta
                                </button>
                            @endcan
                        </div>
                    </section>
                @endcan
            </div>
        </aside>
    </div>
</div>
