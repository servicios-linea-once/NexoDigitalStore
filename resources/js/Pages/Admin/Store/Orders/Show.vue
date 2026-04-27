<template>
  <DashboardLayout :title="`Orden #${shortId}`" active="orders">
    <div class="order-header">
      <div>
        <nav class="breadcrumb">
          <span @click="$inertia.visit(route('admin.store.orders.index'))" class="bc-link">Mis Órdenes</span>
          <i class="pi pi-chevron-right bc-sep" />
          <span>#{{ shortId }}</span>
        </nav>
        <h1 class="page-title">Orden <span class="mono">#{{ shortId }}</span></h1>
      </div>
      <Tag :value="statusLabel(order.status)" :severity="statusSeverity(order.status)" :icon="`pi ${statusIcon(order.status)}`" rounded />
    </div>

    <div class="order-grid">
      <!-- Main col -->
      <div class="col-main">
        <!-- Buyer info -->
        <DataCard class="mb-4">
          <template #header><div class="card-head"><i class="pi pi-user" /> Comprador</div></template>
          <div class="buyer-card">
            <Avatar :label="initials(order.buyer)" shape="circle" size="large" />
            <div>
              <p class="buyer-name">{{ order.buyer?.name }}</p>
              <p class="buyer-email">{{ order.buyer?.email }}</p>
              <p class="buyer-date">Compra realizada el {{ fmtDate(order.created_at) }}</p>
            </div>
          </div>
        </DataCard>

        <!-- Products -->
        <DataCard :noPadding="true" class="mb-4">
          <template #header><div class="card-head"><i class="pi pi-shopping-bag" /> Productos ({{ order.items?.length }})</div></template>

          <div v-for="item in order.items" :key="item.id" class="item-row">
            <div class="item-body">
              <p class="item-name">{{ item.product_name }}</p>
              <div class="item-meta">
                <Tag :value="deliveryLabel(item.delivery_status)" :severity="deliverySeverity(item.delivery_status)" size="small" rounded />
                <span v-if="item.delivered_at" class="date-text">Entregado: {{ fmtDate(item.delivered_at) }}</span>
              </div>
            </div>
            <div class="item-earnings">
              <p class="price-val">${{ Number(item.unit_price).toFixed(2) }}</p>
              <p class="earnings-val">+${{ Number(item.seller_earnings).toFixed(2) }}</p>
              <p class="commission-note">Comisión: {{ item.commission_rate }}%</p>
            </div>
          </div>
        </DataCard>
      </div>

      <!-- Sidebar -->
      <div class="col-side">
        <DataCard class="mb-4">
          <template #header><div class="card-head"><i class="pi pi-receipt" /> Resumen</div></template>
          <div class="sum-list">
            <div class="sum-row"><span>Subtotal</span><span>${{ Number(order.subtotal).toFixed(2) }}</span></div>
            <div class="sum-row"><span>Mis ganancias</span><span class="text-green font-bold">${{ totalEarnings.toFixed(2) }}</span></div>
            <div class="sum-row"><span>Comisión plataforma</span><span class="text-muted">-${{ totalCommission.toFixed(2) }}</span></div>
            <Divider />
            <div class="sum-row sum-total"><span>Total orden</span><span>${{ Number(order.total).toFixed(2) }} {{ order.currency }}</span></div>
            <div class="sum-row"><span>Método de pago</span><span>{{ paymentLabel(order.payment_method) }}</span></div>
          </div>
        </DataCard>

        <DataCard>
          <template #header><div class="card-head"><i class="pi pi-info-circle" /> Estado de entrega</div></template>
          <div class="delivery-status-list">
            <div v-for="item in order.items" :key="item.id" class="delivery-row">
              <span class="delivery-name">{{ item.product_name }}</span>
              <Tag :value="deliveryLabel(item.delivery_status)" :severity="deliverySeverity(item.delivery_status)" size="small" rounded />
            </div>
          </div>
        </DataCard>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import DataCard        from '@/Components/ui/DataCard.vue';

const props = defineProps({ order: { type: Object, required: true } });
const shortId = computed(() => props.order.ulid.slice(-8).toUpperCase());

