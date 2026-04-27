<template>
  <AppLayout>
    <Head :title="`Orden #${shortId} — Nexo Digital Store`" />

    <div class="page-wrap">
      <!-- Header -->
      <div class="order-header">
        <div>
          <nav class="breadcrumb">
            <Link :href="route('orders.index')" class="bc-link">
              <i class="pi pi-arrow-left" style="font-size:0.7rem" /> Mis Órdenes
            </Link>
            <i class="pi pi-chevron-right bc-sep" />
            <span>#{{ shortId }}</span>
          </nav>
          <h1 class="page-title">Orden <span class="mono">#{{ shortId }}</span></h1>
          <p class="order-meta">{{ fmtDate(order.created_at) }} · {{ paymentLabel(order.payment_method) }}</p>
        </div>
        <Tag
          :value="statusLabel(order.status)"
          :severity="statusSeverity(order.status)"
          :icon="`pi ${statusIcon(order.status)}`"
          rounded
          class="order-status-tag"
        />
      </div>

      <div class="order-grid">
        <!-- LEFT: products + keys -->
        <div class="col-main">

          <!-- Success banner -->
          <div v-if="order.is_completed" class="banner banner-success">
            <i class="pi pi-check-circle banner-icon" style="color:var(--p-green-400)" />
            <div>
              <p class="banner-title">¡Pago confirmado! Tus claves están listas.</p>
              <p class="banner-sub">Completado el {{ fmtDate(order.completed_at) }}. Guarda tus claves en lugar seguro.</p>
            </div>
          </div>

          <!-- [PUNTO-4] Banner de espera en tiempo real mientras el pago se confirma -->
          <Transition name="fade-ws">
            <div v-if="waitingPayment" class="banner banner-ws">
              <span class="ws-dot" />
              <div>
                <p class="banner-title">Esperando confirmación del pago…</p>
                <p class="banner-sub">Esta página se actualizará automáticamente al recibir la confirmación.</p>
              </div>
            </div>
          </Transition>

          <!-- Pending banner + payment -->
          <div v-if="order.is_pending" class="banner banner-warn">
            <i class="pi pi-exclamation-circle banner-icon" style="color:var(--p-yellow-400)" />
            <div class="flex-1">
              <p class="banner-title">Pago pendiente</p>
              <p class="banner-sub">Completa el pago para recibir tus claves.</p>
              <div class="payment-zone">
                <div v-if="order.payment_method === 'paypal'">
                  <div id="paypal-button-container" class="paypal-wrap" />
                  <p v-if="paypalLoading" class="pay-hint"><i class="pi pi-spin pi-spinner" /> Cargando PayPal...</p>
                </div>
                <Button
                  v-if="order.payment_method === 'mercadopago'"
                  label="Pagar con MercadoPago"
                  icon="pi pi-credit-card"
                  :loading="mpLoading"
                  @click="redirectToMP"
                  class="mp-btn"
                />
              </div>
            </div>
          </div>

          <!-- Products -->
          <DataCard :noPadding="true" class="mb-4">
            <template #header>
              <div class="card-head">
                <i class="pi pi-shopping-bag" /> <span>Productos ({{ items.length }})</span>
              </div>
            </template>

            <div v-for="item in items" :key="item.id" class="item-row">
              <div class="item-cover">
                <img v-if="item.product_cover" :src="item.product_cover" :alt="item.product_name" class="cover-img" />
                <div v-else class="cover-ph"><i class="pi pi-box" /></div>
              </div>
              <div class="item-body">
                <p class="item-name">{{ item.product_name }}</p>
                <div class="item-tags">
                  <Chip v-if="item.platform" :label="item.platform" class="chip-xs" />
                  <Chip v-if="item.region" :label="item.region" class="chip-xs" />
                  <Tag :value="deliveryLabel(item.delivery_status)" :severity="deliverySeverity(item.delivery_status)" size="small" rounded />
                </div>

                <!-- Key revelation -->
                <div v-if="item.revealed_key" class="key-box">
                  <div class="key-header">
                    <i class="pi pi-key" style="color:var(--c-primary)" />
                    <span>Clave de Activación</span>
                    <Tag value="Entregada" severity="success" size="small" rounded class="ml-auto" />
                  </div>
                  <div class="key-value-row">
                    <InputText
                      :value="keyHidden[item.id] ? '••••••••••••••••' : item.revealed_key"
                      readonly
                      :class="['key-input', { revealed: !keyHidden[item.id] }]"
                    />
                    <Button :icon="keyHidden[item.id] ? 'pi pi-eye' : 'pi pi-eye-slash'" text size="small" @click="toggleKey(item.id)" v-tooltip.top="keyHidden[item.id]?'Revelar':'Ocultar'" />
                    <Button icon="pi pi-copy" text size="small" :severity="copied[item.id] ? 'success' : 'secondary'" @click="copyKey(item)" v-tooltip.top="'Copiar'" />
                  </div>
                </div>
                <div v-else-if="!order.is_completed" class="key-pending">
                  <i class="pi pi-clock" /> Disponible al completar el pago
                </div>
              </div>
              <div class="item-price">
                <span class="price-val">${{ item.unit_price.toFixed(2) }}</span>
                <span v-if="item.cashback_amount > 0" class="cashback-badge">+{{ item.cashback_amount }} NT</span>
              </div>
            </div>
          </DataCard>
        </div>

        <!-- RIGHT: summary -->
        <div class="col-side">
          <DataCard class="mb-4">
            <template #header>
              <div class="card-head"><i class="pi pi-receipt" /> Resumen del pago</div>
            </template>
            <div class="sum-list">
              <div class="sum-row">
                <span>Subtotal</span>
                <span>${{ order.subtotal.toFixed(2) }}</span>
              </div>
              <div v-if="order.discount_amount > 0" class="sum-row text-green">
                <span>Descuento suscripción</span>
                <span>-${{ order.discount_amount.toFixed(2) }}</span>
              </div>
              <div v-if="order.nexocoins_used > 0" class="sum-row text-yellow">
                <span><i class="pi pi-star-fill" /> NexoTokens usados</span>
                <span>-{{ order.nexocoins_used }} NT</span>
              </div>
              <Divider />
              <div class="sum-row sum-total">
                <span>Total pagado</span>
                <span>${{ order.total.toFixed(2) }} {{ order.currency }}</span>
              </div>
              <div class="sum-row">
                <span>Método</span>
                <span>{{ paymentLabel(order.payment_method) }}</span>
              </div>
              <div v-if="order.payment_reference" class="sum-row">
                <span>Referencia</span>
                <code class="ref-code">{{ order.payment_reference.slice(-12) }}</code>
              </div>
            </div>
          </DataCard>

          <!-- Actions -->
          <div class="side-actions">
            <Button
              label="Volver a mis órdenes"
              icon="pi pi-arrow-left"
              severity="secondary"
              outlined
              @click="$inertia.visit(route('orders.index'))"
              fluid
            />
            <Button
              label="Seguir comprando"
              icon="pi pi-shopping-bag"
              @click="$inertia.visit(route('products.index'))"
              fluid
              class="mt-2"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataCard   from '@/Components/ui/DataCard.vue';
