<template>
  <DashboardLayout :title="`Orden #${shortId}`" active="orders">
    <div class="order-header">
      <div>
        <nav class="breadcrumb">
          <span @click="$inertia.visit(route('admin.orders.index'))" class="bc-link">Órdenes</span>
          <i class="pi pi-chevron-right bc-sep" />
          <span>#{{ shortId }}</span>
        </nav>
        <h1 class="page-title">Orden <span class="mono">#{{ shortId }}</span></h1>
      </div>
      <div class="header-right">
        <Tag :value="statusLabel(order.status)" :severity="statusSeverity(order.status)" rounded />
        <Button
          v-if="can('orders.refund') && order.status === 'completed'"
          label="Reembolsar"
          icon="pi pi-refresh"
          severity="danger"
          outlined
          :loading="refunding"
          @click="confirmRefund"
        />
      </div>
    </div>

    <div class="order-grid">
      <!-- LEFT col -->
      <div class="col-main">
        <!-- Buyer info -->
        <DataCard class="mb-4">
          <template #header><div class="card-head"><i class="pi pi-user" /> Comprador</div></template>
          <div class="buyer-row">
            <Avatar :label="initials(order.buyer)" shape="circle" size="large" />
            <div>
              <p class="buyer-name">{{ order.buyer?.name }}</p>
              <p class="buyer-email">{{ order.buyer?.email }}</p>
            </div>
            <Button icon="pi pi-external-link" text size="small" severity="secondary" v-tooltip.top="'Ver perfil'"
              @click="$inertia.visit(route('admin.users.show', order.buyer?.id))" class="ml-auto" />
          </div>
        </DataCard>

        <!-- Items -->
        <DataCard :noPadding="true" class="mb-4">
          <template #header><div class="card-head"><i class="pi pi-shopping-bag" /> Productos ({{ order.items?.length }})</div></template>
          <DataTable :value="order.items" size="small">
            <Column header="Producto" style="min-width:180px">
              <template #body="{ data }"><span class="cell-bold">{{ data.product_name }}</span></template>
            </Column>
            <Column header="Precio" style="width:100px">
              <template #body="{ data }"><span class="cell-money">${{ fmt(data.unit_price) }}</span></template>
            </Column>
            <Column header="Comisión" style="width:120px">
              <template #body="{ data }">
                <span class="cell-money muted">{{ data.commission_rate }}% (${{ fmt(data.commission_amount) }})</span>
              </template>
            </Column>
            <Column header="Ganancias" style="width:110px">
              <template #body="{ data }"><span class="cell-money success">${{ fmt(data.seller_earnings) }}</span></template>
            </Column>
            <Column header="Cashback" style="width:90px">
              <template #body="{ data }"><span class="cell-money warn">{{ fmt(data.cashback_amount) }} NT</span></template>
            </Column>
            <Column header="Entrega" style="width:110px">
              <template #body="{ data }">
                <Tag :value="deliveryLabel(data.delivery_status)" :severity="deliverySeverity(data.delivery_status)" size="small" rounded />
              </template>
            </Column>
          </DataTable>
        </DataCard>

        <!-- Payment records -->
        <DataCard v-if="order.payments?.length" :noPadding="true">
          <template #header><div class="card-head"><i class="pi pi-credit-card" /> Pagos ({{ order.payments.length }})</div></template>
          <DataTable :value="order.payments" size="small">
            <Column header="Gateway" style="width:120px">
              <template #body="{ data }"><Tag :value="data.gateway" severity="info" size="small" /></template>
            </Column>
            <Column header="Order ID" style="min-width:160px">
              <template #body="{ data }"><code class="mono-code">{{ data.gateway_order_id ?? '—' }}</code></template>
            </Column>
            <Column header="Transaction ID" style="min-width:160px">
              <template #body="{ data }"><code class="mono-code">{{ data.gateway_transaction_id ?? '—' }}</code></template>
            </Column>
            <Column header="Monto" style="width:100px">
              <template #body="{ data }"><span class="cell-money">${{ fmt(data.amount) }} {{ data.currency }}</span></template>
            </Column>
            <Column header="Estado" style="width:100px">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="payStatusSeverity(data.status)" size="small" rounded />
              </template>
            </Column>
            <Column header="Fecha" style="width:130px">
              <template #body="{ data }"><span class="date-text">{{ data.paid_at ? fmtDate(data.paid_at) : '—' }}</span></template>
            </Column>
          </DataTable>
        </DataCard>
      </div>

      <!-- RIGHT col -->
      <div class="col-side">
        <DataCard class="mb-4">
          <template #header><div class="card-head"><i class="pi pi-receipt" /> Totales</div></template>
          <div class="sum-list">
            <div class="sum-row"><span>Subtotal</span><span>${{ fmt(order.subtotal) }}</span></div>
            <div v-if="Number(order.discount_amount) > 0" class="sum-row success-text">
              <span>Descuento</span><span>-${{ fmt(order.discount_amount) }}</span>
            </div>
            <div v-if="Number(order.nexocoins_used) > 0" class="sum-row warn-text">
              <span>NT usados</span><span>-{{ fmt(order.nexocoins_used) }} NT</span>
            </div>
            <Divider />
            <div class="sum-row sum-total"><span>Total USD</span><span>${{ fmt(order.total) }}</span></div>
            <div v-if="order.currency !== 'USD'" class="sum-row">
              <span>Total {{ order.currency }}</span><span>{{ fmt(order.total_in_currency) }} {{ order.currency }}</span>
            </div>
          </div>
        </DataCard>

        <DataCard class="mb-4">
          <template #header><div class="card-head"><i class="pi pi-info-circle" /> Información</div></template>
          <div class="info-list">
            <div class="info-row"><span>Método de pago</span><span>{{ paymentLabel(order.payment_method) }}</span></div>
            <div class="info-row"><span>Moneda</span><span>{{ order.currency }}</span></div>
            <div class="info-row"><span>Referencia</span><code class="mono-code">{{ order.payment_reference?.slice(-12) ?? '—' }}</code></div>
            <div class="info-row"><span>IP</span><code class="mono-code">{{ order.ip_address ?? '—' }}</code></div>
            <div class="info-row"><span>Creada</span><span>{{ fmtDate(order.created_at) }}</span></div>
            <div class="info-row"><span>Pagada</span><span>{{ order.paid_at ? fmtDate(order.paid_at) : '—' }}</span></div>
          </div>
        </DataCard>
      </div>
    </div>

    <ConfirmDialog />
    <Toast />
  </DashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router }        from '@inertiajs/vue3';
