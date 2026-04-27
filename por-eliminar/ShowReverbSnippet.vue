<!--
  Orders/Show.vue — Integración de WebSocket Reverb para confirmación de pago en tiempo real.

  Añade este bloque <script setup> a tu página existente Orders/Show.vue.
  El composable escucha el canal privado del usuario y, cuando detecta
  el evento 'OrderCompleted' para ESTA orden, recarga los datos automáticamente
  y muestra un toast de confirmación — sin que el usuario tenga que recargar la página.

  ⚠️ IMPORTANTE: Importa './bootstrap/echo' en app.js ANTES de usar este composable.
-->
<script setup>
import { computed }          from 'vue'
import { router, usePage }   from '@inertiajs/vue3'
import { useToast }          from 'primevue/usetoast'
import { useOrderChannel }   from '@/composables/useOrderChannel'

const props = defineProps({
    order: { type: Object, required: true },
})

const page    = usePage()
const toast   = useToast()
const userId  = computed(() => page.props.auth?.user?.id)

// Solo escuchar si la orden está en estado pendiente o processing
const shouldListen = computed(() =>
    ['pending', 'processing'].includes(props.order.status)
)

// [PUNTO-4] Suscripción al canal privado de Reverb
const { isListening } = useOrderChannel(
    userId.value,
    props.order.ulid,

    // Callback al detectar el pago confirmado
    (payload) => {
        // 1. Toast de confirmación
        toast.add({
            severity: 'success',
            summary:  '¡Pago Confirmado! 🎉',
            detail:   payload.message ?? 'Tu pedido ha sido procesado.',
            life:     8000,
        })

        // 2. Recargar solo la prop 'order' — Inertia partial reload
        //    Esto actualiza el estado de la orden y muestra las claves digitales
        router.reload({
            only:        ['order'],
            onSuccess:   () => console.info('[OrderChannel] Orden recargada correctamente.'),
        })
    }
)
</script>

<!--
  En el <template> de Orders/Show.vue, añade este indicador visual
  para que el usuario sepa que está esperando la confirmación:

  <div v-if="isListening" class="payment-waiting-badge">
    <i class="pi pi-spin pi-spinner" />
    Esperando confirmación de pago...
  </div>

  Ejemplo completo de badge:
-->
<template>
  <!-- ⬇️ Pega solo este bloque dentro de tu <template> existente ⬇️ -->
  <Transition name="fade">
    <div v-if="isListening && shouldListen" class="payment-waiting">
      <span class="pulse-dot" />
      <span>Esperando confirmación del gateway de pago...</span>
      <small>Esta página se actualizará automáticamente.</small>
    </div>
  </Transition>
</template>

<style scoped>
.payment-waiting {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.875rem 1.25rem; border-radius: 12px;
  background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.3);
  color: var(--c-text); font-size: 0.875rem; margin-bottom: 1.25rem;
}
.payment-waiting small { color: var(--c-text-muted); font-size: 0.75rem; }
.pulse-dot {
  width: 10px; height: 10px; border-radius: 50%;
  background: #6366f1; flex-shrink: 0;
  animation: pulse-ring 1.5s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
}
@keyframes pulse-ring {
  0%   { box-shadow: 0 0 0 0 rgba(99,102,241, 0.7); }
  70%  { box-shadow: 0 0 0 10px rgba(99,102,241, 0); }
  100% { box-shadow: 0 0 0 0 rgba(99,102,241, 0); }
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
