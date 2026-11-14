<div wire:key="chat-index-root" class="mx-auto max-w-content px-4 pt-6 pb-4 sm:px-6 md:px-10 md:pt-10 md:pb-6">
    <header class="border-b border-line pb-5">
        <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Comunicación</p>
        <h1 class="mt-1 font-expanded text-2xl font-black tracking-display sm:text-3xl">Mensajes</h1>
    </header>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-12 lg:h-[calc(100vh-12.5rem)] border border-line bg-surface overflow-hidden rounded-2xl">
        {{-- Panel izquierdo: lista de conversaciones --}}
        <aside
            class="{{ $selectedConversationId ? 'hidden lg:flex' : 'flex' }}
                flex-col min-h-0 h-[calc(100vh-17rem)] min-h-[440px] lg:h-auto lg:min-h-0
                border-b lg:border-b-0 lg:border-r border-line lg:col-span-4"
        >
            <div class="flex items-center gap-2 border-b border-line p-3">
                <label for="chat-search" class="sr-only">Buscar conversaciones</label>
                <input
                    id="chat-search"
                    type="text"
                    wire:model.debounce.300ms="search"
                    placeholder="Buscar conversaciones..."
                    class="min-w-0 flex-1 rounded-md border-line bg-canvas px-3 py-2 text-sm placeholder:text-ink-disabled focus:border-accent focus:ring-accent"
                />

                <button
                    type="button"
                    wire:click="openNewChat"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-brand text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                    title="Nueva conversación"
                    aria-label="Nueva conversación"
                >
                    <x-icon name="plus" class="h-4 w-4" />
                </button>
            </div>

            @if ($search !== '')
                <div class="flex items-center gap-2 border-b border-line px-3 py-2">
                    <span class="text-3xs text-ink-muted">Resultados para: "{{ $search }}"</span>
                    <button
                        type="button"
                        wire:click="clearSearch"
                        class="text-3xs text-ink-muted underline hover:text-ink"
                    >
                        Limpiar
                    </button>
                </div>
            @endif

            @if ($this->conversations->isNotEmpty())
                <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-line">
                    @foreach ($this->conversations as $conversation)
                        <button
                            type="button"
                            wire:key="conversation-{{ $conversation->id }}"
                            wire:click="openConversation({{ $conversation->id }})"
                            @class([
                                'flex w-full gap-3 px-4 py-3 text-left transition duration-fast ease-signal',
                                'bg-brand/10' => $selectedConversationId === $conversation->id,
                                'hover:bg-sunken' => $selectedConversationId !== $conversation->id,
                            ])
                        >
                            <div class="relative shrink-0 w-11 h-11">
                                @if ($conversation->is_group)
                                    <div class="w-11 h-11 rounded-full bg-brand/10 flex items-center justify-center">
                                        <x-icon name="users" class="h-5 w-5 text-brand" />
                                    </div>
                                @else
                                    <x-avatar :user="$conversation->otherParticipants(auth()->user())->first()" size="md" />
                                @endif

                                @php
                                    $unread = $conversation->unreadCountFor(auth()->user());
                                @endphp

                                @if ($unread > 0)
                                    <span
                                        class="absolute -top-1 -right-1 min-w-5 px-1 h-5 rounded-full bg-accent text-2xs font-bold flex items-center justify-center text-white"
                                    >
                                        {{ $unread > 9 ? '9+' : $unread }}
                                    </span>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="truncate font-semibold text-sm">{{ $conversation->getDisplayName(auth()->user()) }}</p>
                                    @if ($conversation->latestMessage)
                                        <span class="shrink-0 font-condensed text-3xs tabular-nums text-ink-muted">
                                            {{ $conversation->latestMessage->created_at->locale('es')->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>

                                @if ($conversation->latestMessage)
                                    <p class="mt-0.5 truncate text-sm text-ink-muted">
                                        @if ($conversation->latestMessage->sender_id === auth()->id())
                                            <span class="font-medium">Tú: </span>
                                        @elseif ($conversation->is_group)
                                            <span class="font-medium">{{ $conversation->latestMessage->sender->name }}: </span>
                                        @endif
                                        <span>{{ $conversation->latestMessage->body }}</span>
                                    </p>
                                @else
                                    <p class="mt-0.5 truncate text-sm text-ink-muted">Sin mensajes aún</p>
                                @endif
                            </div>
                        </button>
                    @endforeach

                    <div class="px-4 py-3">
                        {{ $this->conversations->links() }}
                    </div>
                </div>
            @else
                <div class="flex-1 overflow-y-auto p-6 text-center">
                    <x-icon name="chat" class="h-10 w-10 mx-auto text-ink-muted" />
                    <p class="mt-3 font-expanded text-base font-bold">No hay conversaciones</p>
                    <p class="mt-1 text-sm text-ink-muted">
                        Inicia una con el botón + de arriba.
                    </p>
                </div>
            @endif
        </aside>

        {{-- Panel derecho: hilo de conversación en la misma página --}}
        <section
            class="{{ $selectedConversationId ? 'flex' : 'hidden lg:flex' }}
                flex-col min-h-0 h-[calc(100vh-17rem)] min-h-[440px] lg:h-auto lg:min-h-0
                lg:col-span-8"
        >
            @if ($selectedConversation = $this->selectedConversation)
                @include('livewire.chat.partials.thread', [
                    'conversation' => $selectedConversation,
                    'messages' => $this->chatMessages,
                    'message' => $this->message,
                    'replyingToId' => $this->replyingToId,
                    'withHeader' => true,
                    'backAction' => 'closeConversation',
                    'framed' => false,
                    'heightClass' => 'flex-1 min-h-0',
                ])
            @else
                <div class="flex-1 flex flex-col items-center justify-center p-8 text-center text-ink-muted">
                    <x-icon name="chat" class="h-12 w-12" />
                    <p class="mt-4 font-expanded text-lg font-bold text-ink">Selecciona una conversación</p>
                    <p class="mt-1 text-sm">
                        Elige un chat de la lista para verlo aquí mismo, como en WhatsApp Web.
                    </p>
                </div>
            @endif
        </section>
    </div>

    @if ($showNewChatModal)
        <div
            wire:key="new-chat-modal"
            class="fixed inset-0 z-50 flex items-start sm:items-center justify-center bg-black/60 backdrop-blur-sm p-4 pt-16 sm:pt-0"
        >
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-line bg-surface shadow-2xl">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="font-semibold">Iniciar nueva conversación</h2>
                    <button
                        type="button"
                        wire:click="closeNewChat"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-line text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                        aria-label="Cerrar"
                    >
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="max-h-[60vh] overflow-y-auto p-2" role="list" aria-label="Personas disponibles">
                    @forelse ($this->users as $user)
                        <button
                            wire:key="select-user-{{ $user->id }}"
                            wire:click="selectUser({{ $user->id }})"
                            type="button"
                            role="listitem"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition duration-fast ease-signal hover:bg-sunken"
                        >
                            <x-avatar :user="$user" size="sm" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium text-sm">{{ $user->name }}</span>
                                <span class="block truncate text-xs text-ink-muted">
                                    {{ $user->area?->name ?? $user->email }}
                                </span>
                            </span>
                        </button>
                    @empty
                        <p class="p-6 text-center text-sm text-ink-muted">No hay personas disponibles.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