const totalEarnings   = computed(() => (props.order.items ?? []).reduce((s, i) => s + Number(i.seller_earnings ?? 0), 0));
const totalCommission = computed(() => (props.order.items ?? []).reduce((s, i) => s + Number(i.commission_amount ?? 0), 0));

function initials(u) { return (u?.name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2); }
function fmtDate(d)  { return d ? new Date(d).toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' }) : '—'; }
const statusLabel    = s => ({ pending:'Pendiente', completed:'Completada', failed:'Fallida', refunded:'Reembolsada', disputed:'En disputa' }[s] ?? s);
const statusSeverity = s => ({ pending:'warn', completed:'success', failed:'danger', refunded:'secondary', disputed:'danger' }[s] ?? 'secondary');
const statusIcon     = s => ({ pending:'pi-clock', completed:'pi-check-circle', failed:'pi-times-circle', refunded:'pi-refresh', disputed:'pi-exclamation-triangle' }[s] ?? 'pi-info-circle');
const deliveryLabel    = s => ({ pending:'Pendiente', delivered:'Entregada', failed:'Error', disputed:'En disputa' }[s] ?? s);
const deliverySeverity = s => ({ pending:'warn', delivered:'success', failed:'danger', disputed:'danger' }[s] ?? 'secondary');
const paymentLabel     = s => ({ paypal:'PayPal', mercadopago:'MercadoPago', nexotokens:'NexoTokens', mixed:'Mixto' }[s] ?? s);
</script>

<style scoped>
.order-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
.breadcrumb   { display:flex; align-items:center; gap:0.4rem; font-size:0.75rem; color:var(--c-text-muted); margin-bottom:0.35rem; }
.bc-link      { color:var(--c-primary); cursor:pointer; }
.bc-sep       { font-size:0.6rem; }
.page-title   { font-size:1.3rem; font-weight:800; color:var(--c-text); margin:0; }
.mono         { font-family:monospace; }
.order-grid   { display:grid; grid-template-columns:1fr 300px; gap:1.25rem; }
@media (max-width:860px) { .order-grid { grid-template-columns:1fr; } }
.col-main, .col-side { display:flex; flex-direction:column; }
.mb-4 { margin-bottom:1rem; }
.card-head { display:flex; align-items:center; gap:0.5rem; padding:0.875rem 1.125rem; font-size:0.85rem; font-weight:700; color:var(--c-text); }
.buyer-card   { display:flex; align-items:center; gap:1rem; }
.buyer-name   { font-size:1rem; font-weight:700; color:var(--c-text); margin:0; }
.buyer-email  { font-size:0.78rem; color:var(--c-text-muted); margin:0.2rem 0; }
.buyer-date   { font-size:0.75rem; color:var(--c-text-subtle); margin:0; }
.item-row     { display:flex; align-items:flex-start; gap:1rem; padding:1rem 1.125rem; border-bottom:1px solid var(--c-border); }
.item-row:last-child { border-bottom:none; }
.item-body    { flex:1; min-width:0; }
.item-name    { font-size:0.9rem; font-weight:700; color:var(--c-text); margin:0 0 0.4rem; }
.item-meta    { display:flex; align-items:center; gap:0.5rem; }
.item-earnings { text-align:right; flex-shrink:0; }
.price-val     { font-size:0.9rem; font-weight:700; color:var(--c-text); margin:0; }
.earnings-val  { font-size:0.85rem; font-weight:700; color:var(--p-green-400); margin:0.1rem 0 0; }
.commission-note { font-size:0.72rem; color:var(--c-text-muted); margin:0.1rem 0 0; }
.sum-list     { display:flex; flex-direction:column; }
.sum-row      { display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; color:var(--c-text-muted); padding:0.5rem 0; border-bottom:1px solid var(--c-border); }
.sum-row:last-child { border-bottom:none; }
.sum-total    { font-weight:800; color:var(--c-text); }
.font-bold    { font-weight:700; }
.text-green   { color:var(--p-green-400)!important; }
.text-muted   { color:var(--c-text-muted)!important; }
.delivery-status-list { display:flex; flex-direction:column; gap:0.5rem; }
.delivery-row { display:flex; justify-content:space-between; align-items:center; font-size:0.83rem; color:var(--c-text-muted); }
.delivery-name { flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-right:0.5rem; }
.date-text { font-size:0.72rem; color:var(--c-text-subtle); }
</style>
