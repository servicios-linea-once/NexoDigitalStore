<!-- resources/js/Pages/Profile/Partials/ProfileOrders.vue -->
<template>
  <div class="tab-pane">
    <section class="pcard pcard--no-pad">
      <div class="pcard-header">
        <span class="pcard-icon"><i class="pi pi-shopping-bag" /></span>
        <div>
          <h2 class="pcard-title">Mis pedidos recientes</h2>
          <p class="pcard-sub">Historial de tus últimas 10 compras</p>
        </div>
        <Button label="Ver todos" icon="pi pi-list" size="small" variant="text" class="ml-auto" @click="router.visit(route('orders.index'))" />
      </div>

      <DataTable :value="orders" size="small" :rowHover="true">
        <Column header="Pedido" style="min-width:140px">
          <template #body="{ data }">
            <span class="cell-bold">{{ data.ulid.slice(-8).toUpperCase() }}</span>
          </template>
        </Column>
        <Column header="Producto" style="min-width:200px">
          <template #body="{ data }">
            <div class="product-preview">
              <span class="preview-text">{{ data.preview }}</span>
              <Tag v-if="data.item_count > 1" :value="`+${data.item_count - 1}`" severity="secondary" size="small" />
            </div>
          </template>
        </Column>
        <Column header="Estado" style="width:140px">
          <template #body="{ data }">
            <Tag :value="orderStatus(data.status).label" :severity="orderStatus(data.status).severity" />
          </template>
        </Column>
        <Column header="Total" style="width:120px">
          <template #body="{ data }">
            <span class="cell-price">${{ Number(data.total).toFixed(2) }}</span>
          </template>
        </Column>
        <Column header="Fecha" style="width:140px">
          <template #body="{ data }">
            <span class="date-text">{{ fmtDate(data.created_at) }}</span>
          </template>
        </Column>
        <Column style="width:60px">
          <template #body="{ data }">
            <Button icon="pi pi-eye" text rounded size="small" @click="router.visit(route('orders.show', data.ulid))" />
          </template>
        </Column>
      </DataTable>

      <div v-if="!orders.length" class="empty-state">
        <i class="pi pi-shopping-cart" />
        <p>Aún no has realizado ningún pedido.</p>
        <Button label="Ir a la tienda" icon="pi pi-shopping-bag" @click="router.visit('/')" />
      </div>
    </section>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

defineProps({
  orders: Array
});

function orderStatus(s) {
  return {
    pending:   { label: 'Pendiente',  severity: 'warn' },
    processing:{ label: 'Procesando', severity: 'info' },
    completed: { label: 'Completado', severity: 'success' },
    cancelled: { label: 'Cancelado',  severity: 'danger' },
    refunded:  { label: 'Reembolsado',severity: 'secondary' },
  }[s] || { label: s, severity: 'secondary' };
}

function fmtDate(d) {
  if (!d) return '—';
  const ts = typeof d === 'number' ? d * 1000 : d;
  return new Date(ts).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' });
}
</script>

<style scoped>
.tab-pane { display: flex; flex-direction: column; gap: 1.25rem; }
.pcard {
  border-radius: 14px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.07); transition: border-color 0.2s, box-shadow 0.2s;
}
.pcard--no-pad { padding: 0; overflow: hidden; }
.pcard-header { display: flex; align-items: flex-start; gap: 0.85rem; padding: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.07); }
.pcard-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(139,92,246,0.12); border: 1px solid rgba(139,92,246,0.2); display: flex; align-items: center; justify-content: center; color: #a78bfa; font-size: 0.95rem; flex-shrink: 0; }
.pcard-title { margin: 0 0 0.15rem; font-size: 0.9rem; font-weight: 700; color: var(--p-text-color, #e2e8f0); }
.pcard-sub   { margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.38); }
.ml-auto { margin-left: auto; }

.product-preview { display: flex; align-items: center; gap: 0.5rem; }
.preview-text    { font-size: 0.82rem; font-weight: 500; color: var(--p-text-color, #e2e8f0); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
.cell-bold       { font-family: monospace; font-weight: 700; color: #a78bfa; font-size: 0.85rem; }
.cell-price      { font-weight: 700; color: var(--p-text-color, #e2e8f0); }
.date-text       { font-size: 0.76rem; color: rgba(255,255,255,0.38); }

.empty-state { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 4rem 2rem; text-align: center; }
.empty-state .pi { font-size: 3rem; color: rgba(255,255,255,0.1); }
.empty-state p { margin: 0; color: rgba(255,255,255,0.4); font-size: 0.95rem; }
</style>
