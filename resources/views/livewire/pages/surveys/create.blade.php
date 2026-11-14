<?php

use App\Models\Survey;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Nueva encuesta · Daybridge')] class extends Component
{
    public string $title = '';

    public string $description = '';

    public string $closesAt = '';

    /** @var array<int, array{prompt: string, options: array<int, string>}> */
    public array $questions = [
        ['prompt' => '', 'options' => ['', '']],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('create', Survey::class), 403);

        $this->closesAt = today()->addDays(14)->toDateString();
    }

    public function addQuestion(): void
    {
        if (count($this->questions) >= 10) {
            return;
        }

        $this->questions[] = ['prompt' => '', 'options' => ['', '']];
    }

    public function removeQuestion(int $index): void
    {
        if (count($this->questions) <= 1) {
            return;
        }

        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    public function addOption(int $questionIndex): void
    {
        if (count($this->questions[$questionIndex]['options']) >= 5) {
            return;
        }

        $this->questions[$questionIndex]['options'][] = '';
    }

    public function removeOption(int $questionIndex, int $optionIndex): void
    {
        if (count($this->questions[$questionIndex]['options']) <= 2) {
            return;
        }

        unset($this->questions[$questionIndex]['options'][$optionIndex]);
        $this->questions[$questionIndex]['options'] = array_values($this->questions[$questionIndex]['options']);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'closesAt' => ['nullable', 'date', 'after_or_equal:today'],
            'questions' => ['required', 'array', 'min:1', 'max:10'],
            'questions.*.prompt' => ['required', 'string', 'max:255'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:5'],
            'questions.*.options.*' => ['required', 'string', 'max:120'],
        ], [], [
            'title' => 'título',
            'description' => 'descripción',
            'closesAt' => 'fecha de cierre',
            'questions.*.prompt' => 'pregunta',
            'questions.*.options' => 'opciones',
            'questions.*.options.*' => 'opción',
        ]);

        $survey = DB::transaction(function () use ($validated): Survey {
            $survey = Survey::query()->create([
                'user_id' => auth()->id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'closes_at' => $validated['closesAt'] ?: null,
                'is_active' => true,
            ]);

            foreach ($validated['questions'] as $questionIndex => $question) {
                $createdQuestion = $survey->questions()->create([
                    'prompt' => $question['prompt'],
                    'sort_order' => $questionIndex,
                ]);

                foreach ($question['options'] as $optionIndex => $option) {
                    $createdQuestion->options()->create([
                        'label' => $option,
                        'sort_order' => $optionIndex,
                    ]);
                }
            }

            return $survey;
        });

        $this->redirectRoute('surveys.show', $survey, navigate: true);
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

    <header class="mt-4 border-b border-line pb-5">
        <h1 class="font-expanded text-3xl font-black tracking-display sm:text-4xl">Nueva encuesta</h1>
        <p class="mt-2 max-w-prose text-sm text-ink-muted">
            Define hasta diez preguntas con dos a cinco opciones cada una. Las respuestas se guardan una sola vez por persona.
        </p>
    </header>

    <form wire:submit="save" class="mt-6 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="space-y-6 lg:col-span-8">
            <section class="border border-line bg-surface p-5">
                <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Datos generales</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="title" value="Título" />
                        <x-text-input id="title" wire:model="title" class="mt-1.5" maxlength="120" placeholder="Clima del trimestre" />
                        <x-input-error :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Descripción" />
                        <textarea
                            id="description"
                            wire:model="description"
                            rows="3"
                            maxlength="500"
                            placeholder="Opcional: para qué sirve esta encuesta"
                            class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
                        ></textarea>
                        <x-input-error :messages="$errors->get('description')" />
                    </div>

                    <div class="sm:w-56">
                        <x-input-label for="closesAt" value="Fecha de cierre" />
                        <x-text-input id="closesAt" type="date" wire:model="closesAt" class="mt-1.5" />
                        <x-input-error :messages="$errors->get('closesAt')" />
                    </div>
                </div>
            </section>

            @foreach ($questions as $questionIndex => $question)
                <section class="border border-line bg-surface p-5" wire:key="survey-question-{{ $questionIndex }}">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Pregunta {{ $questionIndex + 1 }}</h2>

                        @if (count($questions) > 1)
                            <button
                                type="button"
                                wire:click="removeQuestion({{ $questionIndex }})"
                                class="text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-danger"
                            >
                                Quitar
                            </button>
                        @endif
                    </div>

                    <div class="mt-4">
                        <x-input-label for="question-{{ $questionIndex }}" value="Enunciado" />
                        <x-text-input
                            id="question-{{ $questionIndex }}"
                            wire:model="questions.{{ $questionIndex }}.prompt"
                            class="mt-1.5"
                            maxlength="255"
                            placeholder="¿Qué quieres preguntar?"
                        />
                        <x-input-error :messages="$errors->get('questions.'.$questionIndex.'.prompt')" />
                    </div>

                    <div class="mt-4 space-y-2">
                        @foreach ($question['options'] as $optionIndex => $option)
                            <div class="flex items-center gap-2" wire:key="survey-option-{{ $questionIndex }}-{{ $optionIndex }}">
                                <label for="option-{{ $questionIndex }}-{{ $optionIndex }}" class="sr-only">Opción {{ $optionIndex + 1 }}</label>
                                <x-text-input
                                    id="option-{{ $questionIndex }}-{{ $optionIndex }}"
                                    wire:model="questions.{{ $questionIndex }}.options.{{ $optionIndex }}"
                                    maxlength="120"
                                    placeholder="Opción {{ $optionIndex + 1 }}"
                                />

                                @if (count($question['options']) > 2)
                                    <button
                                        type="button"
                                        wire:click="removeOption({{ $questionIndex }}, {{ $optionIndex }})"
                                        aria-label="Quitar opción {{ $optionIndex + 1 }}"
                                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-danger/30 text-danger transition duration-fast ease-signal hover:bg-danger-soft"
                                    >
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                @endif
                            </div>
                        @endforeach

                        <x-input-error :messages="$errors->get('questions.'.$questionIndex.'.options')" />

                        @foreach ($errors->get('questions.'.$questionIndex.'.options.*') as $optionMessages)
                            <x-input-error :messages="$optionMessages" />
                        @endforeach

                        @if (count($question['options']) < 5)
                            <button
                                type="button"
                                wire:click="addOption({{ $questionIndex }})"
                                class="inline-flex min-h-10 items-center gap-1.5 text-xs font-semibold text-accent-strong transition duration-fast ease-signal hover:text-accent"
                            >
                                <x-icon name="plus" class="h-4 w-4" />
                                Agregar opción
                            </button>
                        @endif
                    </div>
                </section>
            @endforeach

            @if (count($questions) < 10)
                <button
                    type="button"
                    wire:click="addQuestion"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-md border border-dashed border-line text-sm font-semibold text-ink-muted transition duration-fast ease-signal hover:border-line-strong hover:bg-sunken hover:text-ink"
                >
                    <x-icon name="plus" class="h-4 w-4" />
                    Agregar pregunta
                </button>
            @endif
        </div>

        <aside class="lg:col-span-4">
            <div class="border border-line bg-surface p-5 lg:sticky lg:top-20">
                <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Antes de publicar</h2>

                <ul class="mt-3 space-y-2 text-xs leading-relaxed text-ink-muted">
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                        Cada persona puede responder una vez por pregunta.
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                        Los resultados se muestran después de votar o al cerrar la encuesta.
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                        Puedes cerrarla antes de la fecha si ya tienes suficientes respuestas.
                    </li>
                </ul>

                <div class="mt-5 flex flex-col gap-2">
                    <x-primary-button>
                        <x-icon name="check" class="h-4 w-4" />
                        Publicar encuesta
                    </x-primary-button>

                    <a
                        href="{{ route('surveys.index') }}"
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
