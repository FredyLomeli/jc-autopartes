import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/public.js',
                'resources/js/pdf-viewer.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    if (assetInfo.names?.some((name) => name.endsWith('.mjs')) || assetInfo.name?.endsWith('.mjs')) {
                        return 'assets/[name]-[hash].js';
                    }

                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
});
