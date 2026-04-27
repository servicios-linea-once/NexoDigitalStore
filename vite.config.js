import { defineConfig } from 'vite';
import laravel    from 'laravel-vite-plugin';
import vue        from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';
import compression from 'vite-plugin-compression';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
        tailwindcss(),

        // Auto-import PrimeVue components
        Components({
            resolvers: [PrimeVueResolver()],
            dts: false,
        }),

        // ── Optimización: Compresión Gzip/Brotli ───────────────────────
        compression({
            algorithm: 'gzip',
            ext: '.gz',
        }),

        // ── PWA: Offline & Manifest ────────────────────────────────────
        VitePWA({
            registerType: 'autoUpdate',
            // En dev: elimina el SW para que no cachee assets desactualizados
            selfDestroying: process.env.NODE_ENV !== 'production',
            injectRegister: 'auto',
            workbox: {
                // Forzar que el nuevo SW tome control inmediatamente
                skipWaiting: true,
                clientsClaim: true,
            },
            manifest: {
                name: 'Nexo Digital Store',
                short_name: 'NexoKeys',
                description: 'Tu marketplace de claves y licencias digitales de confianza.',
                theme_color: '#6366f1',
                background_color: '#09090b',
                display: 'standalone',
                icons: [
                    {
                        src: 'https://res.cloudinary.com/dexton/image/upload/v1713735000/site-branding/pwa-192.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: 'https://res.cloudinary.com/dexton/image/upload/v1713735000/site-branding/pwa-512.png',
                        sizes: '512x512',
                        type: 'image/png'
                    }
                ]
            }
        })
    ],
    resolve: {
        alias: { '@': '/resources/js' },
    },
    server: {
        host:  '0.0.0.0',
        port:  5173,
        hmr:   { host: '127.0.0.1' },
        watch: { ignored: ['**/storage/framework/views/**'] },
    },
});