import axios from 'axios';

const props = defineProps({
  order:         { type: Object, required: true },
  items:         { type: Array,  default: () => [] },
  paypalClientId:{ type: String, default: '' },
  paypalMode:    { type: String, default: 'sandbox' },
  mpPublicKey:   { type: String, default: '' },
});

const toast    = useToast();
const shortId  = computed(() => props.order.ulid.slice(-8).toUpperCase());
const keyHidden = ref({});
const copied    = ref({});
const paypalLoading = ref(false);
const mpLoading     = ref(false);

props.items.forEach(item => { if (item.revealed_key) keyHidden.value[item.id] = true; });

function toggleKey(id) { keyHidden.value[id] = !keyHidden.value[id]; }

async function copyKey(item) {
  await navigator.clipboard.writeText(item.revealed_key);
  copied.value[item.id] = true;
  toast.add({ severity: 'success', summary: '¡Clave copiada!', life: 2000 });
  setTimeout(() => { copied.value[item.id] = false; }, 2500);
}

function fmtDate(d) { return d ? new Date(d).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' }) : '—'; }
const statusLabel    = s => ({ pending:'Pendiente', processing:'Procesando', completed:'Completado', failed:'Fallido', cancelled:'Cancelado', refunded:'Reembolsado', disputed:'En disputa' }[s] ?? s);
const statusSeverity = s => ({ pending:'warn', processing:'info', completed:'success', failed:'danger', cancelled:'danger', refunded:'secondary', disputed:'danger' }[s] ?? 'secondary');
const statusIcon     = s => ({ pending:'pi-clock', processing:'pi-spinner pi-spin', completed:'pi-check-circle', failed:'pi-times-circle', cancelled:'pi-times-circle', refunded:'pi-refresh', disputed:'pi-exclamation-triangle' }[s] ?? 'pi-info-circle');
const deliveryLabel    = s => ({ pending:'Pendiente', delivered:'Entregada', failed:'Error', disputed:'En disputa' }[s] ?? s);
const deliverySeverity = s => ({ pending:'warn', delivered:'success', failed:'danger', disputed:'danger' }[s] ?? 'secondary');
const paymentLabel     = s => ({ paypal:'PayPal', mercadopago:'MercadoPago', nexotokens:'NexoTokens', mixed:'Mixto', manual:'Manual' }[s] ?? s);

// ── [PUNTO-4] WebSocket via Laravel Reverb ───────────────────
// Canal: private-order.{buyer_id} (autenticado via Sanctum)
// Se escucha SOLO cuando la orden está pendiente de pago.
const waitingPayment = ref(
  ['pending', 'processing'].includes(props.order.status)
);

let echoChannel = null;

function subscribeToOrderChannel() {
  if (typeof window.Echo === 'undefined') return;  // Reverb no instalado
  if (!waitingPayment.value)               return;  // Orden ya completada

  echoChannel = window.Echo.private(`order.${props.order.buyer_id}`);

  echoChannel.listen('OrderCompleted', (e) => {
    // Ignorar eventos de otras órdenes del mismo usuario
    if (e.order_ulid !== props.order.ulid) return;

    waitingPayment.value = false;

    toast.add({
      severity : 'success',
      summary  : '¡Pago Confirmado! 🎉',
      detail   : e.message ?? 'Tu pedido ha sido procesado. Tus claves están listas.',
      life     : 8000,
    });

    // Recarga solo la prop 'order' — no recarga toda la página
    router.reload({ only: ['order', 'items'] });
  });
}

function unsubscribeFromOrderChannel() {
  if (echoChannel) {
    window.Echo.leave(`order.${props.order.buyer_id}`);
    echoChannel = null;
  }
}

// ── PayPal ───────────────────────────────────────────────────
onMounted(() => {
  subscribeToOrderChannel();
  if (props.order.is_pending && props.order.payment_method === 'paypal') initPayPal();
});

onUnmounted(() => {
  unsubscribeFromOrderChannel();
});

function loadScript(src) {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${src}"]`)) { resolve(); return; }
    const s = document.createElement('script');
    s.src = src; s.onload = resolve; s.onerror = reject;
    document.head.appendChild(s);
  });
}

