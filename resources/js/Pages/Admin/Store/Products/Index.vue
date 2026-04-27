<template>
  <DashboardLayout title="Productos" active="products">
    <Head title="Mis Productos — Vendedor" />
    <PageHeader title="Mis Productos" subtitle="Gestiona tu catálogo digital" icon="pi-tag"
      :breadcrumb="[{ label:'Vendedor' }, { label:'Productos' }]">
      <template #actions>
        <Button v-if="can('own_products.create')" label="Nuevo producto" icon="pi pi-plus" @click="$inertia.visit(route('admin.store.products.create'))" />
      </template>
    </PageHeader>

    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <SearchFilter v-model="filters.global" placeholder="Buscar producto..." @search="applyFilters" class="toolbar-search" />
          <Select v-model="filters.status" :options="statusOpts" optionLabel="label" optionValue="value" placeholder="Estado" showClear @change="applyFilters" />
          <Select v-model="filters.platform" :options="platforms" placeholder="Plataforma" showClear @change="applyFilters" />
        </div>
      </template>

      <DataTable :value="products.data" :loading="loading" size="small" :rowHover="true" scrollable scrollHeight="calc(100vh - 340px)">
        <Column header="Producto" style="min-width:220px">
          <template #body="{ data }">
            <div class="prod-cell">
              <img v-if="data.cover_image" :src="data.cover_image" class="prod-thumb" :alt="data.name" />
              <div v-else class="prod-thumb-ph"><i class="pi pi-box" /></div>
              <div>
                <p class="cell-name">{{ data.name }}</p>
                <div class="cell-chips">
                  <Chip v-if="data.platform" :label="data.platform" class="chip-xs" />
                  <Chip v-if="data.region"   :label="data.region"   class="chip-xs" />
                </div>
              </div>
            </div>
          </template>
        </Column>
        <Column header="Precio" style="width:100px">
          <template #body="{ data }">
            <div>
              <p class="price-main">{{ formatCurrency(data.price_usd, 'USD') }}</p>
              <p v-if="data.discount_percent" class="price-disc">-{{ data.discount_percent }}%</p>
            </div>
          </template>
        </Column>
        <Column header="Stock" style="width:80px">
          <template #body="{ data }">
            <Tag :value="String(data.stock_count ?? 0)" :severity="data.stock_count > 5 ? 'success' : data.stock_count > 0 ? 'warn' : 'danger'" rounded size="small" />
          </template>
        </Column>
        <Column header="Estado" style="width:110px">
          <template #body="{ data }"><StatusBadge :status="data.status" /></template>
        </Column>
        <Column header="Acciones" style="width:140px" frozen alignFrozen="right">
          <template #body="{ data }">
            <div class="cell-actions">
              <Button v-if="can('own_products.edit')"   icon="pi pi-pencil" size="small" text severity="info"    v-tooltip.top="'Editar'"   @click="$inertia.visit(route('admin.store.products.edit', data.ulid))" />
              <Button v-if="can('own_products.edit')"   :icon="data.status==='active' ? 'pi pi-pause' : 'pi pi-play'" size="small" text :severity="data.status==='active'?'warn':'success'" v-tooltip.top="data.status==='active'?'Pausar':'Activar'" @click="toggleStatus(data)" />
              <Button v-if="can('own_products.delete')" icon="pi pi-trash" size="small" text severity="danger"   v-tooltip.top="'Eliminar'" @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
        <template #empty><EmptyState icon="pi-box" title="Sin productos" description="Crea tu primer producto digital." action-label="Crear producto" @action="$inertia.visit(route('admin.store.products.create'))" /></template>
        <template #loading><TableSkeleton :rows="8" :cols="5" /></template>
      </DataTable>

      <template #footer>
        <Paginator :rows="products.per_page" :totalRecords="products.total" :first="(products.current_page-1)*products.per_page" @page="goPage($event.page+1)" />
      </template>
    </DataCard>
  </DashboardLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast }   from 'primevue/usetoast';
import { usePermissions } from '@/composables/usePermissions';
import { useFilters }     from '@/composables/useFilters';
import { useUtils }       from '@/composables/useUtils';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader   from '@/Components/ui/PageHeader.vue';
import DataCard     from '@/Components/ui/DataCard.vue';
import EmptyState   from '@/Components/ui/EmptyState.vue';
import SearchFilter from '@/Components/ui/SearchFilter.vue';
import StatusBadge  from '@/Components/ui/StatusBadge.vue';
import TableSkeleton from '@/Components/ui/TableSkeleton.vue';

const props = defineProps({ 
  products: { type: Object, required: true }, 
  filters: { type: Object, default: () => ({}) } 
});

const confirm = useConfirm(); 
const toast = useToast(); 
const { can } = usePermissions();
const { formatCurrency } = useUtils();

const { filters, loading, applyFilters, goPage } = useFilters('admin.store.products.index', {
  global:   props.filters.filter?.global || '',
  status:   props.filters.filter?.status || null,
  platform: props.filters.filter?.platform || null
});

const statusOpts = [{ label:'Activo', value:'active' }, { label:'Borrador', value:'draft' }, { label:'Pausado', value:'paused' }];
const platforms  = ['Steam','Epic Games','GOG','PSN','Xbox','Nintendo','Netflix','Spotify'];

function toggleStatus(p){ router.patch(route('admin.store.products.toggle-status',p.ulid),{},{onSuccess:()=>toast.add({severity:'info',summary:'Estado actualizado',life:3000})}); }
function confirmDelete(p){
  confirm.require({ header:'¿Eliminar producto?', message:`Se eliminará "${p.name}" permanentemente.`, icon:'pi pi-exclamation-triangle', acceptLabel:'Eliminar', rejectLabel:'Cancelar', acceptClass:'p-button-danger',
    accept:()=>router.delete(route('admin.store.products.destroy',p.ulid),{onSuccess:()=>toast.add({severity:'success',summary:'Producto eliminado',life:3000})}) });
}
</script>

<style scoped>
.toolbar-row{ display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; padding:0.875rem 1.125rem; }
.toolbar-search{ flex:1; min-width:240px; }
.prod-cell  { display:flex; align-items:center; gap:0.625rem; }
.prod-thumb { width:48px; height:36px; border-radius:6px; object-fit:cover; flex-shrink:0; }
.prod-thumb-ph{ width:48px; height:36px; border-radius:6px; background:var(--c-card); display:flex; align-items:center; justify-content:center; color:var(--c-text-subtle); flex-shrink:0; }
.cell-name  { font-size:0.85rem; font-weight:600; color:var(--c-text); margin:0; }
.cell-chips { display:flex; gap:0.25rem; margin-top:0.2rem; }
:deep(.chip-xs .p-chip){ padding:0.1rem 0.4rem !important; font-size:0.65rem !important; }
.price-main { font-size:0.88rem; font-weight:700; color:var(--c-text); margin:0; }
.price-disc { font-size:0.7rem; color:#ef4444; margin:0; }
.cell-actions{ display:flex; gap:0.25rem; }
</style>
