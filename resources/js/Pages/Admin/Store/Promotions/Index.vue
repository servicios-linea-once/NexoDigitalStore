<template>
  <DashboardLayout title="Promociones" active="promotions">
    <PageHeader
      title="Mis Promociones"
      subtitle="Gestiona descuentos y códigos de cupón para tus productos"
      icon="pi-tag"
      :breadcrumb="[{ label: 'Vendedor' }, { label: 'Promociones' }]"
    >
      <template #actions>
        <Button label="Nueva promoción" icon="pi pi-plus" @click="$inertia.visit(route('admin.store.promotions.create'))" />
      </template>
    </PageHeader>

    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <SearchFilter v-model="search" placeholder="Buscar por código o nombre..." @search="applySearch" class="toolbar-search" />
          <Select v-model="filterStatus" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Todos" showClear @change="applySearch" style="min-width:150px" />
          <span class="results-badge">{{ promotions.total }} promociones</span>
        </div>
      </template>

      <DataTable :value="promotions.data" :loading="loading" size="small" :rowHover="true" dataKey="id">
        <!-- Code -->
        <Column header="Código" style="min-width:130px">
          <template #body="{ data }">
            <div class="code-cell">
              <code class="promo-code">{{ data.code }}</code>
              <Button icon="pi pi-copy" text size="small" @click="copyCode(data.code)" v-tooltip.top="'Copiar'" />
            </div>
          </template>
        </Column>

        <!-- Name -->
        <Column header="Nombre" style="min-width:180px">
          <template #body="{ data }"><span class="cell-bold">{{ data.name }}</span></template>
        </Column>

        <!-- Discount -->
        <Column header="Descuento" style="width:120px">
          <template #body="{ data }">
            <span class="discount-badge">
              {{ data.type === 'percent' ? `${data.value}%` : `$${Number(data.value).toFixed(2)}` }}
            </span>
          </template>
        </Column>

        <!-- Uses -->
        <Column header="Usos" style="width:110px">
          <template #body="{ data }">
            <div class="uses-cell">
              <ProgressBar :value="usesPct(data)" class="uses-bar" :showValue="false" v-tooltip.top="`${data.uses_count}/${data.max_uses ?? '∞'} usos`" />
              <span class="uses-text">{{ data.uses_count }}/{{ data.max_uses ?? '∞' }}</span>
            </div>
          </template>
        </Column>

        <!-- Validity -->
        <Column header="Validez" style="width:160px">
          <template #body="{ data }">
            <div class="date-range">
              <span class="date-text">{{ fmtDate(data.starts_at) }}</span>
              <span class="date-sep">→</span>
              <span class="date-text" :class="{ 'date-expired': isExpired(data) }">{{ data.expires_at ? fmtDate(data.expires_at) : '∞' }}</span>
            </div>
          </template>
        </Column>

        <!-- Status -->
        <Column header="Estado" style="width:110px">
          <template #body="{ data }">
            <Tag :value="promoStatusLabel(data)" :severity="promoStatusSeverity(data)" size="small" rounded />
          </template>
        </Column>

        <!-- Actions -->
        <Column header="Acciones" style="width:100px">
          <template #body="{ data }">
            <div class="cell-actions">
              <Button icon="pi pi-pencil" size="small" text severity="info" v-tooltip.top="'Editar'" @click="$inertia.visit(route('admin.store.promotions.edit', data.id))" />
              <Button icon="pi pi-trash"  size="small" text severity="danger" v-tooltip.top="'Eliminar'" @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>

        <template #empty>
          <EmptyState icon="pi-tag" title="Sin promociones" description="Crea tu primera promoción para atraer más compradores." action-label="Nueva promoción" @action="$inertia.visit(route('admin.store.promotions.create'))" />
        </template>
        <template #loading><TableSkeleton :rows="8" :cols="7" /></template>
      </DataTable>

      <template #footer>
        <Paginator :rows="promotions.per_page" :totalRecords="promotions.total" :first="(promotions.current_page-1)*promotions.per_page" @page="goPage($event.page+1)" />
      </template>
    </DataCard>

    <ConfirmDialog />
    <Toast />
  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router }        from '@inertiajs/vue3';
