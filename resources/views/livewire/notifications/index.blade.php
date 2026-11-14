<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Comunicación</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Notificaciones</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Mantente al día con lo que sucede en tu organización.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <select
                wire:model="filter"
                class="rounded-md border border-line bg-canvas px-3 py-2 text-sm focus:border-accent focus:ring-accent"
            >
                <option value="all">Todas</option>
                <option value="unread">No leídas ({{ $this->unreadCount }})</option>
            </select>

            @if ($this->unreadCount > 0)
                <button
                    wire:click="markAllAsRead"
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                >
                    <x-icon name="check" class="h-4 w-4" />
                    Marcar todas como leídas
                </button>
            @endif
        </div>
    </header>

    @if ($this->notifications->isNotEmpty())
        <div class="mt-6 border border-line bg-surface rounded-2xl overflow-hidden divide-y divide-line">
            @foreach ($this->notifications as $notification)
                <div
                    class="flex gap-4 p-4 hover:bg-sunken transition-colors {{ ! $notification->isRead() ? 'bg-brand/5' : '' }}"
                    wire:key="notification-{{ $notification->id }}"
                    x-data="{ showActions: false }"
                    @mouseenter.hover="showActions = true"
                    @mouseleave.hover="showActions = false"
                >
                    <div class="relative shrink-0 w-10 h-10 rounded-full bg-brand/10 flex items-center justify-center">
                        <x-icon :name="$notification->getIcon()" class="h-5 w-5 text-brand" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-sm">{{ $notification->getLabel() }}</p>

                            @if (! $notification->isRead())
                                <button
                                    wire:click="markAsRead({{ $notification->id }})"
                                    class="shrink-0 inline-flex h-7 w-7 items-center justify-center rounded-md text-ink-muted hover:bg-sunken hover:text-ink"
                                    title="Marcar como leída"
                                >
                                    <x-icon name="check" class="h-3.5 w-3.5" />
                                </button>
                            @endif
                        </div>

                        <p class="mt-1 text-sm text-ink-secondary">{{ $notification->data['message'] ?? 'Nueva actividad en tu organización' }}</p>

                        <div class="mt-2 flex items-center gap-3">
                            <span class="font-condensed text-3xs tabular-nums text-ink-muted">
                                {{ $notification->created_at->locale('es')->diffForHumans() }}
                            </span>

                            @if ($notification->actor)
                                <span class="flex items-center gap-1 text-xs text-ink-muted">
                                    <x-icon name="user" class="h-3 w-3" />
                                    {{ $notification->actor->name }}
                                </span>
                            @endif

                            @if ($notification->getActionUrl())
                                <a
                                    href="{{ $notification->getActionUrl() }}"
                                    wire:navigate
                                    class="ml-auto inline-flex items-center gap-1 text-sm font-medium text-accent-strong hover:underline"
                                >
                                    Ver
                                    <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="px-4 py-3 border-t border-line">
                {{ $this->notifications->links() }}
            </div>
        </div>
    @else
        <div class="mt-6 border border-line bg-surface rounded-2xl p-8 text-center">
            <x-icon name="bell" class="h-12 w-12 mx-auto text-ink-muted" />
            <p class="mt-4 font-expanded text-lg font-bold">No hay notificaciones</p>
            <p class="mt-1 text-sm text-ink-muted">Cuando haya actividad nueva, aparecerá aquí</p>
        </div>
    @endif
</div>