async function initPayPal() {
  paypalLoading.value = true;
  const currency = ['PEN','NT'].includes(props.order.currency) ? 'USD' : props.order.currency;
  try {
    await loadScript(`https://www.paypal.com/sdk/js?client-id=${props.paypalClientId}&currency=${currency}&intent=capture`);
    paypalLoading.value = false;
    window.paypal.Buttons({
      style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'pay' },
      createOrder: async () => (await axios.post(route('payment.paypal.create'), { order_ulid: props.order.ulid })).data.paypal_order_id,
      onApprove: async (data) => {
        const res = await axios.post(route('payment.paypal.capture'), { paypal_order_id: data.orderID, order_ulid: props.order.ulid });
        if (res.data.success) router.reload();
      },
    }).render('#paypal-button-container');
  } catch { paypalLoading.value = false; }
}

async function redirectToMP() {
  mpLoading.value = true;
  try {
    const res = await axios.post(route('payment.mp.preference'), { order_ulid: props.order.ulid });
    if (res.data.init_point) window.location.href = res.data.init_point;
  } catch { mpLoading.value = false; }
}
</script>

<style scoped>
.page-wrap    { max-width: 1100px; margin: 0 auto; padding-bottom: 3rem; }
.order-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem; }
.breadcrumb   { display: flex; align-items: center; gap: 0.4rem; font-size: 0.75rem; color: var(--c-text-muted); margin-bottom: 0.35rem; }
.bc-link      { color: var(--c-primary); text-decoration: none; display: flex; align-items: center; gap: 0.3rem; }
.bc-sep       { font-size: 0.6rem; }
.page-title   { font-size: 1.4rem; font-weight: 800; color: var(--c-text); margin: 0; }
.mono         { font-family: monospace; }
.order-meta   { font-size: 0.78rem; color: var(--c-text-muted); margin: 0.25rem 0 0; }
.order-status-tag { font-size: 0.8rem !important; }

