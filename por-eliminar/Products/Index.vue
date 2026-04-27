<template>
  <DashboardLayout title="Productos Admin" active="admin-products">
    <Head title="Productos — Admin" />
    <PageHeader title="Productos" subtitle="Supervisión de todos los productos del marketplace" icon="pi-box"
      :breadcrumb="[{ label:'Admin' }, { label:'Productos' }]">
      <template #actions>
        <span class="results-badge">{{ products.total }} productos</span>
      </template>
    </PageHeader>

    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <SearchFilter v-model="filters.search" placeholder="Buscar producto..." @search="apply" class="toolbar-search" />
          <Select v-model="filters.status" :options="statusOpts" optionLabel="label" optionValue="value" placeholder="Estado" showClear @change="apply" />
          <Select v-model="filters.seller" :options="sellers" optionLabel="name" optionValue="id" placeholder="Vendedor" showClear @change="apply" />
        </div>
      </template>

      <DataTable :value="products.data" size="small" :rowHover="true">
        <Column header="Producto" style="min-width:220px">
          <template #body="{ data }">
            <div class="prod-cell">
              <img v-if="data.cover_image" :src="data.cover_image" class="prod-thumb" :alt="data.name" />
              <div v-else class="prod-thumb-ph"><i class="pi pi-box" /></div>
              <div>
                <p class="cell-name">{{ data.name }}</p>
                <p class="cell-sub">por {{ data.seller?.name }}</p>
              </div>
            </div>
          </template>
        </Column>
        <Column header="Precio" style="width:100px">
          <template #body="{ data }"><strong>${{ fmt(data.base_price) }}</strong></template>
        </Column>
        <Column header="Stock" style="width:80px">
          <template #body="{ data }">
            <Tag :value="String(data.stock_count ?? 0)" :severity="data.stock_count > 5 ? 'success' : data.stock_count > 0 ? 'warn' : 'danger'" rounded size="small" />
          </template>
        </Column>
        <Column header="Ventas" style="width:80px">
          <template #body="{ data }">{{ data.sold_count ?? 0 }}</template>
        </Column>
        <Column header="Estado" style="width:110px">
          <template #body="{ data }"><StatusBadge :status="data.status" /></template>
        </Column>
        <Column header="Acciones" style="width:140px" frozen alignFrozen="right">
          <template #body="{ data }">
            <div class="cell-actions">
              <Button icon="pi pi-external-link" size="small" text severity="secondary" v-tooltip.top="'Ver en tienda'" @click="openProduct(data)" />
              <Button v-if="can('products.edit')"
                :icon="data.status==='active' ? 'pi pi-eye-slash' : 'pi pi-eye'" size="small" text
                :severity="data.status==='active' ? 'warn' : 'success'"
                v-tooltip.top="data.status==='active' ? 'Suspender' : 'Aprobar'"
                @click="toggleStatus(data)"
              />
              <Button v-if="can('products.delete')"
                icon="pi pi-trash" size="small" text severity="danger" v-tooltip.top="'Eliminar'" @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
        <template #empty><EmptyState icon="pi-box" title="Sin productos" description="No hay productos con los filtros aplicados." /></template>
        <template #loading><TableSkeleton :rows="8" :cols="5" /></template>
      </DataTable>

      <template #footer>
        <Paginator :rows="products.per_page" :totalRecords="products.total" :first="(products.current_page-1)*products.per_page" @page="goPage($event.page+1)" />
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

const props = defineProps({ products: Object, sellers: { type:Array, default:()=>[] }, filters: { type:Object, default:()=>({}) } });
const confirm = useConfirm(); const toast = useToast(); const { can } = usePermissions();
const filters = reactive({ search: props.filters.search||'', status: props.filters.status||null, seller: props.filters.seller||null });
const statusOpts = [{ label:'Activo', value:'active' }, { label:'Borrador', value:'draft' }, { label:'Pausado', value:'paused' }];
function fmt(v){ return parseFloat(v||0).toFixed(2); }
function apply(){ const p=Object.fromEntries(Object.entries(filters).filter(([,v])=>v)); router.get(route('admin.products.index'),p,{preserveState:true,replace:true}); }
function goPage(pg){ router.get(route('admin.products.index'),{...Object.fromEntries(Object.entries(filters).filter(([,v])=>v)),page:pg},{preserveState:true}); }
function openProduct(p){ window.open(route('products.show', p.slug),'_blank'); }
function toggleStatus(p){ router.patch(route('admin.products.status', p.ulid),{},{onSuccess:()=>toast.add({severity:'info',summary:'Estado actualizado',life:3000})}); }
function confirmDelete(p){
  confirm.require({ header:'¿Eliminar producto?', message:`Se eliminará "${p.name}" y todas sus claves.`, icon:'pi pi-exclamation-triangle', acceptLabel:'Eliminar', acceptClass:'p-button-danger', rejectLabel:'Cancelar',
    accept:()=>router.delete(route('admin.products.destroy',p.ulid),{onSuccess:()=>toast.add({severity:'success',summary:'Producto eliminado',life:3000})}) });
}
</script>

<style scoped>
.toolbar-row { display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; padding:0.875rem 1.125rem; }
.toolbar-search{ flex:1; min-width:220px; }
.results-badge{ background:var(--c-primary-muted); color:var(--c-primary); font-size:0.78rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:8px; }
.prod-cell { display:flex; align-items:center; gap:0.625rem; }
.prod-thumb{ width:48px; height:36px; border-radius:6px; object-fit:cover; flex-shrink:0; }
.prod-thumb-ph{ width:48px; height:36px; border-radius:6px; background:var(--c-card); display:flex; align-items:center; justify-content:center; color:var(--c-text-subtle); flex-shrink:0; }
.cell-name { font-size:0.85rem; font-weight:600; color:var(--c-text); margin:0; }
.cell-sub  { font-size:0.72rem; color:var(--c-text-muted); margin:0; }
.cell-actions{ display:flex; gap:0.25rem; }
</style>
