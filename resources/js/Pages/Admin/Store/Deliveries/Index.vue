<template>
  <DashboardLayout title="Entregas Pendientes" active="deliveries">
    <PageHeader
      title="Entregas Manuales"
      subtitle="Gestiona las entregas pendientes de tus productos"
      icon="pi-truck"
      :breadcrumb="[{ label: 'Vendedor' }, { label: 'Entregas' }]"
    >
      <template #actions>
        <span class="results-badge">{{ deliveries?.total ?? 0 }} pendientes</span>
      </template>
    </PageHeader>

    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <SearchFilter v-model="search" placeholder="Buscar por orden o producto..." @search="applySearch" class="toolbar-search" />
        </div>
      </template>

      <DataTable :value="deliveries?.data ?? []" :loading="loading" size="small" :rowHover="true">
        <!-- Order ID -->
        <Column header="Orden" style="width:120px">
          <template #body="{ data }">
            <span class="order-id">#{{ data.order_ulid?.slice(-8)?.toUpperCase() }}</span>
          </template>
        </Column>

        <!-- Product -->
        <Column header="Producto" style="min-width:180px">
          <template #body="{ data }">
            <p class="cell-name">{{ data.product_name }}</p>
          </template>
        </Column>

        <!-- Buyer -->
        <Column header="Comprador" style="min-width:160px">
          <template #body="{ data }">
            <div class="cell-user">
              <Avatar :label="initials(data.buyer)" shape="circle" size="small" />
              <div>
                <p class="cell-name">{{ data.buyer?.name }}</p>
                <p class="cell-email">{{ data.buyer?.email }}</p>
              </div>
            </div>
          </template>
        </Column>

        <!-- Delivery status -->
        <Column header="Estado" style="width:120px">
          <template #body="{ data }">
            <Tag :value="deliveryLabel(data.delivery_status)" :severity="deliverySeverity(data.delivery_status)" rounded size="small" />
          </template>
        </Column>

        <!-- Date -->
        <Column header="Fecha orden" style="width:130px">
          <template #body="{ data }">
            <span class="date-text">{{ fmtDate(data.created_at) }}</span>
          </template>
        </Column>

        <!-- Actions -->
        <Column header="Acciones" style="width:160px">
          <template #body="{ data }">
            <div class="cell-actions">
              <Button
                v-if="data.delivery_status === 'pending'"
                label="Entregar"
                icon="pi pi-send"
                size="small"
                severity="success"
                :loading="delivering[data.id]"
                @click="deliver(data)"
              />
              <Button
                v-if="data.delivery_status === 'failed'"
                label="Reintentar"
                icon="pi pi-refresh"
                size="small"
                severity="warn"
                :loading="retrying[data.id]"
                @click="retry(data)"
              />
              <Tag v-if="data.delivery_status === 'delivered'" value="Entregado" severity="success" rounded size="small" />
            </div>
          </template>
        </Column>

        <template #empty>
          <EmptyState icon="pi-truck" title="Sin entregas pendientes" description="Todas las entregas de tus productos están completadas." />
        </template>
        <template #loading><TableSkeleton :rows="8" :cols="6" /></template>
      </DataTable>

      <template #footer>
        <Paginator
          v-if="deliveries?.data?.length"
          :rows="deliveries.per_page"
          :totalRecords="deliveries.total"
          :first="(deliveries.current_page - 1) * deliveries.per_page"
          @page="goPage($event.page + 1)"
          class="table-paginator"
        />
      </template>
    </DataCard>

    <Toast />
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router }        from '@inertiajs/vue3';
import { useToast }      from 'primevue/usetoast';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader      from '@/Components/ui/PageHeader.vue';
import DataCard        from '@/Components/ui/DataCard.vue';
import EmptyState      from '@/Components/ui/EmptyState.vue';
import SearchFilter    from '@/Components/ui/SearchFilter.vue';
import TableSkeleton   from '@/Components/ui/TableSkeleton.vue';

const props = defineProps({
  deliveries: { type: Object, required: true },
  filters:    { type: Object, default: () => ({}) },
});

const toast      = useToast();
const loading    = ref(false);
const delivering = reactive({});
const retrying   = reactive({});
const search     = ref(props.filters.search || '');

function applySearch() {
  loading.value = true;
  router.get(route('admin.store.deliveries.index'), { search: search.value }, {
    preserveState: true, replace: true,
    onFinish: () => { loading.value = false; },
  });
}
function goPage(page) {
  router.get(route('admin.store.deliveries.index'), { search: search.value, page }, { preserveState: true });
}

function deliver(item) {
  delivering[item.id] = true;
  router.post(route('admin.store.deliveries.deliver', item.order_ulid), {}, {
    onSuccess: () => toast.add({ severity: 'success', summary: 'Entrega realizada', life: 3000 }),
    onError:   () => toast.add({ severity: 'error', summary: 'Error al entregar', life: 3000 }),
    onFinish:  () => { delivering[item.id] = false; },
  });
}

function retry(item) {
  retrying[item.id] = true;
  router.post(route('admin.store.deliveries.retry', item.order_ulid), {}, {
    onSuccess: () => toast.add({ severity: 'success', summary: 'Reintento enviado', life: 3000 }),
    onError:   () => toast.add({ severity: 'error', summary: 'Error al reintentar', life: 3000 }),
    onFinish:  () => { retrying[item.id] = false; },
  });
}

function initials(u) { return (u?.name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2); }
function fmtDate(d)  { return d ? new Date(d).toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' }) : '—'; }
const deliveryLabel    = s => ({ pending:'Pendiente', delivered:'Entregada', failed:'Error', disputed:'Disputa' }[s] ?? s);
const deliverySeverity = s => ({ pending:'warn', delivered:'success', failed:'danger', disputed:'danger' }[s] ?? 'secondary');
</script>

<style scoped>
.results-badge  { background:var(--c-primary-muted); color:var(--c-primary); font-size:0.78rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:8px; }
.toolbar-row    { display:flex; align-items:center; gap:0.75rem; padding:0.875rem 1.125rem; flex-wrap:wrap; }
.toolbar-search { flex:1; min-width:240px; }
.order-id   { font-family:monospace; font-size:0.85rem; font-weight:700; color:var(--c-primary); }
.cell-user  { display:flex; align-items:center; gap:0.625rem; }
.cell-name  { font-size:0.85rem; font-weight:600; color:var(--c-text); margin:0; }
.cell-email { font-size:0.72rem; color:var(--c-text-muted); margin:0; }
.date-text  { font-size:0.78rem; color:var(--c-text-muted); }
.cell-actions { display:flex; gap:0.25rem; align-items:center; }
.table-paginator { border-top:1px solid var(--c-border); }
</style>
