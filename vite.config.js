import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // The design system's three faces (Space Grotesk / DM Sans /
            // JetBrains Mono) plus Material Symbols are requested in one
            // stylesheet from the layout heads, so there is no `fonts:` plugin
            // here — the scaffolding default (Instrument Sans) was never used.
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
