import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Vite configuration for development and production asset compilation.
export default defineConfig({
    plugins: [
        laravel({
            // Primary asset entry points for the app.
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Local dev server config used by Vite HMR.
        host: '127.0.0.1',
        port: 5173,
        watch: {
            // Ignore compiled Blade view cache files to avoid unnecessary rebuilds.
            ignored: ['**/storage/framework/views/**'],
        },
        hmr: {
            host: '127.0.0.1',
        },
    },
});
