<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Daybridge' }}</title>

        <script>
            (() => {
                // Modo oscuro fijo para garantizar contraste con Liquid Glass
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            })();
        </script>

        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="theme-color" content="#0B1416">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Daybridge">
        <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
        <link rel="icon" href="{{ asset('icons/favicon.png') }}" type="image/png">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@75..125,400..900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        
        <style>
            .bg-apple-wallpaper {
                background-size: cover;
                background-position: center;
                background-attachment: fixed;
            }
            @php
                $customBackground = \App\Models\Setting::get(\App\Models\Setting::BACKGROUND_KEY);
            @endphp
            @if ($customBackground)
                .bg-apple-wallpaper { background-image: url('{{ asset($customBackground) }}'); }
            @else
                @media (min-width: 768px) {
                    .bg-apple-wallpaper { background-image: url('{{ asset('media/background/fondo-montanas-desktop.jpg') }}'); }
                }
                @media (max-width: 767px) {
                    .bg-apple-wallpaper { background-image: url('{{ asset('media/background/fondo-montanas-mobile.jpg') }}'); }
                }
            @endif
        </style>
    </head>
    {{-- isolate: sin contexto de apilamiento propio, el fondo del body se pintaba encima del velo (z-index -1) y el velo nunca se veía. --}}
    <body class="isolate min-h-screen font-sans text-ink antialiased bg-apple-wallpaper">
        <div class="pointer-events-none fixed inset-0 z-[-1] glass-overlay opacity-70" aria-hidden="true"></div>

        @php
            $currentUser = auth()->user();

            $allModules = collect(config('modules'))
                ->map(fn (array $module, string $key): array => ['key' => $key] + $module)
                ->reject(fn (array $module): bool => ($module['admin_only'] ?? false) && ! $currentUser->isAdmin())
                ->reject(fn (array $module): bool => ($module['it_desk_only'] ?? false) && ! ($currentUser->isAdmin() || $currentUser->isItDeskMember()));

            $modules = $allModules
                ->filter(fn (array $module): bool => ($module['enabled'] ?? false) && \Illuminate\Support\Facades\Route::has($module['route']))
                ->values();

            $primaryModules = $modules->where('primary', true)->values();
            $mobileModules = $primaryModules->where('mobile', true)->take(4)->values();
            $desktopModules = $primaryModules->take(6);
        @endphp

        <x-sprite />

        <div class="md:pl-rail" x-data="{ moreOpen: false }" @keydown.escape.window="moreOpen = false">
            <!-- SIDEBAR: Apple Liquid Glass -->
            <aside class="fixed inset-y-0 left-0 z-rail hidden w-rail flex-col items-center border-r border-line apple-liquid-glass-light md:flex">
                <a href="{{ route('home') }}" wire:navigate class="flex h-16 w-full shrink-0 items-center justify-center" aria-label="Daybridge, inicio">
                    {{-- En un riel de 4rem el logotipo horizontal quedaba ilegible: se usa el isotipo sobre una placa clara. --}}
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-inverse p-1.5 shadow-sm ring-1 ring-white/10">
                        <img src="{{ asset('media/brand/A-d-burbuja_icono.png') }}" class="h-full w-full object-contain" alt="" width="40" height="40">
                    </span>
                </a>

                <nav class="relative flex w-full flex-1 flex-col items-center gap-2 overflow-y-auto pt-3" aria-label="Principal" data-anim="sidebar">
                    <span
                        aria-hidden="true"
                        data-anim="sidebar-indicator"
                        class="pointer-events-none absolute left-0 right-0 top-0 z-0 mx-auto h-11 w-11 rounded-2xl bubble-glass opacity-0"
                    ></span>

                    @foreach ($desktopModules as $module)
                        <a
                            href="{{ route($module['route']) }}"
                            wire:navigate
                            data-anim="sidebar-icon"
                            @class([
                                'group relative z-10 flex h-11 w-11 items-center justify-center rounded-md transition duration-fast ease-signal',
                                'bg-brand text-brand-foreground' => request()->routeIs($module['route']),
                                'text-ink-muted hover:bg-sunken hover:text-ink' => ! request()->routeIs($module['route']),
                            ])
                            aria-current="{{ request()->routeIs($module['route']) ? 'page' : 'false' }}"
                            title="{{ $module['label'] }}"
                        >
                            <x-icon :name="$module['icon']" class="h-5 w-5" />
                            <span class="sr-only">{{ $module['label'] }}</span>
                        </a>
                    @endforeach

                    <button
                        type="button"
                        x-on:click="moreOpen = true"
                        class="mt-1 flex h-11 w-11 items-center justify-center rounded-md text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                        title="Todos los módulos"
                        aria-haspopup="dialog"
                        aria-controls="module-sheet"
                    >
                        <x-icon name="grid" class="h-5 w-5" />
                        <span class="sr-only">Todos los módulos</span>
                    </button>
                </nav>

                <div class="flex flex-col items-center gap-2 pb-5">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-md text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                            title="Cerrar sesión"
                        >
                            <x-icon name="logout" class="h-4 w-4" />
                            <span class="sr-only">Cerrar sesión</span>
                        </button>
                    </form>

                    <a href="{{ route('profile') }}" wire:navigate class="mt-1" title="{{ $currentUser->name }}">
                        <x-avatar :user="$currentUser" size="xs" />
                        <span class="sr-only">{{ $currentUser->name }}</span>
                    </a>
                </div>
            </aside>

            <!-- MOBILE HEADER: Apple Liquid Glass -->
            <header class="sticky top-0 z-nav flex h-14 items-center justify-between rounded-none border-b glass-nav px-4 pt-[env(safe-area-inset-top)] md:hidden">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center" aria-label="Daybridge, inicio">
                    {{-- Versión clara del logotipo: la oscura no contrastaba sobre la barra. El nombre ya está en la imagen. --}}
                    <img src="{{ asset('media/brand/A-d-burbuja_horizontal-oscuro.png') }}" class="h-6 w-auto object-contain" alt="Daybridge" width="105" height="24">
                </a>

                <div class="flex items-center gap-2">
                    <a href="{{ route('profile') }}" wire:navigate title="{{ $currentUser->name }}">
                        <x-avatar :user="$currentUser" size="xs" />
                        <span class="sr-only">{{ $currentUser->name }}</span>
                    </a>
                </div>
            </header>

            <main class="pb-[calc(5.25rem+env(safe-area-inset-bottom))] md:pb-0">
                @if (isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>

            <!-- MOBILE NAV: Apple Liquid Glass -->
            <nav
                class="fixed inset-x-0 bottom-0 z-nav grid rounded-none border-t glass-nav pb-[env(safe-area-inset-bottom)] md:hidden"
                style="grid-template-columns: repeat({{ $mobileModules->count() + 1 }}, minmax(0, 1fr))"
                aria-label="Principal"
            >
                @foreach ($mobileModules as $module)
                    <a
                        href="{{ route($module['route']) }}"
                        wire:navigate
                        @class([
                            'flex min-h-[3.25rem] flex-col items-center justify-center gap-1 py-2 text-3xs font-medium tracking-label uppercase transition duration-fast ease-signal',
                            'text-accent-strong' => request()->routeIs($module['route']),
                            'text-ink-muted' => ! request()->routeIs($module['route']),
                        ])
                        aria-current="{{ request()->routeIs($module['route']) ? 'page' : 'false' }}"
                    >
                        <x-icon :name="$module['icon']" class="h-5 w-5" />
                        {{ $module['short'] }}
                    </a>
                @endforeach

                <button
                    type="button"
                    x-on:click="moreOpen = true"
                    class="flex min-h-[3.25rem] flex-col items-center justify-center gap-1 py-2 text-3xs font-medium tracking-label uppercase text-ink-muted transition duration-fast ease-signal"
                    aria-haspopup="dialog"
                    aria-controls="module-sheet"
                >
                    <x-icon name="grid" class="h-5 w-5" />
                    Más
                </button>
            </nav>

            <!-- MODULE SHEET: Apple Liquid Glass -->
            <div
                id="module-sheet"
                x-cloak
                x-show="moreOpen"
                x-transition.opacity.duration.150ms
                class="fixed inset-0 z-overlay"
                role="dialog"
                aria-modal="true"
                aria-label="Todos los módulos"
            >
                <div class="absolute inset-0 bg-inverse/45 backdrop-blur-sm" x-on:click="moreOpen = false"></div>

                <div
                    x-show="moreOpen"
                    x-transition:enter="transition duration-slow ease-out"
                    x-transition:enter-start="translate-y-6 opacity-0 md:-translate-x-6 md:translate-y-0"
                    x-transition:enter-end="translate-y-0 opacity-100 md:translate-x-0"
                    x-transition:leave="transition duration-base ease-signal"
                    x-transition:leave-start="translate-y-0 opacity-100 md:translate-x-0"
                    x-transition:leave-end="translate-y-6 opacity-0 md:-translate-x-6 md:translate-y-0"
                    class="absolute inset-x-0 bottom-0 max-h-[86vh] overflow-y-auto glass-scrollbar border-t border-line apple-liquid-glass-light pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-overlay md:inset-y-0 md:left-rail md:right-auto md:w-[24rem] md:border-l md:border-t-0 md:pb-6"
                >
                    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-line apple-liquid-glass-light px-5 py-4 backdrop-blur">
                        <div>
                            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Daybridge</p>
                            <h2 class="mt-0.5 font-expanded text-lg font-bold">Módulos</h2>
                        </div>

                        <button
                            type="button"
                            x-on:click="moreOpen = false"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                            aria-label="Cerrar"
                        >
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </header>

                    <div class="grid grid-cols-3 gap-2 px-5 pt-5">
                        @foreach ($allModules as $module)
                            @php
                                $isAvailable = ($module['enabled'] ?? false) && \Illuminate\Support\Facades\Route::has($module['route']);
                            @endphp

                            @if ($isAvailable)
                                <a
                                    href="{{ route($module['route']) }}"
                                    wire:navigate
                                    x-on:click="moreOpen = false"
                                    @class([
                                        'flex min-h-[5.25rem] flex-col justify-between rounded-md border p-3 transition duration-fast ease-signal',
                                        'border-brand bg-brand text-brand-foreground' => request()->routeIs($module['route']),
                                        'border-line apple-liquid-glass-light text-ink hover:border-line-strong' => ! request()->routeIs($module['route']),
                                    ])
                                >
                                    <x-icon :name="$module['icon']" class="h-5 w-5" />
                                    <span class="text-xs font-semibold leading-tight">{{ $module['label'] }}</span>
                                </a>
                            @else
                                <div class="flex min-h-[5.25rem] flex-col justify-between rounded-md border border-dashed border-line apple-liquid-glass-light p-3 text-ink-disabled">
                                    <x-icon :name="$module['icon']" class="h-5 w-5" />
                                    <span class="text-xs font-medium leading-tight">{{ $module['label'] }}</span>
                                    <span class="text-3xs tracking-label uppercase">Pronto</span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="mt-6 border-t border-line px-5 pt-4">
                        <a href="{{ route('profile') }}" wire:navigate x-on:click="moreOpen = false" class="flex items-center gap-3 rounded-md p-2 transition duration-fast ease-signal hover:bg-sunken">
                            <x-avatar :user="$currentUser" size="sm" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold">{{ $currentUser->name }}</span>
                                <span class="block text-xs text-ink-muted">{{ $currentUser->role->label() }}</span>
                            </span>
                            <x-icon name="chevron-right" class="h-4 w-4 text-ink-muted" />
                        </a>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-md px-3 text-xs font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink">
                                    <x-icon name="logout" class="h-4 w-4" />
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
