import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

/**
 * SEWA HOSPITALITY — Vite build pipeline.
 *
 * Builds happen LOCALLY/CI only (shared hosting never runs Node).
 * Deploys ship `public/build/*` + the manifest. One Tailwind build,
 * two content roots (public site + admin), one shared token file:
 * resources/css/tokens.css is the single source of truth.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        target: 'esnext',
        cssMinify: 'lightningcss',
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
});
