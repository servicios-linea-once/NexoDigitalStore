/**
 * useOrderChannel.js — [PUNTO-4] Vue 3 composable para escuchar eventos de orden via Reverb.
 *
 * Uso en la página Orders/Show.vue:
 *
 *   import { useOrderChannel } from '@/composables/useOrderChannel'
 *   import { router } from '@inertiajs/vue3'
 *
 *   const { isListening } = useOrderChannel(
 *     page.props.auth.user.id,
 *     page.props.order.ulid,
 *     () => router.reload({ only: ['order'] }) // recarga solo la prop 'order'
 *   )
 *
 * Requisitos (instalar si no están):
 *   npm install laravel-echo pusher-js
 *
 * Variables de entorno (.env):
 *   VITE_REVERB_APP_KEY=your_key
 *   VITE_REVERB_HOST=localhost
 *   VITE_REVERB_PORT=8080
 *   VITE_REVERB_SCHEME=http
 */

import { ref, onMounted, onUnmounted } from 'vue'

/**
 * @param {number}   userId      - ID del usuario autenticado (auth.user.id)
 * @param {string}   orderUlid   - ULID de la orden actual (para filtrar el evento)
 * @param {Function} onCompleted - Callback cuando el pago es confirmado
 */
export function useOrderChannel(userId, orderUlid, onCompleted) {
    const isListening  = ref(false)
    const lastPayload  = ref(null)
    let   channel      = null

    function subscribe() {
        // Echo debe estar inicializado globalmente en bootstrap/echo.js (ver abajo)
        if (typeof window.Echo === 'undefined') {
            console.warn('[useOrderChannel] window.Echo no está disponible. ¿Instalaste laravel-echo?')
            return
        }

        channel = window.Echo.private(`order.${userId}`)

        channel.listen('OrderCompleted', (payload) => {
            lastPayload.value = payload

            // Solo actuar si el evento corresponde a la orden actual
            if (payload.order_ulid !== orderUlid) return

            isListening.value = false

            // Llamar al callback (normalmente: router.reload o toast + redirect)
            if (typeof onCompleted === 'function') {
                onCompleted(payload)
            }
        })

        isListening.value = true
        console.info(`[useOrderChannel] Escuchando canal order.${userId} para orden ${orderUlid}`)
    }

    function unsubscribe() {
        if (channel) {
            window.Echo.leave(`order.${userId}`)
            channel      = null
            isListening.value = false
        }
    }

    onMounted(subscribe)
    onUnmounted(unsubscribe)

    return { isListening, lastPayload, subscribe, unsubscribe }
}
