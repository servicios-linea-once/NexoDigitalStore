<script setup>
import { ref, reactive }  from 'vue';
import { router, Link }    from '@inertiajs/vue3';
import { useConfirm }      from 'primevue/useconfirm';
import { useToast }        from 'primevue/usetoast';
import { usePermissions }  from '@/composables/usePermissions';
import { useFilters }      from '@/composables/useFilters';
import { useUtils }        from '@/composables/useUtils';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader      from '@/Components/ui/PageHeader.vue';
import DataCard        from '@/Components/ui/DataCard.vue';
import EmptyState      from '@/Components/ui/EmptyState.vue';
import SearchFilter    from '@/Components/ui/SearchFilter.vue';
import TableSkeleton   from '@/Components/ui/TableSkeleton.vue';
import StatusBadge     from '@/Components/ui/StatusBadge.vue';

const props = defineProps({
  orders:  { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const toast   = useToast();
const { can } = usePermissions();
const { formatDate, getInitials } = useUtils();

const { filters, loading, applyFilters, goPage } = useFilters('admin.store.orders.index', {
  global: props.filters.filter?.global || '',
  status: props.filters.filter?.status || null,
});

const statusOptions = [
  { label: 'Pendiente',   value: 'pending' },
  { label: 'Procesando',  value: 'processing' },
  { label: 'Completada',  value: 'completed' },
  { label: 'Fallida',     value: 'failed' },
  { label: 'Reembolsada', value: 'refunded' },
];

function deliveryLabel(order) {
  const items = order.items ?? [];
  if (items.every(i => i.delivery_status === 'delivered')) return 'Entregada';
  if (items.some(i => i.delivery_status === 'failed')) return 'Error';
  return 'Pendiente';
}
function deliverySeverity(order) {
  const items = order.items ?? [];
  if (items.every(i => i.delivery_status === 'delivered')) return 'success';
  if (items.some(i => i.delivery_status === 'failed')) return 'danger';
  return 'warn';
}

function confirmRefund(order) {
  confirm.require({
    header:       '¿Reembolsar orden?',
    message:      `Se marcará la orden #${order.ulid.slice(-8).toUpperCase()} como reembolsada. Esta acción no se puede deshacer.`,
    icon:         'pi pi-exclamation-triangle',
    acceptLabel:  'Reembolsar',
    acceptClass:  'p-button-warning',
    rejectLabel:  'Cancelar',
    accept: () => {
      router.post(route('admin.store.orders.refund', order.ulid), {}, {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Orden reembolsada', life: 3000 }),
        onError:   () => toast.add({ severity: 'error',   summary: 'Error al reembolsar', life: 4000 }),
      });
    },
  });
}
</script>

<template>
  <DashboardLayout title="Órdenes" active="orders">
    <PageHeader
      title="Órdenes"
      subtitle="Gestión centralizada de todas las órdenes de la tienda"
      icon="pi-shopping-bag"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Órdenes' }]"
    >
      <template #actions>
        <span class="results-badge">{{ orders.total }} órdenes</span>
      </template>
    </PageHeader>

    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <SearchFilter v-model="filters.global" placeholder="Buscar por ID o comprador..." @search="applyFilters" class="toolbar-search" />
          <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Todos los estados" showClear @change="applyFilters" style="min-width:180px" />
        </div>
      </template>

      <DataTable :value="orders.data" :loading="loading" size="small" :rowHover="true" scrollable scrollHeight="calc(100vh - 340px)">
        <!-- Order ID -->
        <Column header="Orden" style="min-width:120px">
          <template #body="{ data }">
            <span class="order-id">#{{ data.ulid.slice(-8).toUpperCase() }}</span>
          </template>
        </Column>

        <!-- Buyer -->
        <Column header="Comprador" style="min-width:180px">
          <template #body="{ data }">
            <div class="cell-user">
              <Avatar :label="getInitials(data.buyer?.name)" shape="circle" size="small" />
              <div>
                <p class="cell-name">{{ data.buyer?.name ?? '—' }}</p>
                <p class="cell-email">{{ data.buyer?.email }}</p>
              </div>
            </div>
          </template>
        </Column>

        <!-- Products -->
        <Column header="Productos" style="width:180px">
          <template #body="{ data }">
            <div class="product-list">
              <span v-for="item in data.items?.slice(0,2)" :key="item.id" class="product-chip">{{ item.product_name }}</span>
              <span v-if="data.items?.length > 2" class="more-chip">+{{ data.items.length - 2 }} más</span>
            </div>
          </template>
        </Column>

        <!-- Total -->
        <Column header="Total" style="width:110px">
          <template #body="{ data }">
            <span class="cell-money">{{ data.currency }} {{ Number(data.total).toFixed(2) }}</span>
          </template>
        </Column>

        <!-- Mis ganancias -->
        <Column header="Mis ganancias" style="width:120px">
          <template #body="{ data }">
            <span class="cell-money earnings">${{ data.earnings.toFixed(2) }}</span>
          </template>
        </Column>

        <!-- Status -->
        <Column header="Estado" style="width:120px">
          <template #body="{ data }">
            <StatusBadge :status="data.status" />
          </template>
        </Column>

        <!-- Delivery -->
        <Column header="Entrega" style="width:110px">
          <template #body="{ data }">
            <Tag :value="deliveryLabel(data)" :severity="deliverySeverity(data)" rounded size="small" />
          </template>
        </Column>

        <!-- Date -->
        <Column header="Fecha" style="width:130px">
          <template #body="{ data }">
            <span class="date-text">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>

        <!-- Actions -->
        <Column header="" style="width:90px" frozen alignFrozen="right">
          <template #body="{ data }">
            <div style="display:flex;gap:0.25rem">
              <Button icon="pi pi-eye" size="small" text severity="secondary" v-tooltip.top="'Ver detalle'"
                @click="router.visit(route('admin.store.orders.show', data.ulid))" />
              <Button v-if="can('orders.refund') && data.status === 'completed'"
                icon="pi pi-undo" size="small" text severity="warn" v-tooltip.top="'Reembolsar'"
                @click="confirmRefund(data)" />
            </div>
          </template>
        </Column>

        <template #empty>
          <EmptyState icon="pi-shopping-bag" title="Sin órdenes" description="Aún no tienes órdenes de tus productos." />
        </template>
        <template #loading><TableSkeleton :rows="8" :cols="8" /></template>
      </DataTable>

      <template #footer>
        <Paginator
          :rows="orders.per_page"
          :totalRecords="orders.total"
          :first="(orders.current_page - 1) * orders.per_page"
          @page="goPage($event.page + 1)"
          class="table-paginator"
        />
      </template>
    </DataCard>
  </DashboardLayout>
</template>

<style scoped>
.results-badge { background:var(--c-primary-muted); color:var(--c-primary); font-size:0.78rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:8px; }
.toolbar-row   { display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; padding:0.875rem 1.125rem; }
.toolbar-search { flex:1; min-width:240px; }
.order-id     { font-family:monospace; font-size:0.85rem; font-weight:700; color:var(--c-primary); }
.cell-user    { display:flex; align-items:center; gap:0.625rem; }
.cell-name    { font-size:0.85rem; font-weight:600; color:var(--c-text); margin:0; }
.cell-email   { font-size:0.72rem; color:var(--c-text-muted); margin:0; }
.product-list { display:flex; flex-direction:column; gap:0.2rem; }
.product-chip { font-size:0.72rem; background:var(--c-card); color:var(--c-text-muted); padding:0.1rem 0.4rem; border-radius:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px; }
.more-chip    { font-size:0.7rem; color:var(--c-primary); }
.cell-money   { font-size:0.9rem; font-weight:700; color:var(--c-text); }
.earnings     { color:var(--p-green-400)!important; }
.date-text    { font-size:0.78rem; color:var(--c-text-muted); }
.table-paginator { border-top:1px solid var(--c-border); }
</style>
