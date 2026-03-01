import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import { nativephpMobile } from './vendor/nativephp/mobile/resources/js/vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
        nativephpMobile(),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
            },
        },
    },
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
