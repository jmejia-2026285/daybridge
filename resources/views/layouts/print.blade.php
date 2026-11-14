<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Imprimir tickets · IT Desk' }}</title>

        <link rel="icon" href="{{ asset('icons/favicon.png') }}" type="image/png">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            @page {
                size: letter landscape;
                margin: 0.35in;
            }

            @media print {
                html,
                body {
                    background: #fff !important;
                }

                .print-hidden {
                    display: none !important;
                }

                .print-sheet {
                    display: grid !important;
                    grid-template-columns: 1fr 1fr !important;
                    gap: 0.25in !important;
                    width: 100% !important;
                    max-width: none !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .print-ticket {
                    break-inside: avoid;
                    box-shadow: none !important;
                }
            }
        </style>
    </head>
    <body class="min-h-screen bg-sunken text-ink antialiased">
        <x-sprite />

        {{ $slot }}
    </body>
</html>