import { useConfirm }    from 'primevue/useconfirm';
import { useToast }      from 'primevue/usetoast';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader      from '@/Components/ui/PageHeader.vue';
import DataCard        from '@/Components/ui/DataCard.vue';
import EmptyState      from '@/Components/ui/EmptyState.vue';
import SearchFilter    from '@/Components/ui/SearchFilter.vue';
import TableSkeleton   from '@/Components/ui/TableSkeleton.vue';

const props = defineProps({
  promotions: { type: Object, required: true },
  filters:    { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const toast   = useToast();
const loading      = ref(false);
const search       = ref(props.filters.search || '');
const filterStatus = ref(props.filters.status || null);

const statusOptions = [
  { label: 'Activas',   value: 'active'   },
  { label: 'Expiradas', value: 'expired'  },
  { label: 'Inactivas', value: 'inactive' },
];

function applySearch() {
  loading.value = true;
  const params = Object.fromEntries(Object.entries({ search: search.value, status: filterStatus.value }).filter(([,v]) => v));
  router.get(route('admin.store.promotions.index'), params, { preserveState: true, replace: true, onFinish: () => { loading.value = false; } });
}
function goPage(page) { router.get(route('admin.store.promotions.index'), { search: search.value, status: filterStatus.value, page }, { preserveState: true }); }

async function copyCode(code) {
  await navigator.clipboard.writeText(code);
  toast.add({ severity: 'success', summary: 'Código copiado', life: 2000 });
}

function confirmDelete(promo) {
  confirm.require({
    header: '¿Eliminar promoción?', message: `¿Eliminar el código "${promo.code}"?`,
    icon: 'pi pi-exclamation-triangle', acceptLabel: 'Sí, eliminar', rejectLabel: 'Cancelar', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('admin.store.promotions.destroy', promo.id), {
      onSuccess: () => toast.add({ severity: 'success', summary: 'Promoción eliminada', life: 3000 }),
    }),
  });
}

function fmtDate(d)  { return d ? new Date(d).toLocaleDateString('es-PE', { day:'2-digit', month:'short' }) : '—'; }
function isExpired(p){ return p.expires_at && new Date(p.expires_at) < new Date(); }
function usesPct(p)  { return p.max_uses ? Math.min(Math.round(p.uses_count / p.max_uses * 100), 100) : 0; }
const promoStatusLabel = (p) => {
  if (isExpired(p)) return 'Expirada';
  if (!p.is_active) return 'Inactiva';
  return 'Activa';
};
const promoStatusSeverity = (p) => {
  if (isExpired(p)) return 'secondary';
  if (!p.is_active) return 'danger';
  return 'success';
};
</script>

<style scoped>
.toolbar-row { display:flex; align-items:center; gap:0.75rem; padding:0.875rem 1.125rem; flex-wrap:wrap; }
.toolbar-search { flex:1; min-width:220px; }
.results-badge  { background:var(--c-primary-muted); color:var(--c-primary); font-size:0.78rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:8px; margin-left:auto; }
.code-cell  { display:flex; align-items:center; gap:0.25rem; }
.promo-code { font-family:monospace; font-size:0.85rem; font-weight:700; color:var(--c-primary); background:var(--c-primary-muted); padding:0.2rem 0.5rem; border-radius:6px; }
.cell-bold  { font-size:0.85rem; font-weight:600; color:var(--c-text); }
.discount-badge { font-size:0.9rem; font-weight:800; color:var(--p-green-400); }
.uses-cell  { display:flex; flex-direction:column; gap:0.2rem; }
.uses-bar   { height:5px!important; }
.uses-text  { font-size:0.72rem; color:var(--c-text-muted); }
.date-range { display:flex; align-items:center; gap:0.3rem; font-size:0.78rem; }
.date-text  { color:var(--c-text-muted); }
.date-sep   { color:var(--c-text-subtle); }
.date-expired { color:var(--p-red-400)!important; }
.cell-actions { display:flex; gap:0.1rem; }
</style>