import { useConfirm }    from 'primevue/useconfirm';
import { useToast }      from 'primevue/usetoast';
import { usePermissions }from '@/composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import DataCard        from '@/Components/ui/DataCard.vue';

const props  = defineProps({ order: { type: Object, required: true } });
const confirm = useConfirm();
const toast   = useToast();
const { can } = usePermissions();

const refunding = ref(false);
const shortId   = computed(() => props.order.ulid?.slice(-8)?.toUpperCase());

function confirmRefund() {
  confirm.require({
    header: '¿Reembolsar orden?', icon: 'pi pi-exclamation-triangle',
    message: `Esta acción reembolsará la orden #${shortId.value} y notificará al comprador.`,
    acceptLabel: 'Sí, reembolsar', rejectLabel: 'Cancelar', acceptClass: 'p-button-danger',
    accept: () => {
      refunding.value = true;
      router.post(route('admin.orders.refund', props.order.ulid), {}, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Reembolso procesado', life: 3000 }),
        onError:   () => toast.add({ severity: 'error', summary: 'Error al reembolsar', life: 3000 }),
        onFinish:  () => { refunding.value = false; },
      });
    },
  });
}

function initials(u) { return (u?.name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2); }
function fmtDate(d)  { return d ? new Date(d).toLocaleString('es-PE', { dateStyle:'short', timeStyle:'short' }) : '—'; }
function fmt(v)      { return Number(v ?? 0).toFixed(2); }
const statusLabel    = s => ({ pending:'Pendiente', processing:'Procesando', completed:'Completada', failed:'Fallida', refunded:'Reembolsada' }[s] ?? s);
const statusSeverity = s => ({ pending:'warn', processing:'info', completed:'success', failed:'danger', refunded:'secondary' }[s] ?? 'secondary');
const deliveryLabel    = s => ({ pending:'Pendiente', delivered:'Entregada', failed:'Error', disputed:'Disputa' }[s] ?? s);
const deliverySeverity = s => ({ pending:'warn', delivered:'success', failed:'danger', disputed:'danger' }[s] ?? 'secondary');
const payStatusSeverity = s => ({ captured:'success', pending:'warn', failed:'danger', refunded:'secondary' }[s] ?? 'secondary');
const paymentLabel = s => ({ paypal:'PayPal', mercadopago:'MercadoPago', nexotokens:'NexoTokens', mixed:'Mixto' }[s] ?? s);
</script>

