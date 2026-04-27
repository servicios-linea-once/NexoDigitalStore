<template>
  <DashboardLayout title="Órdenes Admin" active="admin-orders">
    <Head title="Órdenes — Admin" />
    <PageHeader title="Órdenes" subtitle="Gestión completa de pedidos del marketplace" icon="pi-shopping-bag"
      :breadcrumb="[{ label:'Admin' }, { label:'Órdenes' }]">
      <template #actions>
        <span class="results-badge">{{ orders.total }} órdenes</span>
      </template>
    </PageHeader>

    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <SearchFilter v-model="filters.search" placeholder="Buscar por ID, comprador..." @search="apply" class="toolbar-search" />
          <Select v-model="filters.status" :options="statusOpts" optionLabel="label" optionValue="value" placeholder="Estado" showClear @change="apply" />
          <Select v-model="filters.period" :options="periodOpts" optionLabel="label" optionValue="value" placeholder="Período" @change="apply" />
        </div>
      </template>

      <DataTable :value="orders.data" size="small" :rowHover="true" @row-click="goDetail">
        <Column header="ID" style="width:110px">
          <template #body="{ data }"><span class="mono">#{{ data.ulid?.slice(-8).toUpperCase() }}</span></template>
        </Column>
        <Column header="Comprador">
          <template #body="{ data }">
            <div class="cell-user">
              <Avatar :label="data.buyer?.name?.[0]" shape="circle" size="small" />
              <div>
                <p class="cell-name">{{ data.buyer?.name }}</p>
                <p class="cell-sub">{{ data.buyer?.email }}</p>
              </div>
            </div>
          </template>
        </Column>
        <Column header="Productos">
          <template #body="{ data }">
            <span class="cell-name">{{ data.items?.[0]?.product?.name ?? '—' }}</span>
            <Tag v-if="data.items?.length > 1" :value="`+${data.items.length-1}`" severity="secondary" size="small" rounded />
          </template>
        </Column>
        <Column header="Total" style="width:100px">
          <template #body="{ data }"><strong>${{ fmt(data.total_amount) }}</strong></template>
        </Column>
        <Column header="Estado" style="width:120px">
          <template #body="{ data }"><StatusBadge :status="data.status" /></template>
        </Column>
        <Column header="Fecha" style="width:120px">
          <template #body="{ data }"><span class="date-text">{{ fmtDate(data.created_at) }}</span></template>
        </Column>
        <Column v-if="can('orders.refund')" style="width:60px">
          <template #body="{ data }">
            <Button v-if="data.status === 'completed'" icon="pi pi-replay" size="small" text severity="warn" v-tooltip.top="'Reembolsar'" @click.stop="confirmRefund(data)" />
          </template>
        </Column>
        <template #empty><EmptyState icon="pi-shopping-bag" title="Sin órdenes" description="No hay órdenes con los filtros seleccionados." /></template>
        <template #loading><TableSkeleton :rows="8" :cols="6" /></template>
      </DataTable>

      <template #footer>
        <Paginator :rows="orders.per_page" :totalRecords="orders.total" :first="(orders.current_page-1)*orders.per_page" @page="goPage($event.page+1)" />
      </template>
    </DataCard>

    <ConfirmDialog /><Toast />
  </DashboardLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast }   from 'primevue/usetoast';
import { usePermissions } from '@/composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader   from '@/Components/ui/PageHeader.vue';
import DataCard     from '@/Components/ui/DataCard.vue';
import EmptyState   from '@/Components/ui/EmptyState.vue';
import SearchFilter from '@/Components/ui/SearchFilter.vue';
import StatusBadge  from '@/Components/ui/StatusBadge.vue';
import TableSkeleton from '@/Components/ui/TableSkeleton.vue';

const props  = defineProps({ orders: Object, filters: { type:Object, default:()=>({}) } });
const confirm = useConfirm(); const toast = useToast(); const { can } = usePermissions();
const filters  = reactive({ search: props.filters.search||'', status: props.filters.status||null, period: props.filters.period||30 });
const statusOpts = [{ label:'Completado', value:'completed' }, { label:'Pendiente', value:'pending' }, { label:'Procesando', value:'processing' }, { label:'Reembolsado', value:'refunded' }];
const periodOpts = [{ label:'Últimos 7 días', value:7 }, { label:'Últimos 30 días', value:30 }, { label:'Últimos 90 días', value:90 }, { label:'Todo', value:0 }];

function fmt(v){ return parseFloat(v||0).toFixed(2); }
function fmtDate(d){ return d ? new Date(d).toLocaleDateString('es-PE',{day:'2-digit',month:'short',year:'numeric'}) : '—'; }
function apply(){ const p=Object.fromEntries(Object.entries(filters).filter(([,v])=>v)); router.get(route('admin.orders.index'),p,{preserveState:true,replace:true}); }
function goPage(pg){ router.get(route('admin.orders.index'),{...Object.fromEntries(Object.entries(filters).filter(([,v])=>v)),page:pg},{preserveState:true}); }
function goDetail(e){ router.visit(route('admin.orders.show', e.data.ulid)); }
function confirmRefund(order){
  confirm.require({ header:'¿Reembolsar orden?', message:`Se reembolsará $${fmt(order.total_amount)} al comprador "${order.buyer?.name}".`, icon:'pi pi-exclamation-triangle', acceptLabel:'Reembolsar', acceptClass:'p-button-warn', rejectLabel:'Cancelar',
    accept:()=>router.post(route('admin.orders.refund', order.ulid),{},{onSuccess:()=>toast.add({severity:'success',summary:'Orden reembolsada',life:3000})}) });
}
</script>

<style scoped>
.toolbar-row{ display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; padding:0.875rem 1.125rem; }
.toolbar-search{ flex:1; min-width:220px; }
.results-badge{ background:var(--c-primary-muted); color:var(--c-primary); font-size:0.78rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:8px; }
.cell-user  { display:flex; align-items:center; gap:0.5rem; }
.cell-name  { font-size:0.83rem; font-weight:600; color:var(--c-text); margin:0; }
.cell-sub   { font-size:0.72rem; color:var(--c-text-muted); margin:0; }
.mono       { font-family:monospace; font-size:0.8rem; color:var(--c-text-muted); font-weight:600; }
.date-text  { font-size:0.78rem; color:var(--c-text-muted); }
:deep(.p-datatable-tbody > tr){ cursor:pointer; }
</style>