/* Grid */
.order-grid { display: grid; grid-template-columns: 1fr 340px; gap: 1.25rem; }
@media (max-width: 860px) { .order-grid { grid-template-columns: 1fr; } }
.col-main { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
.col-side { display: flex; flex-direction: column; gap: 1rem; }

/* Banners */
.banner       { display: flex; align-items: flex-start; gap: 1rem; border-radius: 14px; padding: 1.125rem 1.25rem; margin-bottom: 0.5rem; }
.banner-icon  { font-size: 1.4rem; flex-shrink: 0; margin-top: 0.1rem; }
.banner-title { font-size: 0.9rem; font-weight: 700; color: var(--c-text); margin: 0 0 0.2rem; }
.banner-sub   { font-size: 0.78rem; color: var(--c-text-muted); margin: 0; }
.banner-success { background: rgba(52,211,153,0.06); border: 1px solid rgba(52,211,153,0.2); }
.banner-warn    { background: rgba(251,191,36,0.06); border: 1px solid rgba(251,191,36,0.25); }
.flex-1         { flex: 1; min-width: 0; }
.payment-zone   { margin-top: 1rem; }
.paypal-wrap    { min-height: 44px; }
.pay-hint       { font-size: 0.75rem; color: var(--c-text-muted); margin-top: 0.5rem; }
.mp-btn         { width: 100%; }

/* Card head */
.card-head { display: flex; align-items: center; gap: 0.5rem; padding: 0.875rem 1.125rem; font-size: 0.85rem; font-weight: 700; color: var(--c-text); }

/* Items */
.item-row   { display: flex; align-items: flex-start; gap: 1rem; padding: 1rem 1.125rem; border-bottom: 1px solid var(--c-border); }
.item-row:last-child { border-bottom: none; }
.item-cover { flex-shrink: 0; }
.cover-img  { width: 64px; height: 48px; border-radius: 8px; object-fit: cover; }
.cover-ph   { width: 64px; height: 48px; border-radius: 8px; background: var(--c-card); display: flex; align-items: center; justify-content: center; color: var(--c-text-subtle); font-size: 1.2rem; }
.item-body  { flex: 1; min-width: 0; }
.item-name  { font-size: 0.9rem; font-weight: 700; color: var(--c-text); margin: 0 0 0.4rem; }
.item-tags  { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-bottom: 0.625rem; }
:deep(.chip-xs .p-chip) { padding: 0.1rem 0.4rem !important; font-size: 0.65rem !important; }
.item-price { flex-shrink: 0; text-align: right; }
.price-val  { font-size: 1rem; font-weight: 800; color: var(--c-text); display: block; }
.cashback-badge { font-size: 0.72rem; color: var(--p-yellow-400); display: block; margin-top: 0.2rem; }

/* Key box */
.key-box     { background: rgba(99,102,241,0.05); border: 1px solid rgba(99,102,241,0.2); border-radius: 10px; padding: 0.875rem; margin-top: 0.5rem; }
.key-header  { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; font-weight: 600; color: var(--c-text); margin-bottom: 0.625rem; }
.ml-auto     { margin-left: auto; }
.key-value-row { display: flex; align-items: center; gap: 0.25rem; }
.key-input   { font-family: monospace; font-size: 0.85rem; flex: 1; color: var(--c-text-muted); }
.key-input.revealed { color: var(--c-primary); font-weight: 700; }
.key-pending { display: flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: var(--c-text-muted); margin-top: 0.5rem; }

/* Summary */
.sum-list { padding: 0.25rem 0; }
.sum-row  { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--c-text-muted); padding: 0.5rem 0; }
.sum-total { font-weight: 800; font-size: 1rem; color: var(--c-text); }
.text-green { color: var(--p-green-400) !important; }
.text-yellow { color: var(--p-yellow-400) !important; }
.ref-code { font-family: monospace; font-size: 0.78rem; color: var(--c-text-subtle); }
.side-actions { display: flex; flex-direction: column; }
.mt-2 { margin-top: 0.5rem; }
.mb-4 { margin-bottom: 1rem; }

/* ── [PUNTO-4] WebSocket waiting banner ──────────────────── */
.banner-ws {
  background: rgba(99,102,241,0.06);
  border: 1px solid rgba(99,102,241,0.25);
  align-items: center; gap: 0.875rem;
}
.ws-dot {
  flex-shrink: 0;
  display: inline-block; width: 12px; height: 12px;
  border-radius: 50%; background: var(--c-primary, #6366f1);
  animation: ws-pulse 1.5s ease-in-out infinite;
}
@keyframes ws-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(99,102,241,0.7); }
  50%       { box-shadow: 0 0 0 8px rgba(99,102,241,0); }
}
.fade-ws-enter-active, .fade-ws-leave-active { transition: opacity 0.35s, transform 0.35s; }
.fade-ws-enter-from, .fade-ws-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
