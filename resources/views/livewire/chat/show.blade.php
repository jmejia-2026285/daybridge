<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-line pb-5">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('chat.index') }}"
                wire:navigate
                class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                aria-label="Volver a conversaciones"
            >
                <x-icon name="chevron-left" class="h-5 w-5" />
            </a>

            <div class="flex items-center gap-3">
                @if ($conversation->is_group)
                    <div class="w-12 h-12 rounded-full bg-brand/10 flex items-center justify-center">
                        <x-icon name="users" class="h-6 w-6 text-brand" />
                    </div>
                @else
                    <x-avatar :user="$conversation->otherParticipants(auth()->user())->first()" size="xl" />
                @endif

                <div>
                    <p class="font-expanded text-lg font-bold">{{ $conversation->getDisplayName(auth()->user()) }}</p>
                    @if ($conversation->is_group)
                        <p class="text-xs text-ink-muted">
                            {{ $conversation->participants()->count() }} participantes
                        </p>
                    @else
                        <p class="text-xs text-ink-muted">
                            {{ $conversation->otherParticipants(auth()->user())->first()?->job_title ?? 'Sin puesto' }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if ($conversation->is_group)
                <button
                    type="button"
                    wire:click="openGroupSettings"
                    class="inline-flex min-h-10 items-center rounded-md border border-line px-3 text-sm font-medium text-ink transition duration-fast ease-signal hover:bg-sunken"
                >
                    <x-icon name="settings" class="h-4 w-4" />
                    Grupo
                </button>
            @endif
        </div>
    </header>

    <div class="mt-6 grid gap-6 lg:grid-cols-12 lg:gap-8">
        <div class="lg:col-span-8">
            @include('livewire.chat.partials.thread', [
                'conversation' => $conversation,
                'messages' => $this->chatMessages,
                'message' => $this->message,
                'replyingToId' => $this->replyingToId,
            ])
        </div>

        <aside class="lg:col-span-4">
            <div class="border border-line bg-surface lg:sticky lg:top-20">
                <header class="border-b border-line px-5 py-4">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Info</h2>
                </header>

                <div class="divide-y divide-line">
                    @if (! $conversation->is_group)
                        @php $other = $conversation->otherParticipants(auth()->user())->first(); @endphp
                        @if ($other)
                            <div class="px-5 py-4 flex items-center gap-3">
                                <x-avatar :user="$other" size="lg" />
                                <div class="min-w-0">
                                    <p class="font-semibold truncate">{{ $other->name }}</p>
                                    <p class="text-xs text-ink-muted">{{ $other->job_title ?? 'Sin puesto' }}</p>
                                    <p class="text-xs text-ink-muted">{{ $other->area?->name }}</p>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="px-5 py-4">
                            <p class="text-xs text-ink-muted uppercase tracking-label">Participantes</p>
                            <ul class="mt-2 space-y-2">
                                @foreach ($conversation->participants as $participant)
                                    <li class="flex items-center gap-2">
                                        <x-avatar :user="$participant" size="sm" />
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium truncate">{{ $participant->name }}</p>
                                            <p class="text-xs text-ink-muted">{{ $participant->job_title ?? $participant->area?->name }}</p>
                                        </div>
                                        @if ($participant->pivot->is_admin)
                                            <span class="ml-auto inline-flex items-center gap-1 rounded-sm border border-accent/30 bg-accent-soft px-1.5 py-0.5 text-3xs font-semibold tracking-label text-accent-strong uppercase">
                                                <x-icon name="star" class="h-3 w-3" />
                                                Admin
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                @if (! $conversation->is_group)
                    <div class="p-5 border-t border-line">
                        <a
                            href="{{ route('directory.show', $conversation->otherParticipants(auth()->user())->first()) }}"
                            wire:navigate
                            class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-line px-4 py-2 text-sm font-medium text-ink transition duration-fast ease-signal hover:bg-sunken"
                        >
                            <x-icon name="user" class="h-4 w-4" />
                            Ver perfil
                        </a>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>
