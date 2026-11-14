import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    base: './',
    build: {
        // Keep locally bundled fonts usable from XAMPP sub-folder deployments.
        assetsInlineLimit: 1024 * 1024,
    },
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
    ],
});
