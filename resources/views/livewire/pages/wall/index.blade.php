<?php

use App\Enums\WallReactionType;
use App\Models\Notification;
use App\Models\User;
use App\Models\WallComment;
use App\Models\WallPost;
use App\Models\WallReaction;
use App\Support\ActionThrottle;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Muro · Daybridge')] class extends Component
{
    public string $body = '';

    public ?int $editingId = null;

    /** @var array<int, string> */
    public array $commentDrafts = [];

    public string $statusMessage = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', WallPost::class), 403);
    }

    #[Computed]
    public function posts(): Collection
    {
        return WallPost::query()
            ->with([
                'author.area',
                'reactions.user.area',
                'comments.author.area',
            ])
            ->pinnedFirst()
            ->take(30)
            ->get();
    }

    /**
     * @return array{posts: int, reactions: int, comments: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'posts' => WallPost::query()->count(),
            'reactions' => WallReaction::query()->count(),
            'comments' => WallComment::query()->count(),
        ];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'body' => ['required', 'string', 'max:1000'],
        ], [], [
            'body' => 'mensaje',
        ]);

        ActionThrottle::hit('wall-post', 10, errorField: 'body');

        if ($this->editingId !== null) {
            $post = WallPost::query()->findOrFail($this->editingId);

            abort_unless(auth()->user()->can('update', $post), 403);

            $post->update(['body' => $validated['body']]);
            $message = 'Publicación actualizada.';
        } else {
            abort_unless(auth()->user()->can('create', WallPost::class), 403);

            WallPost::query()->create([
                'user_id' => auth()->id(),
                'body' => $validated['body'],
            ]);

            $message = 'Publicación compartida en el muro.';
        }

        $this->resetForm();
        $this->statusMessage = $message;
        $this->forgetWall();
    }

    public function edit(int $postId): void
    {
        $post = WallPost::query()->findOrFail($postId);

        abort_unless(auth()->user()->can('update', $post), 403);

        $this->editingId = $post->id;
        $this->body = $post->body;
        $this->statusMessage = '';
        $this->resetValidation();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->body = '';
        $this->statusMessage = '';
        $this->resetValidation();
    }

    public function delete(int $postId): void
    {
        $post = WallPost::query()->findOrFail($postId);

        abort_unless(auth()->user()->can('delete', $post), 403);

        $post->delete();

        if ($this->editingId === $postId) {
            $this->resetForm();
        }

        $this->statusMessage = 'Publicación eliminada.';
        $this->forgetWall();
    }

    public function togglePin(int $postId): void
    {
        $post = WallPost::query()->findOrFail($postId);

        abort_unless(auth()->user()->can('pin', $post), 403);

        $post->update(['is_pinned' => ! $post->is_pinned]);

        $this->statusMessage = $post->is_pinned
            ? 'Publicación fijada en la parte superior.'
            : 'Publicación desfijada.';

        $this->forgetWall();
    }

    public function react(int $postId, string $type): void
    {
        $validated = validator(
            ['type' => $type],
            ['type' => ['required', Rule::enum(WallReactionType::class)]],
        )->validate();

        $post = WallPost::query()->findOrFail($postId);

        abort_unless(auth()->user()->can('view', $post), 403);

        ActionThrottle::hit('wall-react', 40, errorField: 'statusMessage');

        $existing = WallReaction::query()
            ->where('wall_post_id', $post->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existing !== null && $existing->type->value === $validated['type']) {
            $existing->delete();
            $this->statusMessage = 'Reacción retirada.';
        } else {
            $reaction = WallReaction::query()->updateOrCreate(
                [
                    'wall_post_id' => $post->id,
                    'user_id' => auth()->id(),
                ],
                [
                    'type' => $validated['type'],
                ],
            );

            $this->statusMessage = 'Reacción guardada.';

            // Una sola notificación por persona y publicación: quitar y volver a
            // reaccionar en bucle no debe inundar la bandeja del autor.
            $alreadyNotified = Notification::query()
                ->where('type', 'wall_reaction')
                ->where('subject_type', WallPost::class)
                ->where('subject_id', $post->id)
                ->where('actor_id', auth()->id())
                ->exists();

            if ($reaction->wasRecentlyCreated && ! $alreadyNotified && $post->user_id !== auth()->id()) {
                Notification::query()->create([
                    'type' => 'wall_reaction',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $post->user_id,
                    'subject_type' => WallPost::class,
                    'subject_id' => $post->id,
                    'actor_id' => auth()->id(),
                    'data' => ['message' => 'Reaccionó a tu publicación en el muro.'],
                ]);
            }
        }

        $this->forgetWall();
    }

    public function addComment(int $postId): void
    {
        $validated = $this->validate([
            'commentDrafts.'.$postId => ['required', 'string', 'max:500'],
        ], [], [
            'commentDrafts.'.$postId => 'comentario',
        ]);

        $post = WallPost::query()->findOrFail($postId);

        abort_unless(auth()->user()->can('create', WallComment::class), 403);

        ActionThrottle::hit('wall-comment', 15, errorField: 'commentDrafts.'.$postId);

        WallComment::query()->create([
            'wall_post_id' => $post->id,
            'user_id' => auth()->id(),
            'body' => $validated['commentDrafts'][$postId],
        ]);

        if ($post->user_id !== auth()->id()) {
            Notification::query()->create([
                'type' => 'wall_comment',
                'notifiable_type' => User::class,
                'notifiable_id' => $post->user_id,
                'subject_type' => WallPost::class,
                'subject_id' => $post->id,
                'actor_id' => auth()->id(),
                'data' => ['message' => 'Comentó en tu publicación del muro.'],
            ]);
        }

        unset($this->commentDrafts[$postId]);
        $this->statusMessage = 'Comentario publicado.';
        $this->forgetWall();
    }

    public function deleteComment(int $commentId): void
    {
        $comment = WallComment::query()->findOrFail($commentId);

        abort_unless(auth()->user()->can('delete', $comment), 403);

        $comment->delete();

        $this->statusMessage = 'Comentario eliminado.';
        $this->forgetWall();
    }

    private function forgetWall(): void
    {
        unset($this->posts, $this->stats);
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Comunidad</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Muro</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Lo que el equipo comparte hoy: reconocimientos, avisos breves y conversaciones del día a día.
            </p>
        </div>

        <a
            href="{{ route('feed.index') }}"
            wire:navigate
            class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3.5 text-sm font-medium text-ink transition duration-fast ease-signal hover:bg-sunken"
        >
            <x-icon name="megaphone" class="h-4 w-4" />
            Comunicados
        </a>
    </header>

    @if ($statusMessage !== '')
        <p class="mt-4 border-l-2 border-brand bg-brand-soft px-3 py-2 text-sm text-brand-strong" role="status">
            {{ $statusMessage }}
        </p>
    @endif

    <div class="mt-6 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="lg:col-span-8">
            <form wire:submit="save" class="group relative rounded-2xl border border-line bg-surface p-5 transition duration-base ease-signal hover:border-line-strong hover:shadow-sm">
                <div class="flex items-start gap-3">
                    <x-avatar :user="auth()->user()" size="sm" />

                    <div class="min-w-0 flex-1 pt-0.5">
                        <label for="wall-body" class="sr-only">Mensaje para el muro</label>
                        <textarea
                            id="wall-body"
                            wire:model="body"
                            rows="3"
                            maxlength="1000"
                            placeholder="¿Qué quieres compartir con tu equipo?"
                            class="w-full resize-y rounded-md border-0 bg-transparent py-2.5 text-sm text-ink placeholder:text-ink-disabled placeholder:font-medium focus:outline-none focus:ring-0"
                        ></textarea>

                        <x-input-error :messages="$errors->get('body')" />

                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                            <p class="font-condensed text-3xs tabular-nums text-ink-muted">
                                {{ mb_strlen($body) }}/1000
                            </p>

                            <div class="flex items-center gap-2">
                                @if ($editingId)
                                    <button
                                        type="button"
                                        wire:click="resetForm"
                                        class="inline-flex min-h-10 items-center rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                                    >
                                        Cancelar
                                    </button>
                                @endif

                                <button
                                    type="submit"
                                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                                >
                                    <x-icon name="megaphone" class="h-4 w-4" />
                                    {{ $editingId ? 'Guardar cambios' : 'Publicar' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="mt-6 space-y-4">
                @forelse ($this->posts as $post)
                    @php
                        $myReaction = $post->reactionBy(auth()->user());
                        $reactionCounts = $post->reactionCounts();
                    @endphp

                    <article class="border border-line bg-surface" wire:key="wall-post-{{ $post->id }}">
                        <div class="flex items-start gap-3 border-b border-line px-5 py-4">
                            <x-avatar :user="$post->author" size="md" />

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <p class="text-sm font-semibold">{{ $post->author->name }}</p>

                                    @if ($post->author->area)
                                        <x-area-label :area="$post->author->area" />
                                    @endif

                                    <span class="h-1 w-1 rounded-full bg-line-strong"></span>
                                    <p class="font-condensed text-3xs tabular-nums text-ink-muted">
                                        {{ $post->created_at->locale('es')->diffForHumans() }}
                                    </p>

                                    @if ($post->is_pinned)
                                        <span class="inline-flex items-center gap-1 rounded-sm border border-accent/30 bg-accent-soft px-1.5 py-0.5 text-3xs font-semibold tracking-label text-accent-strong uppercase">
                                            <x-icon name="star" class="h-3 w-3" />
                                            Fijada
                                        </span>
                                    @endif
                                </div>

                                @if ($post->author->job_title)
                                    <p class="mt-0.5 text-xs text-ink-muted">{{ $post->author->job_title }}</p>
                                @endif
                            </div>

                            @if (auth()->user()->can('update', $post) || auth()->user()->can('delete', $post) || auth()->user()->can('pin', $post))
                                <div class="flex shrink-0 items-center gap-1">
                                    @can('pin', $post)
                                        <button
                                            type="button"
                                            wire:click="togglePin({{ $post->id }})"
                                            aria-label="{{ $post->is_pinned ? 'Desfijar publicación' : 'Fijar publicación' }}"
                                            title="{{ $post->is_pinned ? 'Desfijar' : 'Fijar' }}"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                                        >
                                            <x-icon name="star" class="h-4 w-4" />
                                        </button>
                                    @endcan

                                    @can('update', $post)
                                        <button
                                            type="button"
                                            wire:click="edit({{ $post->id }})"
                                            aria-label="Editar publicación"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink transition duration-fast ease-signal hover:bg-sunken"
                                        >
                                            <x-icon name="edit" class="h-4 w-4" />
                                        </button>
                                    @endcan

                                    @can('delete', $post)
                                        <button
                                            type="button"
                                            wire:click="delete({{ $post->id }})"
                                            wire:confirm="¿Eliminar esta publicación del muro?"
                                            aria-label="Eliminar publicación"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-danger/30 text-danger transition duration-fast ease-signal hover:bg-danger-soft"
                                        >
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    @endcan
                                </div>
                            @endif
                        </div>

                        <div class="px-5 py-4">
                            <p class="whitespace-pre-line text-sm leading-relaxed text-ink-secondary">{{ $post->body }}</p>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                @foreach (WallReactionType::cases() as $type)
                                    <button
                                        type="button"
                                        wire:click="react({{ $post->id }}, '{{ $type->value }}')"
                                        @class([
                                            'inline-flex min-h-9 items-center gap-1.5 rounded-md border px-2.5 text-xs font-medium transition duration-fast ease-signal',
                                            'border-accent bg-accent-soft text-accent-strong' => $myReaction?->type === $type,
                                            'border-line text-ink-muted hover:border-line-strong hover:bg-sunken hover:text-ink' => $myReaction?->type !== $type,
                                        ])
                                        aria-pressed="{{ $myReaction?->type === $type ? 'true' : 'false' }}"
                                    >
                                        <x-icon :name="$type->icon()" class="h-4 w-4" />
                                        {{ $type->label() }}
                                        <span class="font-condensed tabular-nums">{{ $reactionCounts[$type->value] }}</span>
                                    </button>
                                @endforeach

                                <span class="ml-auto inline-flex items-center gap-1.5 text-3xs tracking-label text-ink-muted uppercase">
                                    <x-icon name="chat" class="h-3.5 w-3.5" />
                                    {{ $post->comments->count() }} {{ $post->comments->count() === 1 ? 'comentario' : 'comentarios' }}
                                </span>
                            </div>
                        </div>

                        @if ($post->comments->isNotEmpty())
                            <div class="space-y-3 border-t border-line bg-canvas px-5 py-4">
                                @foreach ($post->comments as $comment)
                                    <div class="flex gap-2.5" wire:key="wall-comment-{{ $comment->id }}">
                                        <x-avatar :user="$comment->author" size="2xs" />

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                                <p class="text-xs font-semibold">{{ $comment->author->name }}</p>
                                                <p class="font-condensed text-3xs tabular-nums text-ink-muted">
                                                    {{ $comment->created_at->locale('es')->diffForHumans() }}
                                                </p>
                                            </div>

                                            <p class="mt-0.5 whitespace-pre-line text-sm leading-relaxed text-ink-secondary">{{ $comment->body }}</p>
                                        </div>

                                        @can('delete', $comment)
                                            <button
                                                type="button"
                                                wire:click="deleteComment({{ $comment->id }})"
                                                wire:confirm="¿Eliminar este comentario?"
                                                aria-label="Eliminar comentario"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-ink-muted transition duration-fast ease-signal hover:bg-danger-soft hover:text-danger"
                                            >
                                                <x-icon name="trash" class="h-3.5 w-3.5" />
                                            </button>
                                        @endcan
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="flex flex-col items-start gap-4 border border-line bg-surface p-6 sm:flex-row sm:items-center">
                        <img src="{{ asset('media/rem/rem_feliz.png') }}" alt="" class="h-24 w-auto">
                        <div>
                            <p class="font-expanded text-xl font-bold">El muro está en silencio.</p>
                            <p class="mt-1 text-sm text-ink-muted">Sé la primera persona en compartir algo con el equipo.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="lg:col-span-4">
            <div class="border border-line bg-surface lg:sticky lg:top-20">
                <header class="border-b border-line px-5 py-4">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">En el muro</h2>
                </header>

                <dl class="divide-y divide-line">
                    <div class="flex items-baseline justify-between px-5 py-4">
                        <dt class="text-xs text-ink-muted">Publicaciones</dt>
                        <dd class="font-condensed text-3xl font-black tabular-nums">{{ $this->stats['posts'] }}</dd>
                    </div>

                    <div class="flex items-baseline justify-between px-5 py-4">
                        <dt class="text-xs text-ink-muted">Reacciones</dt>
                        <dd class="font-condensed text-3xl font-black tabular-nums">{{ $this->stats['reactions'] }}</dd>
                    </div>

                    <div class="flex items-baseline justify-between px-5 py-4">
                        <dt class="text-xs text-ink-muted">Comentarios</dt>
                        <dd class="font-condensed text-3xl font-black tabular-nums">{{ $this->stats['comments'] }}</dd>
                    </div>
                </dl>

                <section class="border-t border-line px-5 py-4">
                    <h3 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Cómo participar</h3>
                    <ul class="mt-3 space-y-2 text-xs leading-relaxed text-ink-muted">
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                            Comparte avisos breves y reconocimientos concretos.
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                            Responde con contexto, no solo con emojis.
                        </li>
                        <li class="flex gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-accent"></span>
                            Para temas delicados, usa un canal privado.
                        </li>
                    </ul>
                </section>
            </div>
        </aside>
    </div>
</div>