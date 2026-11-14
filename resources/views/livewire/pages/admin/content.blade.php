<?php

use App\Models\Area;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Comunicados · Administración')] class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    public string $status = '';

    public bool $formOpen = false;

    public ?int $editingPostId = null;

    public string $title = '';

    public string $highlight = '';

    public string $excerpt = '';

    public string $body = '';

    public string $type = Post::TYPE_NEWS;

    public string $areaId = '';

    public bool $isUrgent = false;

    public bool $isFeatured = false;

    public string $publishedAt = '';

    public bool $publishNow = false;

    public ?int $deletingPostId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('create', Post::class), 403);
    }

    #[Computed]
    public function posts()
    {
        return Post::query()
            ->with(['area', 'author.area'])
            ->when(
                $this->search !== '',
                fn (Builder $query): Builder => $query->where(
                    fn (Builder $query): Builder => $query
                        ->where('title', 'like', "%{$this->search}%")
                        ->orWhere('body', 'like', "%{$this->search}%"),
                ),
            )
            ->orderByDesc('is_featured')
            ->orderByRaw('published_at IS NULL, published_at DESC')
            ->paginate(15);
    }

    /**
     * @return array{total: int, published: int, drafts: int}
     */
    #[Computed]
    public function counts(): array
    {
        return [
            'total' => Post::query()->count(),
            'published' => Post::query()->published()->count(),
            'drafts' => Post::query()->whereNull('published_at')->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function areas(): Collection
    {
        return Area::query()->orderBy('sort_order')->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function types(): array
    {
        return [
            Post::TYPE_NEWS => 'Noticia',
            Post::TYPE_NOTICE => 'Aviso',
            Post::TYPE_RECOGNITION => 'Reconocimiento',
        ];
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->can('create', Post::class), 403);

        $this->resetForm();
        $this->dispatch('open-modal', 'admin-content-form');
    }

    public function openEdit(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);

        abort_unless(auth()->user()->can('update', $post), 403);

        $this->editingPostId = $post->id;
        $this->title = $post->title;
        $this->highlight = (string) $post->highlight;
        $this->excerpt = (string) $post->excerpt;
        $this->body = (string) $post->body;
        $this->type = $post->type;
        $this->areaId = (string) $post->area_id;
        $this->isUrgent = $post->is_urgent;
        $this->isFeatured = $post->is_featured;
        $this->publishedAt = $post->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->publishNow = false;
        $this->resetValidation();
        $this->dispatch('open-modal', 'admin-content-form');
    }

    public function closeForm(): void
    {
        $this->dispatch('close-modal', 'admin-content-form');
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('create', Post::class), 403);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'highlight' => ['nullable', 'string', 'max:100'],
            'excerpt' => ['nullable', 'string', 'max:280'],
            'body' => ['required', 'string'],
            'type' => ['required', 'in:'.Post::TYPE_NEWS.','.Post::TYPE_NOTICE.','.Post::TYPE_RECOGNITION],
            'areaId' => ['required', 'exists:'.Area::class.',id'],
            'isUrgent' => ['boolean'],
            'isFeatured' => ['boolean'],
            'publishedAt' => ['nullable', 'string', 'date_format:Y-m-d\TH:i'],
            'publishNow' => ['boolean'],
        ], [], [
            'title' => 'título',
            'highlight' => 'resaltado',
            'excerpt' => 'resumen',
            'body' => 'contenido',
            'type' => 'tipo',
            'areaId' => 'área',
            'publishedAt' => 'fecha de publicación',
        ]);

        $user = auth()->user();

        // Los editores solo publican en su propia área (o conservan la que ya
        // tenía el comunicado que editan); no pueden moverlo a un área ajena.
        $currentAreaId = $this->editingPostId !== null
            ? Post::query()->whereKey($this->editingPostId)->value('area_id')
            : null;

        if (! $user->isAdmin()
            && (int) $validated['areaId'] !== (int) $user->area_id
            && (int) $validated['areaId'] !== (int) $currentAreaId) {
            $this->addError('areaId', 'Solo puedes publicar comunicados en tu área.');

            return;
        }

        $publishedAt = null;

        if ($validated['publishNow']) {
            $publishedAt = now();
        } elseif ($validated['publishedAt'] !== null && $validated['publishedAt'] !== '') {
            $publishedAt = Carbon::parse($validated['publishedAt']);
        }

        if ($this->editingPostId !== null) {
            $post = Post::query()->findOrFail($this->editingPostId);

            abort_unless(auth()->user()->can('update', $post), 403);

            $post->update($this->postAttributes($validated, $publishedAt));

            $message = 'Comunicado actualizado.';
        } else {
            Post::query()->create($this->postAttributes($validated, $publishedAt) + [
                'user_id' => auth()->id(),
            ]);

            $message = 'Comunicado creado.';
        }

        unset($this->posts, $this->counts);
        $this->closeForm();

        $this->status = $message;
    }

    public function toggleFeatured(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);

        abort_unless(auth()->user()->can('update', $post), 403);

        $post->update(['is_featured' => ! $post->is_featured]);

        unset($this->posts);

        $this->status = $post->is_featured
            ? "“{$post->title}” quedó fijado arriba del feed."
            : "“{$post->title}” dejó de estar fijado.";
    }

    public function confirmDelete(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);

        abort_unless(auth()->user()->can('delete', $post), 403);

        $this->deletingPostId = $postId;
        $this->dispatch('open-modal', 'admin-content-delete');
    }

    public function cancelDelete(): void
    {
        $this->dispatch('close-modal', 'admin-content-delete');
        $this->deletingPostId = null;
    }

    public function deletePost(): void
    {
        if ($this->deletingPostId === null) {
            return;
        }

        $post = Post::query()->findOrFail($this->deletingPostId);

        abort_unless(auth()->user()->can('delete', $post), 403);

        $title = $post->title;

        $post->delete();

        unset($this->posts, $this->counts);
        $this->deletingPostId = null;
        $this->dispatch('close-modal', 'admin-content-delete');

        $this->status = "“{$title}” fue eliminado.";
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    private function resetForm(): void
    {
        $this->formOpen = false;
        $this->editingPostId = null;
        $this->title = '';
        $this->highlight = '';
        $this->excerpt = '';
        $this->body = '';
        $this->type = Post::TYPE_NEWS;
        $this->areaId = '';
        $this->isUrgent = false;
        $this->isFeatured = false;
        $this->publishedAt = '';
        $this->publishNow = false;
        $this->resetValidation();
    }

    /**
     * @param  array<string, string|bool>  $validated
     * @return array<string, string|bool|null>
     */
    private function postAttributes(array $validated, ?Carbon $publishedAt): array
    {
        return [
            'title' => $validated['title'],
            'highlight' => $validated['highlight'] !== '' ? $validated['highlight'] : null,
            'excerpt' => $validated['excerpt'] !== '' ? $validated['excerpt'] : Str::limit($validated['body'], 280),
            'body' => $validated['body'],
            'type' => $validated['type'],
            'area_id' => $validated['areaId'],
            'is_urgent' => $validated['isUrgent'],
            'is_featured' => $validated['isFeatured'],
            'published_at' => $publishedAt,
        ];
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Administración</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Comunicados</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Escribe, programa, fija y retira las noticias, avisos y reconocimientos del feed.
            </p>
        </div>

        <div class="flex flex-wrap items-end justify-between gap-4">
            <dl class="flex gap-5 text-right">
                <div>
                    <dt class="text-3xs tracking-label text-ink-muted uppercase">Total</dt>
                    <dd class="font-condensed text-2xl font-black tabular-nums">{{ $this->counts['total'] }}</dd>
                </div>
                <div>
                    <dt class="text-3xs tracking-label text-ink-muted uppercase">Publicados</dt>
                    <dd class="font-condensed text-2xl font-black tabular-nums">{{ $this->counts['published'] }}</dd>
                </div>
                <div>
                    <dt class="text-3xs tracking-label text-ink-muted uppercase">Borradores</dt>
                    <dd class="font-condensed text-2xl font-black tabular-nums">{{ $this->counts['drafts'] }}</dd>
                </div>
            </dl>

            <button
                type="button"
                wire:click="openCreate"
                class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Nuevo comunicado
            </button>
        </div>
    </header>

    @if ($status !== '')
        <p class="mt-4 rounded-md border border-success/40 bg-success-soft/40 px-4 py-2.5 text-sm font-medium text-success" role="status">
            {{ $status }}
        </p>
    @endif

    <div class="mt-6 flex items-center gap-3">
        <label class="relative flex-1">
            <span class="sr-only">Buscar comunicados</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" />
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por título o contenido"
                class="w-full rounded-md border-line bg-canvas py-2.5 pl-9 pr-3 text-sm placeholder:text-ink-disabled focus:border-brand focus:ring-brand"
            >
        </label>

        @if ($search !== '')
            <button
                type="button"
                wire:click="clearSearch"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-3 text-xs font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
            >
                <x-icon name="x" class="h-4 w-4" />
                Limpiar
            </button>
        @endif
    </div>

    <div class="mt-6 hidden grid-cols-12 gap-4 border-b border-line pb-2 text-3xs font-semibold tracking-label text-ink-muted uppercase md:grid">
        <span class="col-span-5">Comunicado</span>
        <span class="col-span-3">Autor</span>
        <span class="col-span-2">Estado</span>
        <span class="col-span-2 text-right">Acciones</span>
    </div>

    <ul class="divide-y divide-line">
        @forelse ($this->posts as $post)
            <li class="py-4" wire:key="content-{{ $post->id }}">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-center">
                    <div class="col-span-5 min-w-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="text-3xs font-bold tracking-label text-accent-strong uppercase">{{ $post->typeLabel() }}</span>

                            @if ($post->is_featured)
                                <span class="inline-flex items-center gap-1 rounded-sm border border-accent/30 bg-accent-soft px-1.5 py-0.5 text-3xs font-semibold tracking-label text-accent-strong uppercase">
                                    <x-icon name="star" class="h-3 w-3" />
                                    Fijada
                                </span>
                            @endif

                            @if ($post->is_urgent)
                                <span class="inline-flex items-center gap-1 rounded-sm border border-danger/30 bg-danger-soft px-1.5 py-0.5 text-3xs font-semibold tracking-label text-danger uppercase">Urgente</span>
                            @endif
                        </div>

                        <a href="{{ route('feed.show', $post) }}" wire:navigate class="mt-1 block truncate text-sm font-semibold hover:text-accent-strong">
                            {{ $post->title }}
                        </a>

                        @if ($post->area)
                            <p class="mt-0.5 truncate text-xs text-ink-muted">{{ $post->area->name }}</p>
                        @endif
                    </div>

                    <div class="col-span-3 flex items-center gap-2">
                        <x-avatar :user="$post->author" size="xs" />
                        <p class="truncate text-xs text-ink-secondary">{{ $post->author?->name ?? 'Sin autor' }}</p>
                    </div>

                    <div class="col-span-2">
                        <span @class([
                            'inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label uppercase',
                            'text-success' => $post->isPublished(),
                            'text-warning' => $post->published_at !== null && ! $post->isPublished(),
                            'text-ink-muted' => $post->published_at === null,
                        ])>
                            <span @class([
                                'h-1.5 w-1.5 rounded-full',
                                'bg-success' => $post->isPublished(),
                                'bg-warning' => $post->published_at !== null && ! $post->isPublished(),
                                'bg-line-strong' => $post->published_at === null,
                            ])></span>
                            {{ $post->published_at === null ? 'Borrador' : ($post->isPublished() ? 'Publicado' : 'Programado') }}
                        </span>
                    </div>

                    <div class="col-span-2 flex items-center justify-between gap-2 md:justify-end">
                        <button
                            type="button"
                            wire:click="toggleFeatured({{ $post->id }})"
                            @disabled(! auth()->user()->can('update', $post))
                            class="inline-flex min-h-9 items-center rounded-md border border-line px-2.5 text-3xs font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            {{ $post->is_featured ? 'Desfijar' : 'Fijar' }}
                        </button>

                        <button
                            type="button"
                            wire:click="openEdit({{ $post->id }})"
                            @disabled(! auth()->user()->can('update', $post))
                            class="inline-flex min-h-9 items-center rounded-md border border-line px-2.5 text-3xs font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            Editar
                        </button>

                        <button
                            type="button"
                            wire:click="confirmDelete({{ $post->id }})"
                            @disabled(! auth()->user()->can('delete', $post))
                            class="inline-flex min-h-9 items-center rounded-md border border-line px-2.5 text-3xs font-medium text-danger transition duration-fast ease-signal hover:bg-danger-soft disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <x-icon name="trash" class="h-3.5 w-3.5" />
                            <span class="sr-only">Eliminar comunicado {{ $post->title }}</span>
                        </button>
                    </div>
                </div>
            </li>
        @empty
            <li class="py-14 text-center">
                <p class="font-expanded text-xl font-bold">Aún no hay comunicados{{ $search !== '' ? ' con ese filtro' : '' }}.</p>
                @if ($search !== '')
                    <button type="button" wire:click="clearSearch" class="mt-2 text-sm font-semibold text-accent-strong">
                        Ver todos los comunicados
                    </button>
                @endif
            </li>
        @endforelse
    </ul>

    <div class="mt-6">
        {{ $this->posts->links() }}
    </div>

    <x-modal name="admin-content-form" max-width="2xl" focusable>
        <form wire:submit="save">
            <header class="mb-6">
                <h2 class="font-expanded text-xl font-bold">{{ $editingPostId !== null ? 'Editar comunicado' : 'Nuevo comunicado' }}</h2>
                <p class="mt-1 text-sm text-ink-muted">Deja la fecha vacía y sin publicar ahora para guardarlo como borrador.</p>
            </header>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="contentTitle" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Título</label>
                    <input id="contentTitle" type="text" wire:model="title" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" required>
                    <x-input-error :messages="$errors->get('title')"/>
                </div>

                <div>
                    <label for="contentType" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Tipo</label>
                    <select id="contentType" wire:model="type" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand">
                        @foreach ($this->types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('type')"/>
                </div>

                <div>
                    <label for="contentAreaId" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Área</label>
                    <select id="contentAreaId" wire:model="areaId" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" required>
                        <option value="">Elegir área</option>
                        @foreach ($this->areas as $areaOption)
                            <option value="{{ $areaOption->id }}">{{ $areaOption->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('areaId')"/>
                </div>

                <div class="sm:col-span-2">
                    <label for="contentHighlight" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Palabra destacada en el título <span class="normal-case tracking-normal">(opcional)</span></label>
                    <input id="contentHighlight" type="text" wire:model="highlight" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand">
                    <x-input-error :messages="$errors->get('highlight')"/>
                </div>

                <div class="sm:col-span-2">
                    <label for="contentExcerpt" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Resumen <span class="normal-case tracking-normal">(máx. 280, opcional)</span></label>
                    <textarea id="contentExcerpt" wire:model="excerpt" rows="2" maxlength="280" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand"></textarea>
                    <x-input-error :messages="$errors->get('excerpt')"/>
                </div>

                <div class="sm:col-span-2">
                    <label for="contentBody" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Contenido</label>
                    <textarea id="contentBody" wire:model="body" rows="8" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" required></textarea>
                    <x-input-error :messages="$errors->get('body')"/>
                </div>

                <div>
                    <label for="contentPublishedAt" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Fecha de publicación</label>
                    <input id="contentPublishedAt" type="datetime-local" wire:model="publishedAt" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand">
                    <x-input-error :messages="$errors->get('publishedAt')"/>
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="publishNow" class="h-4 w-4 rounded border-line accent-brand">
                        Publicar ahora
                    </label>
                </div>

                <div class="flex flex-wrap gap-6 sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="isUrgent" class="h-4 w-4 rounded border-line accent-danger">
                        Marcar como urgente
                    </label>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="isFeatured" class="h-4 w-4 rounded border-line accent-accent">
                        Fijar en el feed
                    </label>
                </div>
            </div>

            <footer class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    wire:click="closeForm"
                    class="inline-flex min-h-10 items-center rounded-md border border-line px-4 text-sm font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="inline-flex min-h-10 items-center rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                    wire:loading.attr="disabled"
                >
                    {{ $editingPostId !== null ? 'Guardar cambios' : 'Crear comunicado' }}
                </button>
            </footer>
        </form>
    </x-modal>

    <x-modal name="admin-content-delete" max-width="sm">
        <h2 class="font-expanded text-xl font-bold">Eliminar comunicado</h2>

        <p class="mt-2 text-sm text-ink-muted">
            El comunicado desaparecerá del feed y de su página. Esta acción no se puede deshacer.
        </p>

        <footer class="mt-6 flex justify-end gap-2">
            <button
                type="button"
                wire:click="cancelDelete"
                class="inline-flex min-h-10 items-center rounded-md border border-line px-4 text-sm font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
            >
                Cancelar
            </button>

            <button
                type="button"
                wire:click="deletePost"
                class="inline-flex min-h-10 items-center rounded-md bg-danger px-4 text-sm font-semibold text-white transition duration-fast ease-signal hover:bg-danger/80"
                wire:loading.attr="disabled"
            >
                Eliminar definitivamente
            </button>
        </footer>
    </x-modal>
</div>
