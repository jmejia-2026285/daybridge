<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#0E3B3A">
        <title>@yield('title', 'Algo salió mal') · Daybridge</title>
        <link rel="icon" href="/icons/favicon.png" type="image/png">
        <style>
            :root { color-scheme: light dark; }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 2rem 1.25rem calc(2rem + env(safe-area-inset-bottom));
                font-family: Archivo, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
                color: #101820;
                background: #EDEAE3;
            }
            main { width: min(34rem, 100%); }
            .brand { display: flex; align-items: center; gap: .65rem; color: #0E3B3A; font-weight: 700; letter-spacing: -.02em; }
            .code { margin: 2.25rem 0 0; font-size: .6875rem; letter-spacing: .08em; text-transform: uppercase; color: #5F6A6B; font-weight: 600; }
            h1 { margin: .4rem 0 0; font-size: clamp(2rem, 7vw, 3.25rem); line-height: .98; letter-spacing: -.035em; }
            p { margin: .9rem 0 0; font-size: .95rem; line-height: 1.6; color: #46514F; max-width: 38ch; }
            figure { margin: 1.5rem 0 0; }
            figure img { display: block; width: 100%; max-width: 20rem; height: auto; border-radius: .5rem; }
            .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.75rem; }
            a, button {
                display: inline-flex; align-items: center; justify-content: center;
                min-height: 2.75rem; padding: 0 1.1rem; border-radius: .375rem;
                font: inherit; font-size: .875rem; font-weight: 600; text-decoration: none; cursor: pointer;
            }
            .primary { background: #0E3B3A; color: #F7F5F0; border: 1px solid #0E3B3A; }
            .secondary { background: transparent; color: #101820; border: 1px solid #CFCBC2; }
            .note { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #CFCBC2; font-size: .6875rem; letter-spacing: .08em; text-transform: uppercase; color: #5F6A6B; }
            @media (prefers-color-scheme: dark) {
                body { color: #E8EDE7; background: #0B1416; }
                .brand { color: #7DBBB2; }
                .code, .note { color: #8B9896; }
                p { color: #B3BFBD; }
                .primary { background: #7DBBB2; color: #0B1416; border-color: #7DBBB2; }
                .secondary { color: #E8EDE7; border-color: #253537; }
                .note { border-color: #253537; }
            }
        </style>
    </head>
    <body>
        <main>
            <a href="/" class="brand" aria-label="Daybridge" style="text-decoration: none">
                <svg viewBox="0 0 32 32" width="28" height="28" fill="none" aria-hidden="true">
                    <path d="M6 26V18M26 26V18" stroke="currentColor" stroke-width="2.5" stroke-linecap="square" />
                    <path d="M6 19.5C9.5 12.5 22.5 12.5 26 19.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="square" />
                    <circle cx="16" cy="14.5" r="2.5" fill="#FF5A1F" />
                </svg>
                Daybridge
            </a>

            <p class="code">Error @yield('code', '500')</p>
            <h1>@yield('heading', 'Algo se cruzó en el puente.')</h1>
            <p>@yield('message', 'No pudimos completar la operación. Intenta de nuevo en unos segundos.')</p>

            @hasSection('illustration')
                <figure>
                    <img src="@yield('illustration')" alt="" loading="lazy">
                </figure>
            @endif

            <div class="actions">
                <a href="/" class="primary">Volver al inicio</a>
                <button type="button" class="secondary" onclick="window.location.reload()">Reintentar</button>
            </div>

            <p class="note">Daybridge · Comunicación interna</p>
        </main>
    </body>
</html>
