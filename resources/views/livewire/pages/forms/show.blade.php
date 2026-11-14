<?php

use App\Enums\FormFieldType;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Formulario · Daybridge')] class extends Component
{
    public Form $form;

    /** @var array<int, string|null> */
    public array $answers = [];

    public string $statusMessage = '';

    public function mount(Form $form): void
    {
        abort_unless(auth()->user()->can('view', $form), 403);

        $this->form = $form->load(['creator.area', 'area']);
    }

    #[Computed]
    public function fields(): Collection
    {
        return $this->form->fields()->get();
    }

    #[Computed]
    public function mySubmission(): ?FormSubmission
    {
        return $this->form->submissions()
            ->where('user_id', auth()->id())
            ->first();
    }

    #[Computed]
    public function canSubmit(): bool
    {
        return auth()->user()->can('submit', $this->form);
    }

    #[Computed]
    public function canSeeResponses(): bool
    {
        return auth()->user()->can('viewResponses', $this->form);
    }

    #[Computed]
    public function submissions(): Collection
    {
        if (! $this->canSeeResponses) {
            return collect();
        }

        return $this->form->submissions()
            ->with('user')
            ->get();
    }

    #[Computed]
    public function responseCount(): int
    {
        return $this->form->submissions()->count();
    }

    public function submit(): void
    {
        $form = $this->form->fresh(['fields']);

        abort_unless(auth()->user()->can('submit', $form), 403);

        $rules = [];
        $attributes = [];

        foreach ($form->fields as $field) {
            $rules['answers.'.$field->id] = $this->rulesFor($field);
            $attributes['answers.'.$field->id] = mb_strtolower($field->label);
        }

        $this->validate($rules, [], $attributes);

        $answers = [];

        foreach ($form->fields as $field) {
            $value = $this->answers[$field->id] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $answers[$field->id] = $value === '' ? null : $value;
        }

        try {
            FormSubmission::query()->create([
                'form_id' => $form->id,
                'user_id' => auth()->id(),
                'answers' => $answers,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Doble envío simultáneo: la respuesta ya quedó registrada.
            $this->statusMessage = 'Ya habías enviado una respuesta a este formulario.';

            return;
        }

        $this->answers = [];
        $this->statusMessage = 'Tu respuesta se envió correctamente.';
        unset($this->mySubmission, $this->canSubmit, $this->submissions, $this->responseCount);
    }

    public function close(): void
    {
        abort_unless(auth()->user()->can('update', $this->form), 403);

        $this->form->update(['is_active' => false]);
        $this->form->refresh();

        $this->statusMessage = 'El formulario se cerró.';
        unset($this->canSubmit, $this->canSeeResponses);
    }

    public function reopen(): void
    {
        abort_unless(auth()->user()->can('update', $this->form), 403);

        $closesAt = $this->form->closes_at;

        if ($closesAt === null || $closesAt->isBefore(today())) {
            $closesAt = today()->addDays(14);
        }

        $this->form->update([
            'is_active' => true,
            'closes_at' => $closesAt,
        ]);
        $this->form->refresh();

        $this->statusMessage = 'El formulario volvió a abrir.';
        unset($this->canSubmit, $this->canSeeResponses);
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->can('delete', $this->form), 403);

        $this->form->delete();

        $this->redirectRoute('forms.index', navigate: true);
    }

    /**
     * @return array<int, mixed>
     */
    private function rulesFor(FormField $field): array
    {
        $presence = $field->is_required ? 'required' : 'nullable';

        return match ($field->type) {
            FormFieldType::Email => [$presence, 'email', 'max:255'],
            FormFieldType::Number => [$presence, 'numeric'],
            FormFieldType::Date => [$presence, 'date'],
            FormFieldType::Select => [$presence, 'string', Rule::in($field->options ?? [])],
            FormFieldType::Textarea => [$presence, 'string', 'max:2000'],
            default => [$presence, 'string', 'max:255'],
        };
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <a
        href="{{ route('forms.index') }}"
        wire:navigate
        class="inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
    >
        <x-icon name="arrow-left" class="h-4 w-4" />
        Formularios
    </a>

    <header class="mt-5 border-b border-line pb-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-sm border px-1.5 py-0.5 text-3xs font-semibold tracking-label uppercase {{ $form->statusClasses() }}">
                {{ $form->statusLabel() }}
            </span>

            @if ($form->area)
                <x-area-label :area="$form->area" />
            @endif

            @if ($form->closes_at)
                <span class="font-condensed text-3xs tabular-nums text-ink-muted">
                    {{ $form->isOpen() ? 'Cierra' : 'Cerró' }} {{ $form->closes_at->locale('es')->translatedFormat('j M Y') }}
                </span>
            @endif
        </div>

        <h1 class="mt-3 max-w-4xl font-expanded text-3xl font-black leading-tight tracking-display sm:text-4xl">
            {{ $form->title }}
        </h1>

        @if ($form->description)
            <p class="mt-3 max-w-prose text-sm leading-relaxed text-ink-secondary sm:text-base">
                {{ $form->description }}
            </p>
        @endif

        <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-ink-muted">
            <span class="inline-flex items-center gap-1.5">
                <x-avatar :user="$form->creator" size="2xs" />
                {{ $form->creator->name }}
            </span>

            <span class="inline-flex items-center gap-1.5">
                <x-icon name="clipboard" class="h-3.5 w-3.5" />
                {{ $this->fields->count() }} {{ $this->fields->count() === 1 ? 'campo' : 'campos' }}
            </span>

            <span class="inline-flex items-center gap-1.5">
                <x-icon name="users" class="h-3.5 w-3.5" />
                {{ $this->responseCount }} {{ $this->responseCount === 1 ? 'respuesta' : 'respuestas' }}
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
            @if ($this->mySubmission)
                <section class="border border-line bg-surface">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                        <div>
                            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Tu respuesta</p>
                            <h2 class="mt-1 font-expanded text-lg font-bold tracking-tight">Enviada correctamente</h2>
                        </div>

                        <span class="inline-flex items-center gap-1 rounded-sm border border-success/30 bg-success-soft px-1.5 py-0.5 text-3xs font-semibold tracking-label text-success uppercase">
                            <x-icon name="check" class="h-3 w-3" />
                            Registrada
                        </span>
                    </header>

                    <dl class="divide-y divide-line">
                        @foreach ($this->fields as $field)
                            <div class="px-5 py-4">
                                <dt class="text-3xs font-semibold tracking-label text-ink-muted uppercase">{{ $field->label }}</dt>
                                <dd class="mt-1 text-sm text-ink">
                                    {{ $this->mySubmission->answerFor($field->id) ?? 'Sin respuesta' }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    <p class="border-t border-line px-5 py-3 text-xs text-ink-muted">
                        Enviada el {{ $this->mySubmission->created_at->locale('es')->translatedFormat('j M Y, H:i') }}.
                    </p>
                </section>
            @elseif ($this->canSubmit)
                <form wire:submit="submit" class="border border-line bg-surface">
                    <header class="border-b border-line px-5 py-4">
                        <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Formulario</p>
                        <h2 class="mt-1 font-expanded text-lg font-bold tracking-tight">Completa los campos</h2>
                    </header>

                    <div class="space-y-5 px-5 py-5">
                        @forelse ($this->fields as $field)
                            <div wire:key="form-answer-{{ $field->id }}">
                                <x-input-label
                                    :for="'answer-'.$field->id"
                                    :value="$field->label.($field->is_required ? ' *' : '')"
                                />

                                @if ($field->type === FormFieldType::Textarea)
                                    <textarea
                                        id="answer-{{ $field->id }}"
                                        wire:model="answers.{{ $field->id }}"
                                        rows="4"
                                        maxlength="2000"
                                        class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
                                    ></textarea>
                                @elseif ($field->type === FormFieldType::Select)
                                    <select
                                        id="answer-{{ $field->id }}"
                                        wire:model="answers.{{ $field->id }}"
                                        class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                                    >
                                        <option value="">Selecciona una opción</option>
                                        @foreach ($field->options ?? [] as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <x-text-input
                                        :id="'answer-'.$field->id"
                                        type="{{ $field->type->inputType() }}"
                                        wire:model="answers.{{ $field->id }}"
                                        class="mt-1.5"
                                        maxlength="255"
                                    />
                                @endif

                                <x-input-error :messages="$errors->get('answers.'.$field->id)" />
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">Este formulario todavía no tiene campos.</p>
                        @endforelse
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line px-5 py-4">
                        <p class="mr-auto text-xs text-ink-muted">Solo puedes enviar una respuesta.</p>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="submit"
                            class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <x-icon name="check" class="h-4 w-4" />
                            <span wire:loading.remove wire:target="submit">Enviar respuesta</span>
                            <span wire:loading wire:target="submit">Enviando...</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="flex flex-col items-start gap-4 border border-line bg-surface p-6 sm:flex-row sm:items-center">
                    <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-24 w-auto">
                    <div>
                        <p class="font-expanded text-xl font-bold">Este formulario ya no acepta respuestas.</p>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ $form->is_active ? 'La fecha de cierre ya pasó.' : 'Quien lo creó lo cerró.' }}
                        </p>
                    </div>
                </div>
            @endif

            @if ($this->canSeeResponses)
                <section class="border border-line bg-surface">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                        <div>
                            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Administración</p>
                            <h2 class="mt-1 font-expanded text-lg font-bold tracking-tight">Respuestas recibidas</h2>
                        </div>

                        <span class="inline-flex items-center gap-1 rounded-sm border border-line bg-canvas px-1.5 py-0.5 font-condensed text-3xs tabular-nums text-ink-muted">
                            {{ $this->responseCount }} {{ $this->responseCount === 1 ? 'respuesta' : 'respuestas' }}
                        </span>
                    </header>

                    @if ($this->submissions->isNotEmpty())
                        <div class="divide-y divide-line">
                            @foreach ($this->submissions as $submission)
                                <article class="px-5 py-5" wire:key="form-submission-{{ $submission->id }}">
                                    <header class="flex flex-wrap items-center justify-between gap-3">
                                        <span class="inline-flex items-center gap-2 text-sm font-medium text-ink">
                                            <x-avatar :user="$submission->user" size="2xs" />
                                            {{ $submission->user->name }}
                                        </span>

                                        <span class="font-condensed text-3xs tabular-nums text-ink-muted">
                                            {{ $submission->created_at->locale('es')->translatedFormat('j M Y, H:i') }}
                                        </span>
                                    </header>

                                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                                        @foreach ($this->fields as $field)
                                            <div>
                                                <dt class="text-3xs font-semibold tracking-label text-ink-muted uppercase">{{ $field->label }}</dt>
                                                <dd class="mt-0.5 text-sm text-ink-secondary">
                                                    {{ $submission->answerFor($field->id) ?? 'Sin respuesta' }}
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="px-5 py-6 text-sm text-ink-muted">
                            Todavía no hay respuestas. Comparte el formulario con el equipo para empezar a recibirlas.
                        </div>
                    @endif
                </section>
            @endif
        </div>

        <aside class="lg:col-span-4">
            <div class="border border-line bg-surface lg:sticky lg:top-20">
                <header class="border-b border-line px-5 py-4">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Detalles</h2>
                </header>

                <dl class="divide-y divide-line">
                    <div class="flex items-baseline justify-between gap-3 px-5 py-4">
                        <dt class="text-xs text-ink-muted">Estado</dt>
                        <dd class="text-xs font-semibold text-ink">{{ $form->statusLabel() }}</dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3 px-5 py-4">
                        <dt class="text-xs text-ink-muted">Respuestas</dt>
                        <dd class="font-condensed text-3xl font-black tabular-nums">{{ $this->responseCount }}</dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3 px-5 py-4">
                        <dt class="text-xs text-ink-muted">Campos</dt>
                        <dd class="font-condensed text-3xl font-black tabular-nums">{{ $this->fields->count() }}</dd>
                    </div>

                    @if ($form->closes_at)
                        <div class="flex items-baseline justify-between gap-3 px-5 py-4">
                            <dt class="text-xs text-ink-muted">Fecha de cierre</dt>
                            <dd class="text-xs font-medium text-ink">
                                {{ $form->closes_at->locale('es')->translatedFormat('j M Y') }}
                            </dd>
                        </div>
                    @endif

                    <div class="flex items-baseline justify-between gap-3 px-5 py-4">
                        <dt class="text-xs text-ink-muted">Creado por</dt>
                        <dd class="text-xs font-medium text-ink">{{ $form->creator->name }}</dd>
                    </div>
                </dl>

                <section class="border-t border-line px-5 py-4">
                    <h3 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Cómo funciona</h3>
                    <ul class="mt-3 space-y-2 text-xs leading-relaxed text-ink-muted">
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                            Cada persona puede enviar una sola respuesta.
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                            Quien creó el formulario y la administración ven todas las respuestas.
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                            Las respuestas quedan guardadas aunque el formulario se cierre.
                        </li>
                    </ul>
                </section>

                @can('update', $form)
                    <section class="border-t border-line px-5 py-4">
                        <h3 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Administrar</h3>

                        <div class="mt-3 flex flex-col gap-2">
                            @if ($form->is_active)
                                <button
                                    type="button"
                                    wire:click="close"
                                    wire:confirm="¿Cerrar el formulario? Dejará de aceptar respuestas."
                                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                                >
                                    <x-icon name="lock" class="h-4 w-4" />
                                    Cerrar formulario
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="reopen"
                                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                                >
                                    <x-icon name="refresh" class="h-4 w-4" />
                                    Reabrir formulario
                                </button>
                            @endif

                            @can('delete', $form)
                                <button
                                    type="button"
                                    wire:click="delete"
                                    wire:confirm="¿Eliminar este formulario y todas sus respuestas?"
                                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-danger/30 px-3 text-xs font-semibold tracking-label text-danger uppercase transition duration-fast ease-signal hover:bg-danger-soft"
                                >
                                    <x-icon name="trash" class="h-4 w-4" />
                                    Eliminar formulario
                                </button>
                            @endcan
                        </div>
                    </section>
                @endcan
            </div>
        </aside>
    </div>
</div>
