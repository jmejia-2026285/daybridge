<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Daybridge' }}</title>

        <script>
            (() => {
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
    <body class="isolate min-h-screen font-sans text-ink antialiased bg-apple-wallpaper">
        <div class="pointer-events-none fixed inset-0 z-[-1] glass-overlay opacity-60" aria-hidden="true"></div>
        <x-sprite />

        <div class="flex min-h-screen flex-col">
            <header class="mx-auto flex w-full max-w-content items-center justify-between px-4 pb-5 pt-[calc(1.25rem+env(safe-area-inset-top))] sm:px-6 md:px-10">
                <a href="{{ url('/') }}" wire:navigate class="flex items-center" aria-label="Daybridge, inicio">
                    {{-- Mismo logotipo que dentro de la app (antes se usaba otra marca, pequeña y de bajo contraste). --}}
                    <img src="{{ asset('media/brand/A-d-burbuja_horizontal-oscuro.png') }}" class="h-7 w-auto object-contain" alt="Daybridge" width="112" height="28">
                </a>
            </header>

            <main class="flex flex-1 items-center justify-center px-4 pb-16 pt-4 sm:px-6">
                <div class="w-full max-w-md">
                    <div class="border border-line bg-surface p-6 sm:p-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-3xs tracking-label text-ink-muted uppercase">
                        Comunicación interna
                    </p>
                </div>
            </main>
        </div>
    </body>
</html>
