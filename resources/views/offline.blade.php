<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#0B1416">
        <title>Sin conexión · Daybridge</title>
        <link rel="icon" href="/icons/favicon.png" type="image/png">
        <link rel="manifest" href="/manifest.webmanifest">
        <style>
            :root { color-scheme: dark; }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 2rem 1.25rem calc(2rem + env(safe-area-inset-bottom));
                font-family: Archivo, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
                color: #E8EDE7;
                background:
                    linear-gradient(180deg, rgba(11, 20, 22, .93), rgba(11, 20, 22, .99)),
                    url('/media/background/fondo-montanas.jpg') center / cover no-repeat;
            }
            main { width: min(30rem, 100%); }
            .brand { display: flex; align-items: center; gap: .65rem; color: #7DBBB2; font-weight: 700; letter-spacing: -.02em; }
            .eyebrow { margin: 2.5rem 0 0; font-size: .6875rem; letter-spacing: .08em; text-transform: uppercase; color: #8B9896; font-weight: 600; }
            h1 { margin: .5rem 0 0; font-size: clamp(2rem, 7vw, 3.25rem); line-height: .98; letter-spacing: -.035em; }
            p { margin: 1rem 0 0; font-size: .95rem; line-height: 1.6; color: #B3BFBD; max-width: 34ch; }
            .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.75rem; }
            a, button {
                display: inline-flex; align-items: center; justify-content: center;
                min-height: 2.75rem; padding: 0 1.1rem; border-radius: .375rem;
                font: inherit; font-size: .875rem; font-weight: 600; text-decoration: none; cursor: pointer;
                transition: background-color 120ms cubic-bezier(.2, 0, 0, 1);
            }
            .primary { background: #7DBBB2; color: #0B1416; border: 1px solid #7DBBB2; }
            .primary:hover { background: #4E9A90; }
            .secondary { background: transparent; color: #E8EDE7; border: 1px solid #253537; }
            .secondary:hover { background: #111E20; }
            .note { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #253537; font-size: .6875rem; letter-spacing: .08em; text-transform: uppercase; color: #8B9896; }
        </style>
    </head>
    <body>
        <main>
            <div class="brand" aria-label="Daybridge">
                <svg viewBox="0 0 32 32" width="28" height="28" fill="none" aria-hidden="true">
                    <path d="M6 26V18M26 26V18" stroke="currentColor" stroke-width="2.5" stroke-linecap="square" />
                    <path d="M6 19.5C9.5 12.5 22.5 12.5 26 19.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="square" />
                    <circle cx="16" cy="14.5" r="2.5" fill="#FF5A1F" />
                </svg>
                Daybridge
            </div>

            <p class="eyebrow">Modo sin conexión</p>
            <h1>El puente está en pausa.</h1>
            <p>No pudimos alcanzar la red. Revisa tu conexión y vuelve a intentarlo; lo que ya visitaste sigue disponible.</p>

            <div class="actions">
                <button type="button" class="primary" onclick="window.location.reload()">Reintentar</button>
                <a href="/" class="secondary">Ir al inicio</a>
            </div>

            <p class="note">Daybridge · Comunicación interna</p>
        </main>
    </body>
</html>
