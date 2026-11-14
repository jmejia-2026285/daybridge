{{-- Hilo de conversación compartido: burbujas, acciones, composer.
     Consumido por livewire/chat/index (panel derecho) y livewire/chat/show (página completa).
     Variables: $conversation, $messages, $message (draft), $replyingToId;
     opcionales: $heightClass, $withHeader, $backAction, $framed. --}}
@php
    $heightClass = $heightClass ?? 'h-[calc(100vh-20rem)]';
    $framed = $framed ?? true;
    $draft = $message ?? '';
@endphp

<div class="{{ $framed ? 'border border-line rounded-2xl ' : '' }}bg-surface overflow-hidden flex flex-col {{ $heightClass }}">
    @if (! empty($withHeader))
        <div class="flex items-center gap-3 border-b border-line px-4 py-3">
            <button
                type="button"
                wire:click="{{ $backAction }}"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-line text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink lg:hidden"
                aria-label="Volver a la lista de conversaciones"
            >
                <x-icon name="chevron-left" class="h-4 w-4" />
            </button>

            @if ($conversation->is_group)
                <div class="w-10 h-10 shrink-0 rounded-full bg-brand/10 flex items-center justify-center">
                    <x-icon name="users" class="h-5 w-5 text-brand" />
                </div>
            @else
                <x-avatar :user="$conversation->otherParticipants(auth()->user())->first()" size="sm" />
            @endif

            <div class="min-w-0">
                <p class="truncate font-semibold text-sm">{{ $conversation->getDisplayName(auth()->user()) }}</p>
                <p class="truncate text-xs text-ink-muted">
                    @if ($conversation->is_group)
                        {{ $conversation->participants()->count() }} participantes
                    @else
                        {{ $conversation->otherParticipants(auth()->user())->first()?->job_title ?? 'Sin puesto' }}
                    @endif
                </p>
            </div>
        </div>
    @endif

    <div
        class="flex-1 min-h-0 overflow-y-auto p-6 space-y-4"
        x-data
        x-init="$el.scrollTop = $el.scrollHeight; window.addEventListener('message-sent', () => $el.scrollTop = $el.scrollHeight)"
        wire:poll.5s
    >
        @if ($messages->isEmpty())
            <div class="flex flex-col items-center justify-center h-full min-h-[300px] text-center text-ink-muted">
                <x-icon name="chat" class="h-12 w-12 mb-4" />
                <p class="font-expanded text-lg font-bold">No hay mensajes</p>
                <p class="mt-1 text-sm">Sé el primero en escribir</p>
            </div>
        @else
            @foreach ($messages->reverse() as $threadMessage)
                <div
                    class="flex gap-3 {{ $threadMessage->isFromCurrentUser() ? 'flex-row-reverse' : '' }}"
                    wire:key="message-{{ $threadMessage->id }}"
                >

                    @if (! $threadMessage->isFromCurrentUser())
                        <x-avatar :user="$threadMessage->sender" size="sm" />
                    @endif

                    <div class="flex flex-col max-w-[70%] gap-1 {{ $threadMessage->isFromCurrentUser() ? 'items-end' : 'items-start' }}">
                        @if ($quotedMessage = $threadMessage->replyTo())
                            <div class="px-3 py-1.5 rounded-lg border border-line/50 bg-sunken/50 text-xs text-ink-muted max-w-full">
                                <p class="font-semibold text-ink">{{ $quotedMessage->sender->name }}</p>
                                <p class="truncate">{{ $quotedMessage->body }}</p>
                            </div>
                        @endif

                        <div class="group relative px-4 py-2 rounded-2xl text-sm leading-relaxed
                            {{ $threadMessage->isFromCurrentUser()
                                ? 'bg-brand text-brand-foreground rounded-tr-sm'
                                : 'bg-sunken text-ink rounded-tl-sm' }}
                        ">
                            <p class="whitespace-pre-wrap">{{ $threadMessage->body }}</p>

                            <div class="absolute -top-8 right-0 hidden group-hover:flex gap-1">
                                @if (! $threadMessage->isFromCurrentUser())
                                    <button
                                        type="button"
                                        wire:click="startReply({{ $threadMessage->id }})"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-surface border border-line text-ink-muted hover:bg-sunken hover:text-ink text-xs"
                                        title="Responder"
                                    >
                                        <x-icon name="reply" class="h-3.5 w-3.5" />
                                    </button>
                                @endif

                                @if ($threadMessage->isFromCurrentUser())
                                    <button
                                        type="button"
                                        wire:click="deleteMessage({{ $threadMessage->id }})"
                                        wire:confirm="¿Eliminar este mensaje?"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-danger-soft border border-danger/30 text-danger hover:bg-danger hover:text-white text-xs"
                                        title="Eliminar"
                                    >
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </button>
                                @endif
                            </div>
                        </div>

                        <p class="font-condensed text-3xs tabular-nums text-ink-muted px-1">
                            {{ $threadMessage->created_at->locale('es')->diffForHumans() }}
                            @if ($threadMessage->read_at && $threadMessage->isFromCurrentUser())
                                <x-icon name="check-double" class="h-3.5 w-3.5 inline-block align-middle ml-1 text-accent" />
                            @endif
                        </p>
                    </div>

                    @if ($threadMessage->isFromCurrentUser())
                        <div class="w-8"></div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    @if ($replyingToId && $quotedMessage = $conversation->messages()->find($replyingToId))
        <div class="border-t border-line p-4 bg-sunken/30 flex items-center gap-3">
            <x-icon name="reply" class="h-5 w-5 text-accent" />
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-accent">Respondiendo a {{ $quotedMessage->sender->name }}</p>
                <p class="text-sm truncate text-ink-muted">{{ $quotedMessage->body }}</p>
            </div>
            <button wire:click="cancelReply" class="text-ink-muted hover:text-ink">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>
    @endif

    <div class="border-t border-line p-4">
        <form wire:submit="sendMessage" class="flex items-end gap-3">
            <label for="chat-message" class="sr-only">Escribe un mensaje</label>
            <textarea
                id="chat-message"
                wire:model="message"
                rows="1"
                maxlength="5000"
                placeholder="Escribe un mensaje..."
                class="flex-1 resize-none rounded-xl border-line bg-canvas px-4 py-3 text-sm placeholder:text-ink-disabled focus:border-accent focus:ring-accent min-h-[48px] max-h-48"
                @keydown.shift.enter.prevent
                @keydown.enter="if (!event.shiftKey) { event.preventDefault(); $wire.sendMessage(); }"
            ></textarea>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong disabled:opacity-50 disabled:cursor-not-allowed"
                aria-label="Enviar mensaje"
            >
                <x-icon name="send" class="h-5 w-5" />
            </button>
        </form>

        <p class="mt-2 text-center font-condensed text-3xs tabular-nums text-ink-muted">
            {{ mb_strlen($draft) }}/5000
        </p>
    </div>
</div>
