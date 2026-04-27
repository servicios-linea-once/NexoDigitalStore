<template>
  <AppLayout>
    <Head title="Mis Licencias — Nexo Digital Store" />
    <div class="page-wrap">
      <PageHeader title="Mis Licencias" subtitle="Claves y licencias de tus compras" icon="pi-key"
        :breadcrumb="[{ label:'Mi cuenta' }, { label:'Licencias' }]" />

      <DataCard :noPadding="true">
        <template #header>
          <div class="toolbar-row">
            <SearchFilter v-model="search" placeholder="Buscar producto..." @search="apply" class="toolbar-search" />
            <span class="results-badge">{{ licenses.total }} licencias</span>
          </div>
        </template>

        <DataTable :value="licenses.data" size="small" :rowHover="true">
          <Column header="Producto" style="min-width:200px">
            <template #body="{ data }">
              <div class="prod-cell">
                <img v-if="data.product?.cover_image" :src="data.product.cover_image" class="prod-thumb" :alt="data.product?.name" />
                <div v-else class="prod-thumb-ph"><i class="pi pi-box" /></div>
                <div>
                  <p class="cell-name">{{ data.product?.name ?? '—' }}</p>
                  <Chip v-if="data.product?.platform" :label="data.product.platform" class="chip-xs" />
                </div>
              </div>
            </template>
          </Column>
          <Column header="Clave / Licencia" style="min-width:200px">
            <template #body="{ data }">
              <div class="key-cell">
                <InputText :value="revealed[data.id] ? data.key_value : masked(data.key_value)" readonly size="small" class="key-input" />
                <Button :icon="revealed[data.id] ? 'pi pi-eye-slash' : 'pi pi-eye'" size="small" text v-tooltip.top="revealed[data.id]?'Ocultar':'Revelar'" @click="toggle(data.id)" />
                <Button icon="pi pi-copy" size="small" text v-tooltip.top="'Copiar'" @click="copy(data.key_value, data.id)" />
              </div>
            </template>
          </Column>
          <Column header="Estado" style="width:110px">
            <template #body="{ data }"><StatusBadge :status="data.status ?? 'active'" /></template>
          </Column>
          <Column header="Activaciones" style="width:120px">
            <template #body="{ data }">
              <ProgressBar :value="actPct(data)" class="act-bar" :showValue="false" v-tooltip.top="`${data.activations_used}/${data.max_activations} activaciones`" />
              <span class="act-text">{{ data.activations_used }}/{{ data.max_activations }}</span>
            </template>
          </Column>
          <Column header="Obtenida" style="width:130px">
            <template #body="{ data }"><span class="date-text">{{ fmtDate(data.created_at) }}</span></template>
          </Column>
          <template #empty><EmptyState icon="pi-key" title="Sin licencias" description="Aquí aparecerán las claves de tus compras." action-label="Comprar productos" @action="$inertia.visit(route('products.index'))" /></template>
          <template #loading><TableSkeleton :rows="6" :cols="5" /></template>
        </DataTable>

        <template #footer>
          <Paginator :rows="licenses.per_page" :totalRecords="licenses.total" :first="(licenses.current_page-1)*licenses.per_page" @page="goPage($event.page+1)" />
        </template>
      </DataCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout    from '@/Layouts/AppLayout.vue';
import PageHeader   from '@/Components/ui/PageHeader.vue';
import DataCard     from '@/Components/ui/DataCard.vue';
import EmptyState   from '@/Components/ui/EmptyState.vue';
import SearchFilter from '@/Components/ui/SearchFilter.vue';
import StatusBadge  from '@/Components/ui/StatusBadge.vue';
import TableSkeleton from '@/Components/ui/TableSkeleton.vue';

const props = defineProps({ licenses: Object, filters: { type:Object, default:()=>({}) } });
const toast    = useToast();
const search   = ref(props.filters.search||'');
const revealed = reactive({});

function masked(v){ if(!v) return ''; const l=v.length; return v.slice(0,4)+'*'.repeat(Math.max(l-8,4))+v.slice(-4); }
function toggle(id){ revealed[id] = !revealed[id]; }
async function copy(val, id){
  try { await navigator.clipboard.writeText(val); toast.add({severity:'success',summary:'Copiado al portapapeles',life:2000}); }
  catch { toast.add({severity:'error',summary:'Error al copiar',life:2000}); }
}
function actPct(l){ return l.max_activations ? Math.round(l.activations_used/l.max_activations*100) : 0; }
function fmtDate(d){ return d ? new Date(d).toLocaleDateString('es-PE',{day:'2-digit',month:'short',year:'numeric'}) : '—'; }
function apply(){ router.get(route('licenses.index'),{search:search.value},{preserveState:true,replace:true}); }
function goPage(pg){ router.get(route('licenses.index'),{search:search.value,page:pg},{preserveState:true}); }
</script>

<style scoped>
.page-wrap   { max-width:1000px; margin:0 auto; padding-bottom:3rem; }
.toolbar-row { display:flex; align-items:center; gap:0.75rem; padding:0.875rem 1.125rem; flex-wrap:wrap; }
.toolbar-search{ flex:1; min-width:240px; }
.results-badge{ margin-left:auto; background:var(--c-primary-muted); color:var(--c-primary); font-size:0.78rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:8px; }
.prod-cell   { display:flex; align-items:center; gap:0.625rem; }
.prod-thumb  { width:48px; height:36px; border-radius:6px; object-fit:cover; flex-shrink:0; }
.prod-thumb-ph{ width:48px; height:36px; border-radius:6px; background:var(--c-card); display:flex; align-items:center; justify-content:center; color:var(--c-text-subtle); flex-shrink:0; }
.cell-name   { font-size:0.85rem; font-weight:600; color:var(--c-text); margin:0 0 0.2rem; }
:deep(.chip-xs .p-chip){ padding:0.1rem 0.4rem !important; font-size:0.65rem !important; }
.key-cell    { display:flex; align-items:center; gap:0.25rem; }
.key-input   { font-family:monospace; font-size:0.78rem; flex:1; min-width:120px; }
.act-bar     { height:6px !important; margin-bottom:0.2rem; }
.act-text    { font-size:0.72rem; color:var(--c-text-muted); }
.date-text   { font-size:0.78rem; color:var(--c-text-muted); }
</style>
