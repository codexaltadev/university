// Default theme asset build (Theme Guide §12): run from the repository root with
// `npm run build:theme` (or `npm run dev:theme`); output goes to public/build here.
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('.', import.meta.url));

export default defineConfig({
    root,
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            publicDirectory: 'public',
            buildDirectory: 'build',
            hotFile: `${root}public/hot`,
            refresh: ['views/**', '../../Modules/*/resources/views/**', '../../platform/resources/views/**'],
        }),
    ],
    build: {
        outDir: `${root}public/build`,
        emptyOutDir: true,
    },
});
