<?php

use App\Enums\DocumentCategory;
use App\Models\Area;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Documentos · Daybridge')] class extends Component
{
    use WithFileUploads;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'area')]
    public string $area = '';

    #[Url(as: 'categoria')]
    public string $category = '';

    public bool $showForm = false;

    public string $title = '';

    public string $description = '';

    public string $areaId = '';

    public string $documentCategory = DocumentCategory::Other->value;

    public $file = null;

    public string $statusMessage = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', Document::class), 403);
    }

    #[Computed]
    public function documents(): Collection
    {
        return Document::query()
            ->with(['area', 'uploader.area'])
            ->when($this->search !== '', fn (Builder $query): Builder => $query->search($this->search))
            ->when($this->area !== '', fn (Builder $query): Builder => $query->whereHas(
                'area',
                fn (Builder $query): Builder => $query->where('slug', $this->area),
            ))
            ->when($this->category !== '', fn (Builder $query): Builder => $query->where('category', $this->category))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function areas(): Collection
    {
        return Area::query()
            ->withCount('documents')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function categories(): array
    {
        return DocumentCategory::options();
    }

    public function toggleForm(): void
    {
        abort_unless(auth()->user()->can('create', Document::class), 403);

        $this->showForm = ! $this->showForm;
        $this->statusMessage = '';
        $this->resetValidation();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('create', Document::class), 403);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'areaId' => ['nullable', Rule::exists('areas', 'id')],
            'documentCategory' => ['required', Rule::enum(DocumentCategory::class)],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv'],
        ], [], [
            'title' => 'título',
            'description' => 'descripción',
            'areaId' => 'área',
            'documentCategory' => 'categoría',
            'file' => 'archivo',
        ]);

        $originalName = $this->file->getClientOriginalName();
        $mimeType = $this->file->getMimeType() ?? 'application/octet-stream';
        $fileSize = $this->file->getSize();
        $path = $this->file->store('documents', 'local');

        Document::query()->create([
            'user_id' => auth()->id(),
            'area_id' => $validated['areaId'] ?: null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['documentCategory'],
            'file_path' => $path,
            'file_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);

        $this->resetForm();
        $this->showForm = false;
        $this->statusMessage = 'Documento publicado en la biblioteca.';
        unset($this->documents, $this->areas);
    }

    public function delete(int $documentId): void
    {
        $document = Document::query()->findOrFail($documentId);

        abort_unless(auth()->user()->can('delete', $document), 403);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        $this->statusMessage = 'Documento eliminado.';
        unset($this->documents, $this->areas);
    }

    public function resetForm(): void
    {
        $this->title = '';
        $this->description = '';
        $this->areaId = '';
        $this->documentCategory = DocumentCategory::Other->value;
        $this->file = null;
        $this->resetValidation();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->area = '';
        $this->category = '';
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Biblioteca</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Documentos</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Políticas, manuales y formatos compartidos por las áreas. Todo en un solo lugar y siempre a mano.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <p class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink-muted uppercase">
                <x-icon name="folder" class="h-4 w-4" />
                {{ $this->documents->count() }} {{ $this->documents->count() === 1 ? 'archivo' : 'archivos' }}
            </p>

            @can('create', Document::class)
                <button
                    type="button"
                    wire:click="toggleForm"
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                >
                    <x-icon :name="$showForm ? 'x' : 'upload'" class="h-4 w-4" />
                    {{ $showForm ? 'Cerrar' : 'Subir documento' }}
                </button>
            @endcan
        </div>
    </header>

    @if ($statusMessage !== '')
        <p class="mt-4 border-l-2 border-brand bg-brand-soft px-3 py-2 text-sm text-brand-strong" role="status">
            {{ $statusMessage }}
        </p>
    @endif

    @if ($showForm)
        <form wire:submit="save" class="mt-5 border border-line bg-surface p-5">
            <div class="flex items-center justify-between gap-3 border-b border-line pb-4">
                <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Nuevo documento</h2>

                <button
                    type="button"
                    wire:click="toggleForm"
                    class="text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-danger"
                >
                    Cancelar
                </button>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <x-input-label for="document-title" value="Título" />
                    <x-text-input id="document-title" wire:model="title" class="mt-1.5" maxlength="120" placeholder="Manual de bienvenida" />
                    <x-input-error :messages="$errors->get('title')" />
                </div>

                <div class="lg:col-span-2">
                    <x-input-label for="document-description" value="Descripción" />
                    <textarea
                        id="document-description"
                        wire:model="description"
                        rows="2"
                        maxlength="500"
                        placeholder="Para qué sirve este documento"
                        class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-1 focus:ring-accent focus:ring-offset-0"
                    ></textarea>
                    <x-input-error :messages="$errors->get('description')" />
                </div>

                <div>
                    <x-input-label for="document-area" value="Área" />
                    <select
                        id="document-area"
                        wire:model="areaId"
                        class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                    >
                        <option value="">Todas las áreas</option>
                        @foreach ($this->areas as $areaOption)
                            <option value="{{ $areaOption->id }}">{{ $areaOption->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('areaId')" />
                </div>

                <div>
                    <x-input-label for="document-category" value="Categoría" />
                    <select
                        id="document-category"
                        wire:model="documentCategory"
                        class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                    >
                        @foreach ($this->categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('documentCategory')" />
                </div>

                <div class="lg:col-span-2">
                    <x-input-label for="document-file" value="Archivo" />
                    <input
                        id="document-file"
                        type="file"
                        wire:model="file"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv"
                        class="mt-1.5 block w-full cursor-pointer rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink-secondary file:mr-3 file:rounded-md file:border-0 file:bg-brand-soft file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-brand-strong"
                    >
                    <p class="mt-1.5 text-xs text-ink-muted">
                        PDF, Word, Excel, PowerPoint, TXT o CSV. Máximo 10 MB.
                        @if ($file)
                            <span class="font-medium text-ink">{{ $file->getClientOriginalName() }}</span>
                        @endif
                    </p>
                    <x-input-error :messages="$errors->get('file')" />
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-line pt-4">
                <button
                    type="button"
                    wire:click="resetForm"
                    class="inline-flex min-h-10 items-center rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                >
                    Limpiar
                </button>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="file,save"
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <x-icon name="upload" class="h-4 w-4" />
                    <span wire:loading.remove wire:target="save">Publicar documento</span>
                    <span wire:loading wire:target="save">Subiendo...</span>
                </button>
            </div>
        </form>
    @endif

    <div class="mt-5 border border-line bg-surface p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="relative lg:col-span-2">
                <span class="sr-only">Buscar documentos</span>
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" />
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por título, descripción o archivo"
                    class="w-full rounded-md border-line bg-canvas py-2.5 pl-9 pr-3 text-sm placeholder:text-ink-disabled focus:border-brand focus:ring-brand"
                >
            </label>

            <label>
                <span class="sr-only">Filtrar por área</span>
                <select wire:model.live="area" class="w-full rounded-md border-line bg-canvas py-2.5 text-sm focus:border-brand focus:ring-brand">
                    <option value="">Todas las áreas</option>
                    @foreach ($this->areas as $areaOption)
                        <option value="{{ $areaOption->slug }}">{{ $areaOption->name }} ({{ $areaOption->documents_count }})</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="sr-only">Filtrar por categoría</span>
                <select wire:model.live="category" class="w-full rounded-md border-line bg-canvas py-2.5 text-sm focus:border-brand focus:ring-brand">
                    <option value="">Todas las categorías</option>
                    @foreach ($this->categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="mt-3 flex items-center justify-between gap-3">
            <p class="text-xs text-ink-muted">
                {{ $this->documents->count() }} {{ $this->documents->count() === 1 ? 'documento' : 'documentos' }} en esta vista
            </p>

            <button
                type="button"
                wire:click="clearFilters"
                class="text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
            >
                Limpiar filtros
            </button>
        </div>
    </div>

    @if ($this->documents->isNotEmpty())
        <div class="mt-5 divide-y divide-line border-y border-line">
            @foreach ($this->documents as $document)
                <article class="flex flex-col gap-4 py-5 sm:flex-row sm:items-start" wire:key="document-{{ $document->id }}">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-md border border-line bg-sunken font-condensed text-3xs font-bold tracking-label text-ink-muted">
                        {{ $document->extension() }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-sm border border-line bg-canvas px-1.5 py-0.5 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                                {{ $document->category->label() }}
                            </span>

                            @if ($document->area)
                                <x-area-label :area="$document->area" />
                            @endif
                        </div>

                        <h2 class="mt-1.5 font-expanded text-lg font-bold leading-snug tracking-tight">
                            {{ $document->title }}
                        </h2>

                        @if ($document->description)
                            <p class="mt-1 max-w-prose text-sm text-ink-secondary">{{ $document->description }}</p>
                        @endif

                        <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-muted">
                            <span class="inline-flex items-center gap-1.5">
                                <x-avatar :user="$document->uploader" size="2xs" />
                                {{ $document->uploader->name }}
                            </span>
                            <span class="font-condensed tabular-nums">
                                {{ $document->created_at->locale('es')->translatedFormat('j M Y') }}
                            </span>
                            <span class="font-condensed tabular-nums">{{ $document->formattedSize() }}</span>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-2 sm:flex-col sm:items-stretch">
                        <a
                            href="{{ route('documents.download', $document) }}"
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                        >
                            <x-icon name="download" class="h-4 w-4" />
                            Descargar
                        </a>

                        @can('delete', $document)
                            <button
                                type="button"
                                wire:click="delete({{ $document->id }})"
                                wire:confirm="¿Eliminar este documento de la biblioteca?"
                                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-danger/30 px-3 text-xs font-semibold tracking-label text-danger uppercase transition duration-fast ease-signal hover:bg-danger-soft"
                            >
                                <x-icon name="trash" class="h-4 w-4" />
                                Eliminar
                            </button>
                        @endcan
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="mt-5 flex flex-col items-start gap-4 border border-line bg-surface p-6 sm:flex-row sm:items-center">
            <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-24 w-auto">
            <div>
                <p class="font-expanded text-xl font-bold">No encontramos documentos con esos filtros.</p>
                <p class="mt-1 text-sm text-ink-muted">
                    Prueba con otra búsqueda, cambia el área o sube el primer archivo de esta categoría.
                </p>
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
    @endif
</div>
