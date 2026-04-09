import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/site.css',
                'resources/js/site.js',
                'resources/css/jquery-ui.css',
                'resources/css/style.css',
                'resources/css/mmh1xmw.css',
                'resources/css/viewer.css',
                'resources/js/jquery-3.6.0.min.js',
                'resources/js/jquery-ui.js',
                'resources/js/lottie.min.js',
                'resources/js/scr-actions.js',
                'resources/js/scr-delete.js',
                'resources/js/scr-display.js',
                'resources/js/scr-send.js',
            ],
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
