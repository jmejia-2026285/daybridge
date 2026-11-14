<?php

use App\Models\Form;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Formularios · Daybridge')] class extends Component
{
    #[Url(as: 'estado')]
    public string $status = 'abiertos';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', Form::class), 403);
    }

    #[Computed]
    public function forms(): Collection
    {
        return Form::query()
            ->with([
                'creator.area',
                'area',
                'submissions' => fn ($query) => $query->where('user_id', auth()->id()),
            ])
            ->withCount(['fields', 'submissions'])
            ->when($this->status === 'abiertos', fn ($query) => $query->open())
            ->when($this->status === 'cerrados', fn ($query) => $query->closed())
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Solicitudes</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Formularios</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Solicitudes internas, registros y confirmaciones con la información ordenada desde el primer clic.
            </p>
        </div>

        @can('create', Form::class)
            <a
                href="{{ route('forms.create') }}"
                wire:navigate
                class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Nuevo formulario
            </a>
        @endcan
    </header>

    <nav class="mt-5 flex gap-5 overflow-x-auto pb-1" aria-label="Filtrar formularios">
        @foreach (['abiertos' => 'Abiertos', 'cerrados' => 'Cerrados', 'todos' => 'Todos'] as $value => $label)
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

    @if ($this->forms->isNotEmpty())
        <div class="mt-6 space-y-4">
            @foreach ($this->forms as $form)
                @php
                    $hasSubmitted = $form->hasSubmittedBy(auth()->user());
                @endphp

                <article class="border border-line bg-surface p-5" wire:key="form-{{ $form->id }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-sm border px-1.5 py-0.5 text-3xs font-semibold tracking-label uppercase {{ $form->statusClasses() }}">
                                    {{ $form->statusLabel() }}
                                </span>

                                @if ($hasSubmitted)
                                    <span class="inline-flex items-center gap-1 rounded-sm border border-success/30 bg-success-soft px-1.5 py-0.5 text-3xs font-semibold tracking-label text-success uppercase">
                                        <x-icon name="check" class="h-3 w-3" />
                                        Ya respondiste
                                    </span>
                                @endif

                                @if ($form->area)
                                    <x-area-label :area="$form->area" />
                                @endif

                                @if ($form->closes_at)
                                    <span class="font-condensed text-3xs tabular-nums text-ink-muted">
                                        {{ $form->isOpen() ? 'Cierra' : 'Cerró' }} {{ $form->closes_at->locale('es')->translatedFormat('j M Y') }}
                                    </span>
                                @endif
                            </div>

                            <h2 class="mt-2 font-expanded text-xl font-bold leading-tight tracking-tight">
                                {{ $form->title }}
                            </h2>

                            @if ($form->description)
                                <p class="mt-1.5 max-w-prose text-sm text-ink-secondary">{{ $form->description }}</p>
                            @endif

                            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-ink-muted">
                                <span class="inline-flex items-center gap-1.5">
                                    <x-avatar :user="$form->creator" size="2xs" />
                                    {{ $form->creator->name }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <x-icon name="clipboard" class="h-3.5 w-3.5" />
                                    {{ $form->fields_count }} {{ $form->fields_count === 1 ? 'campo' : 'campos' }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <x-icon name="users" class="h-3.5 w-3.5" />
                                    {{ $form->submissions_count }} {{ $form->submissions_count === 1 ? 'respuesta' : 'respuestas' }}
                                </span>
                            </div>
                        </div>

                        <a
                            href="{{ route('forms.show', $form) }}"
                            wire:navigate
                            class="inline-flex min-h-10 shrink-0 items-center gap-1.5 self-start rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                        >
                            {{ $form->isOpen() && ! $hasSubmitted ? 'Responder' : 'Ver formulario' }}
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
                    {{ $status === 'cerrados' ? 'No hay formularios cerrados.' : 'No hay formularios abiertos por ahora.' }}
                </p>
                <p class="mt-1 text-sm text-ink-muted">
                    @can('create', Form::class)
                        Crea un formulario nuevo o revisa los que ya cerraron.
                    @else
                        Cuando el equipo publique un formulario, aparecerá aquí.
                    @endcan
                </p>
            </div>
        </div>
    @endif
</div>
