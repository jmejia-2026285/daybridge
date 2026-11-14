<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Comunicación</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Nueva conversación</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Selecciona una persona para iniciar un chat directo
            </p>
        </div>

        <a
            href="{{ route('chat.index') }}"
            wire:navigate
            class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3.5 text-sm font-medium text-ink transition duration-fast ease-signal hover:bg-sunken"
        >
            <x-icon name="chevron-left" class="h-4 w-4" />
            Volver
        </a>
    </header>

    <div class="mt-6">
        <div class="border border-line bg-surface rounded-2xl overflow-hidden">
            <div class="border-b border-line px-4 py-3">
                <label for="create-chat-search" class="sr-only">Buscar personas</label>
                <input
                    id="create-chat-search"
                    type="text"
                    wire:model.debounce.300ms="search"
                    placeholder="Buscar por nombre..."
                    class="w-full rounded-md border-line bg-canvas px-3 py-2 text-sm placeholder:text-ink-disabled focus:border-accent focus:ring-accent"
                />
            </div>

            @if ($this->users->isNotEmpty())
                <div class="divide-y divide-line">
                    @foreach ($this->users as $user)
                        <button
                            wire:key="select-user-{{ $user->id }}"
                            wire:click="startConversation({{ $user->id }})"
                            wire:loading.attr="disabled"
                            class="flex w-full items-center gap-3 px-4 py-3 hover:bg-sunken transition-colors text-left apple-liquid-glass-light"
                        >
                            <x-avatar :user="$user" size="lg" />
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold truncate">{{ $user->name }}</p>
                                <p class="text-sm text-ink-muted">{{ $user->job_title ?? $user->area?->name ?? 'Sin área' }}</p>
                            </div>
                            <x-icon name="chevron-right" class="h-5 w-5 text-ink-muted" />
                        </button>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center">
                    <x-icon name="search" class="h-12 w-12 mx-auto text-ink-muted" />
                    <p class="mt-4 font-expanded text-lg font-bold">No se encontraron personas</p>
                    <p class="mt-1 text-sm text-ink-muted">Intenta con otro término de búsqueda</p>
                </div>
            @endif
        </div>
    </div>
</div>