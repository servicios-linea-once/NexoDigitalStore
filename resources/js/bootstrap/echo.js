/**
 * echo.js — [PUNTO-4] Configuración de Laravel Echo con Reverb.
 *
 * Importar este archivo en resources/js/app.js:
 *   import './bootstrap/echo'
 *
 * Asegúrate de tener en .env:
 *   REVERB_APP_KEY=nexo_reverb_key
 *   REVERB_HOST=localhost
 *   REVERB_PORT=8080
 *   REVERB_SCHEME=http
 *
 * Y en el frontend (.env del Vite):
 *   VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
 *   VITE_REVERB_HOST="${REVERB_HOST}"
 *   VITE_REVERB_PORT="${REVERB_PORT}"
 *   VITE_REVERB_SCHEME="${REVERB_SCHEME}"
 *
 * Instalar dependencias npm:
 *   npm install laravel-echo pusher-js
 */
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: 'reverb',
    key:         import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:      import.meta.env.VITE_REVERB_HOST      ?? 'localhost',
    wsPort:      import.meta.env.VITE_REVERB_PORT      ?? 8080,
    wssPort:     import.meta.env.VITE_REVERB_PORT      ?? 443,
    scheme:      import.meta.env.VITE_REVERB_SCHEME    ?? 'http',
    forceTLS:   (import.meta.env.VITE_REVERB_SCHEME   ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],

    // Autenticación de canales privados vía Sanctum
    // (usa la cookie de sesión web — no requiere token manual)
    authEndpoint: '/broadcasting/auth',
})