<style scoped>
.order-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
.breadcrumb   { display:flex; align-items:center; gap:0.4rem; font-size:0.75rem; color:var(--c-text-muted); margin-bottom:0.35rem; }
.bc-link      { color:var(--c-primary); cursor:pointer; }
.bc-sep       { font-size:0.6rem; }
.page-title   { font-size:1.3rem; font-weight:800; color:var(--c-text); margin:0; }
.mono         { font-family:monospace; }
.header-right { display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; }
.order-grid   { display:grid; grid-template-columns:1fr 280px; gap:1.25rem; }
@media (max-width:900px) { .order-grid { grid-template-columns:1fr; } }
.col-main, .col-side { display:flex; flex-direction:column; }
.mb-4   { margin-bottom:1rem; }
.ml-auto { margin-left:auto; }
.card-head { display:flex; align-items:center; gap:0.5rem; padding:0.875rem 1.125rem; font-size:0.85rem; font-weight:700; color:var(--c-text); }
.buyer-row  { display:flex; align-items:center; gap:1rem; }
.buyer-name { font-size:1rem; font-weight:700; color:var(--c-text); margin:0; }
.buyer-email{ font-size:0.78rem; color:var(--c-text-muted); margin:0; }
.cell-bold  { font-size:0.85rem; font-weight:600; color:var(--c-text); }
.cell-money { font-size:0.85rem; font-weight:600; color:var(--c-text); }
.cell-money.muted   { color:var(--c-text-muted); font-weight:400; }
.cell-money.success { color:var(--p-green-400); }
.cell-money.warn    { color:var(--p-yellow-400); }
.mono-code  { font-family:monospace; font-size:0.75rem; color:var(--c-text-muted); background:var(--c-card); padding:0.15rem 0.4rem; border-radius:5px; }
.date-text  { font-size:0.78rem; color:var(--c-text-muted); }
.sum-list   { display:flex; flex-direction:column; }
.sum-row    { display:flex; justify-content:space-between; font-size:0.85rem; color:var(--c-text-muted); padding:0.5rem 0; border-bottom:1px solid var(--c-border); }
.sum-row:last-child { border-bottom:none; }
.sum-total  { font-weight:800; font-size:0.95rem; color:var(--c-text); }
.success-text { color:var(--p-green-400)!important; }
.warn-text    { color:var(--p-yellow-400)!important; }
.info-list  { display:flex; flex-direction:column; }
.info-row   { display:flex; justify-content:space-between; align-items:center; font-size:0.83rem; color:var(--c-text-muted); padding:0.5rem 0; border-bottom:1px solid var(--c-border); gap:0.5rem; }
.info-row:last-child { border-bottom:none; }
</style>
