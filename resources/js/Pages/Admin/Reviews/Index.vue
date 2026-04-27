<template>
  <DashboardLayout title="Reseñas" active="reviews">
    <PageHeader
      title="Moderación de Reseñas"
      subtitle="Revisa y modera las opiniones del marketplace"
      icon="pi-star"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Reseñas' }]"
    >
      <template #actions>
        <span class="results-badge">{{ reviews.total }} reseñas</span>
      </template>
    </PageHeader>

    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <SearchFilter v-model="search" placeholder="Buscar por producto o usuario..." @search="applySearch" class="toolbar-search" />
          <Select v-model="filterStatus" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Todos los estados" showClear @change="applySearch" style="min-width:160px" />
          <Select v-model="filterRating" :options="ratingOptions" optionLabel="label" optionValue="value" placeholder="Todas las estrellas" showClear @change="applySearch" style="min-width:160px" />
        </div>
      </template>

      <DataTable :value="reviews.data" :loading="loading" size="small" :rowHover="true" dataKey="id">
        <!-- Rating -->
        <Column header="Calificación" style="width:130px">
          <template #body="{ data }">
            <Rating :modelValue="data.rating" :cancel="false" readonly />
          </template>
        </Column>

        <!-- Product -->
        <Column header="Producto" style="min-width:180px">
          <template #body="{ data }">
            <p class="cell-bold">{{ data.product?.name ?? '—' }}</p>
          </template>
        </Column>

        <!-- Reviewer -->
        <Column header="Usuario" style="min-width:150px">
          <template #body="{ data }">
            <div class="cell-user">
              <Avatar :label="initials(data.user)" shape="circle" size="small" />
              <span class="cell-name">{{ data.user?.name }}</span>
            </div>
          </template>
        </Column>

        <!-- Comment -->
        <Column header="Comentario" style="min-width:220px">
          <template #body="{ data }">
            <p class="comment-text">{{ data.comment || '—' }}</p>
          </template>
        </Column>

        <!-- Status -->
        <Column header="Estado" style="width:110px">
          <template #body="{ data }">
            <Tag :value="reviewStatusLabel(data.status)" :severity="reviewStatusSeverity(data.status)" size="small" rounded />
          </template>
        </Column>

        <!-- Date -->
        <Column header="Fecha" style="width:120px">
          <template #body="{ data }">
            <span class="date-text">{{ fmtDate(data.created_at) }}</span>
          </template>
        </Column>

        <!-- Actions -->
        <Column header="Acciones" style="width:130px">
          <template #body="{ data }">
            <div class="cell-actions">
              <Button
                v-if="can('reviews.approve') && data.status !== 'approved'"
                icon="pi pi-check"
                size="small" text severity="success"
                v-tooltip.top="'Aprobar'"
                :loading="actioning[data.id+'-approve']"
                @click="action(data, 'approve')"
              />
              <Button
                v-if="can('reviews.flag') && data.status !== 'flagged'"
                icon="pi pi-flag"
                size="small" text severity="warn"
                v-tooltip.top="'Marcar'"
                :loading="actioning[data.id+'-flag']"
                @click="action(data, 'flag')"
              />
              <Button
                v-if="can('reviews.reject') && data.status !== 'rejected'"
                icon="pi pi-times"
                size="small" text severity="danger"
                v-tooltip.top="'Rechazar'"
                :loading="actioning[data.id+'-reject']"
                @click="action(data, 'reject')"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <EmptyState icon="pi-star" title="Sin reseñas" description="No hay reseñas que coincidan con los filtros." />
        </template>
        <template #loading><TableSkeleton :rows="8" :cols="7" /></template>
      </DataTable>

      <template #footer>
        <Paginator :rows="reviews.per_page" :totalRecords="reviews.total" :first="(reviews.current_page-1)*reviews.per_page" @page="goPage($event.page+1)" />
      </template>
    </DataCard>

    <Toast />
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router }        from '@inertiajs/vue3';
import { useToast }      from 'primevue/usetoast';
import { usePermissions }from '@/composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader      from '@/Components/ui/PageHeader.vue';
import DataCard        from '@/Components/ui/DataCard.vue';
import EmptyState      from '@/Components/ui/EmptyState.vue';
import SearchFilter    from '@/Components/ui/SearchFilter.vue';
import TableSkeleton   from '@/Components/ui/TableSkeleton.vue';

const props = defineProps({
  reviews: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const toast   = useToast();
const { can } = usePermissions();

const loading      = ref(false);
const actioning    = reactive({});
const search       = ref(props.filters.search || '');
const filterStatus = ref(props.filters.status || null);
const filterRating = ref(props.filters.rating || null);

const statusOptions = [
  { label: 'Pendiente', value: 'pending'  },
  { label: 'Aprobada',  value: 'approved' },
  { label: 'Marcada',   value: 'flagged'  },
  { label: 'Rechazada', value: 'rejected' },
];
const ratingOptions = [1, 2, 3, 4, 5].map(r => ({ label: `${r} estrella${r > 1 ? 's' : ''}`, value: r }));

function applySearch() {
  loading.value = true;
  const params = Object.fromEntries(Object.entries({ search: search.value, status: filterStatus.value, rating: filterRating.value }).filter(([,v]) => v));
  router.get(route('admin.reviews.index'), params, { preserveState: true, replace: true, onFinish: () => { loading.value = false; } });
}
function goPage(page) { router.get(route('admin.reviews.index'), { search: search.value, status: filterStatus.value, rating: filterRating.value, page }, { preserveState: true }); }

function action(review, type) {
  const key = `${review.id}-${type}`;
  actioning[key] = true;
  const routes = { approve: 'admin.reviews.approve', flag: 'admin.reviews.flag', reject: 'admin.reviews.reject' };
  router.post(route(routes[type], review.id), {}, {
    onSuccess: () => toast.add({ severity: type === 'approve' ? 'success' : type === 'flag' ? 'warn' : 'info', summary: `Reseña ${type === 'approve' ? 'aprobada' : type === 'flag' ? 'marcada' : 'rechazada'}`, life: 3000 }),
    onError:   () => toast.add({ severity: 'error', summary: 'Error', life: 3000 }),
    onFinish:  () => { actioning[key] = false; },
  });
}

function initials(u) { return (u?.name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2); }
function fmtDate(d)  { return d ? new Date(d).toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' }) : '—'; }
const reviewStatusLabel    = s => ({ pending:'Pendiente', approved:'Aprobada', flagged:'Marcada', rejected:'Rechazada' }[s] ?? s);
const reviewStatusSeverity = s => ({ pending:'warn', approved:'success', flagged:'danger', rejected:'secondary' }[s] ?? 'secondary');
</script>

<style scoped>
.results-badge  { background:var(--c-primary-muted); color:var(--c-primary); font-size:0.78rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:8px; }
.toolbar-row    { display:flex; align-items:center; gap:0.75rem; padding:0.875rem 1.125rem; flex-wrap:wrap; }
.toolbar-search { flex:1; min-width:220px; }
.cell-user      { display:flex; align-items:center; gap:0.5rem; }
.cell-name      { font-size:0.85rem; color:var(--c-text); }
.cell-bold      { font-size:0.85rem; font-weight:600; color:var(--c-text); margin:0; }
.comment-text   { font-size:0.82rem; color:var(--c-text-muted); margin:0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; max-width:280px; }
.date-text      { font-size:0.78rem; color:var(--c-text-muted); }
.cell-actions   { display:flex; gap:0.1rem; align-items:center; }
</style>
