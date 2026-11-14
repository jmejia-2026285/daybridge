import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
        server: {
            host: true,
            hmr: {
                // IP LAN de esta máquina; se define en .env (VITE_HMR_HOST)
                host: env.VITE_HMR_HOST || 'localhost',
            },
        },
    };
});
