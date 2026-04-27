<template>
  <AppLayout>
    <Head title="Mis Pedidos — Nexo Digital Store" />
    <div class="page-wrap">
      <PageHeader title="Mis Pedidos" subtitle="Historial completo de compras" icon="pi-shopping-bag"
        :breadcrumb="[{ label:'Mi cuenta' }, { label:'Pedidos' }]" />

      <DataCard :noPadding="true">
        <template #header>
          <div class="toolbar-row">
            <Select v-model="status" :options="statusOpts" optionLabel="label" optionValue="value" placeholder="Todos los estados" showClear @change="apply" />
            <span class="results-badge">{{ orders.total }} pedidos</span>
          </div>
        </template>

        <DataTable :value="orders.data" size="small" :rowHover="true" @row-click="goDetail">
          <Column header="Pedido" style="min-width:140px">
            <template #body="{ data }"><span class="mono">#{{ data.ulid?.slice(-8).toUpperCase() }}</span></template>
          </Column>
          <Column header="Productos">
            <template #body="{ data }">
              <div class="cell-products">
                <span class="prod-name">{{ data.items?.[0]?.product?.name ?? '—' }}</span>
                <Tag v-if="data.items?.length > 1" :value="`+${data.items.length-1} más`" severity="secondary" rounded size="small" />
              </div>
            </template>
          </Column>
          <Column header="Total" style="width:100px">
            <template #body="{ data }"><strong>${{ fmt(data.total_amount) }}</strong></template>
          </Column>
          <Column header="Estado" style="width:120px">
            <template #body="{ data }"><StatusBadge :status="data.status" /></template>
          </Column>
          <Column header="Fecha" style="width:130px">
            <template #body="{ data }"><span class="date-text">{{ fmtDate(data.created_at) }}</span></template>
          </Column>
          <Column style="width:50px">
            <template #body><i class="pi pi-chevron-right row-arrow" /></template>
          </Column>
          <template #empty><EmptyState icon="pi-shopping-bag" title="Sin pedidos" description="Aún no has realizado ninguna compra." action-label="Explorar productos" @action="$inertia.visit(route('products.index'))" /></template>
        </DataTable>

        <template #footer>
          <Paginator :rows="orders.per_page" :totalRecords="orders.total" :first="(orders.current_page-1)*orders.per_page" @page="goPage($event.page+1)" />
        </template>
      </DataCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout   from '@/Layouts/AppLayout.vue';
import PageHeader  from '@/Components/ui/PageHeader.vue';
import DataCard    from '@/Components/ui/DataCard.vue';
import EmptyState  from '@/Components/ui/EmptyState.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';

const props = defineProps({ orders: Object, filters: { type:Object, default:()=>({}) } });
const statusOpts = [
  { label:'Completado',  value:'completed' }, { label:'Pendiente',   value:'pending' },
  { label:'Procesando',  value:'processing' }, { label:'Reembolsado', value:'refunded' },
];
const status = ref(props.filters.status || null);
function fmt(v){ return parseFloat(v||0).toFixed(2); }
function fmtDate(d){ return d ? new Date(d).toLocaleDateString('es-PE',{day:'2-digit',month:'short',year:'numeric'}) : '—'; }
function apply(){ router.get(route('orders.index'),status.value ? {status:status.value} : {},{preserveState:true,replace:true}); }
function goPage(pg){ router.get(route('orders.index'),{status:status.value,page:pg},{preserveState:true}); }
function goDetail(e){ router.visit(route('orders.show', e.data.ulid)); }
</script>

<style scoped>
.page-wrap  { max-width:1000px; margin:0 auto; padding-bottom:3rem; }
.toolbar-row{ display:flex; align-items:center; gap:0.75rem; padding:0.875rem 1.125rem; flex-wrap:wrap; }
.results-badge{ margin-left:auto; background:var(--c-primary-muted); color:var(--c-primary); font-size:0.78rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:8px; }
.mono       { font-family:monospace; font-size:0.82rem; color:var(--c-text-muted); font-weight:600; }
.cell-products{ display:flex; align-items:center; gap:0.5rem; }
.prod-name  { font-size:0.83rem; color:var(--c-text); font-weight:500; }
.date-text  { font-size:0.78rem; color:var(--c-text-muted); }
.row-arrow  { color:var(--c-text-subtle); font-size:0.75rem; }
:deep(.p-datatable-tbody > tr){ cursor:pointer; }
</style>
