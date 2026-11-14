<?php

use App\Enums\FormFieldType;
use App\Models\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Nuevo formulario · Daybridge')] class extends Component
{
    public string $title = '';

    public string $description = '';

    public string $closesAt = '';

    public string $areaId = '';

    /** @var array<int, array{label: string, type: string, required: bool, optionsText: string}> */
    public array $fields = [
        ['label' => '', 'type' => FormFieldType::Text->value, 'required' => false, 'optionsText' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('create', Form::class), 403);

        $this->closesAt = today()->addDays(14)->toDateString();
    }

    public function addField(): void
    {
        if (count($this->fields) >= 10) {
            return;
        }

        $this->fields[] = ['label' => '', 'type' => FormFieldType::Text->value, 'required' => false, 'optionsText' => ''];
    }

    public function removeField(int $index): void
    {
        if (count($this->fields) <= 1) {
            return;
        }

        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('create', Form::class), 403);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'closesAt' => ['nullable', 'date', 'after_or_equal:today'],
            'areaId' => ['nullable', Rule::exists('areas', 'id')],
            'fields' => ['required', 'array', 'min:1', 'max:10'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::enum(FormFieldType::class)],
            'fields.*.required' => ['boolean'],
            'fields.*.optionsText' => ['nullable', 'string', 'max:500'],
        ], [], [
            'title' => 'título',
            'description' => 'descripción',
            'closesAt' => 'fecha de cierre',
            'areaId' => 'área',
            'fields.*.label' => 'nombre del campo',
            'fields.*.type' => 'tipo de campo',
            'fields.*.optionsText' => 'opciones',
        ]);

        $fields = [];

        foreach ($validated['fields'] as $index => $field) {
            $options = null;

            if ($field['type'] === FormFieldType::Select->value) {
                $options = collect(preg_split('/[,\n]/', $field['optionsText'] ?? ''))
                    ->map(fn (string $option): string => trim($option))
                    ->filter()
                    ->values()
                    ->all();

                if (count($options) < 2) {
                    $this->addError('fields.'.$index.'.optionsText', 'Agrega al menos dos opciones separadas por coma.');

                    return;
                }
            }

            $fields[] = [
                'label' => $field['label'],
                'type' => $field['type'],
                'options' => $options,
                'is_required' => (bool) ($field['required'] ?? false),
                'sort_order' => $index,
            ];
        }

        $form = DB::transaction(function () use ($validated, $fields): Form {
            $form = Form::query()->create([
                'user_id' => auth()->id(),
                'area_id' => $validated['areaId'] ?: null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'closes_at' => $validated['closesAt'] ?: null,
                'is_active' => true,
            ]);

            foreach ($fields as $field) {
                $form->fields()->create($field);
            }

            return $form;
        });

        $this->redirectRoute('forms.show', $form, navigate: true);
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

    <header class="mt-4 border-b border-line pb-5">
        <h1 class="font-expanded text-3xl font-black tracking-display sm:text-4xl">Nuevo formulario</h1>
        <p class="mt-2 max-w-prose text-sm text-ink-muted">
            Define hasta diez campos. Cada persona puede enviar una respuesta y tú ves todas las respuestas en un solo lugar.
        </p>
    </header>

    <form wire:submit="save" class="mt-6 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="space-y-6 lg:col-span-8">
            <section class="border border-line bg-surface p-5">
                <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Datos generales</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="form-title" value="Título" />
                        <x-text-input id="form-title" wire:model="title" class="mt-1.5" maxlength="120" placeholder="Solicitud de equipo" />
                        <x-input-error :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <x-input-label for="form-description" value="Descripción" />
                        <textarea
                            id="form-description"
                            wire:model="description"
                            rows="3"
                            maxlength="500"
                            placeholder="Explica para qué sirve este formulario"
                            class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
                        ></textarea>
                        <x-input-error :messages="$errors->get('description')" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="form-closes-at" value="Fecha de cierre" />
                            <x-text-input id="form-closes-at" type="date" wire:model="closesAt" class="mt-1.5" />
                            <x-input-error :messages="$errors->get('closesAt')" />
                        </div>

                        <div>
                            <x-input-label for="form-area" value="Área" />
                            <select
                                id="form-area"
                                wire:model="areaId"
                                class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                            >
                                <option value="">Todas las áreas</option>
                                @foreach (\App\Models\Area::query()->orderBy('sort_order')->get() as $areaOption)
                                    <option value="{{ $areaOption->id }}">{{ $areaOption->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('areaId')" />
                        </div>
                    </div>
                </div>
            </section>

            @foreach ($fields as $fieldIndex => $field)
                <section class="border border-line bg-surface p-5" wire:key="form-field-{{ $fieldIndex }}">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Campo {{ $fieldIndex + 1 }}</h2>

                        @if (count($fields) > 1)
                            <button
                                type="button"
                                wire:click="removeField({{ $fieldIndex }})"
                                class="text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-danger"
                            >
                                Quitar
                            </button>
                        @endif
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="field-label-{{ $fieldIndex }}" value="Etiqueta" />
                            <x-text-input
                                id="field-label-{{ $fieldIndex }}"
                                wire:model="fields.{{ $fieldIndex }}.label"
                                class="mt-1.5"
                                maxlength="255"
                                placeholder="¿Qué necesitas saber?"
                            />
                            <x-input-error :messages="$errors->get('fields.'.$fieldIndex.'.label')" />
                        </div>

                        <div>
                            <x-input-label for="field-type-{{ $fieldIndex }}" value="Tipo" />
                            <select
                                id="field-type-{{ $fieldIndex }}"
                                wire:model.live="fields.{{ $fieldIndex }}.type"
                                class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                            >
                                @foreach (\App\Enums\FormFieldType::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('fields.'.$fieldIndex.'.type')" />
                        </div>

                        <div class="flex items-end">
                            <label class="inline-flex min-h-10 cursor-pointer items-center gap-2.5 text-sm text-ink">
                                <input
                                    type="checkbox"
                                    wire:model="fields.{{ $fieldIndex }}.required"
                                    class="h-4 w-4 rounded border-line text-brand focus:ring-brand"
                                >
                                Campo obligatorio
                            </label>
                            <x-input-error :messages="$errors->get('fields.'.$fieldIndex.'.required')" />
                        </div>

                        @if ($field['type'] === \App\Enums\FormFieldType::Select->value)
                            <div class="sm:col-span-2">
                                <x-input-label for="field-options-{{ $fieldIndex }}" value="Opciones" />
                                <textarea
                                    id="field-options-{{ $fieldIndex }}"
                                    wire:model="fields.{{ $fieldIndex }}.optionsText"
                                    rows="2"
                                    maxlength="500"
                                    placeholder="Opción A, Opción B, Opción C"
                                    class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
                                ></textarea>
                                <p class="mt-1 text-xs text-ink-muted">Separa cada opción con una coma o un salto de línea.</p>
                                <x-input-error :messages="$errors->get('fields.'.$fieldIndex.'.optionsText')" />
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach

            @if (count($fields) < 10)
                <button
                    type="button"
                    wire:click="addField"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-md border border-dashed border-line text-sm font-semibold text-ink-muted transition duration-fast ease-signal hover:border-line-strong hover:bg-sunken hover:text-ink"
                >
                    <x-icon name="plus" class="h-4 w-4" />
                    Agregar campo
                </button>
            @endif
        </div>

        <aside class="lg:col-span-4">
            <div class="border border-line bg-surface p-5 lg:sticky lg:top-20">
                <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Antes de publicar</h2>

                <ul class="mt-3 space-y-2 text-xs leading-relaxed text-ink-muted">
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                        Cada persona puede enviar una sola respuesta.
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                        Solo tú y la administración ven todas las respuestas.
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                        Puedes cerrarlo antes de la fecha si ya tienes lo que necesitas.
                    </li>
                </ul>

                <div class="mt-5 flex flex-col gap-2">
                    <x-primary-button>
                        <x-icon name="check" class="h-4 w-4" />
                        Publicar formulario
                    </x-primary-button>

                    <a
                        href="{{ route('forms.index') }}"
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
