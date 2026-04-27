<template>
  <DashboardLayout title="Logs de Auditoría" active="audit-logs">
    <PageHeader
      title="Logs de Auditoría"
      subtitle="Registro completo de todas las acciones del sistema"
      icon="pi-shield"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Audit Logs' }]"
    >
      <template #actions>
        <span class="results-badge">{{ logs.total }} eventos</span>
      </template>
    </PageHeader>

    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <SearchFilter v-model="search" placeholder="Buscar por acción o usuario..." @search="applySearch" class="toolbar-search" />
          <Select v-model="filterAction" :options="actionOptions" optionLabel="label" optionValue="value" placeholder="Todas las acciones" showClear @change="applySearch" style="min-width:190px" />
          <DatePicker v-model="filterDate" placeholder="Filtrar por fecha" showButtonBar @date-select="applySearch" style="min-width:160px" />
        </div>
      </template>

      <DataTable :value="logs.data" :loading="loading" size="small" :rowHover="true" dataKey="id">
        <!-- Event type -->
        <Column header="Evento" style="min-width:180px">
          <template #body="{ data }">
            <div class="event-cell">
              <div class="event-icon" :class="eventIconClass(data.event)">
                <i :class="`pi ${eventIcon(data.event)}`" />
              </div>
              <span class="event-label">{{ formatEvent(data.event) }}</span>
            </div>
          </template>
        </Column>

        <!-- Actor -->
        <Column header="Usuario" style="min-width:180px">
          <template #body="{ data }">
            <div v-if="data.user" class="cell-user">
              <Avatar :label="initials(data.user)" shape="circle" size="small" />
              <div>
                <p class="cell-name">{{ data.user.name }}</p>
                <p class="cell-email">{{ data.user.email }}</p>
              </div>
            </div>
            <span v-else class="system-badge">Sistema</span>
          </template>
        </Column>

        <!-- Target -->
        <Column header="Afectado" style="min-width:160px">
          <template #body="{ data }">
            <div v-if="data.target_user" class="cell-user">
              <Avatar :label="initials(data.target_user)" shape="circle" size="small" severity="secondary" />
              <div>
                <p class="cell-name">{{ data.target_user.name }}</p>
                <p class="cell-email">{{ data.target_user.email }}</p>
              </div>
            </div>
            <span v-else class="no-target">—</span>
          </template>
        </Column>

        <!-- Meta -->
        <Column header="Detalles" style="min-width:200px">
          <template #body="{ data }">
            <div v-if="data.properties && Object.keys(data.properties).length" class="meta-cell">
              <Tag
                v-for="(val, key) in metaPreview(data.properties)"
                :key="key"
                :value="`${key}: ${val}`"
                severity="secondary"
                size="small"
                class="meta-tag"
              />
            </div>
            <span v-else class="no-target">—</span>
          </template>
        </Column>

        <!-- IP -->
        <Column header="IP" style="width:130px">
          <template #body="{ data }">
            <code class="ip-code">{{ data.ip_address ?? '—' }}</code>
          </template>
        </Column>

        <!-- Date -->
        <Column header="Fecha" style="width:140px">
          <template #body="{ data }">
            <div class="date-cell">
              <span class="date-val">{{ fmtDate(data.created_at) }}</span>
              <span class="time-val">{{ fmtTime(data.created_at) }}</span>
            </div>
          </template>
        </Column>

        <template #empty>
          <EmptyState icon="pi-shield" title="Sin logs" description="No hay registros de auditoría que coincidan." />
        </template>
        <template #loading><TableSkeleton :rows="10" :cols="6" /></template>
      </DataTable>

      <template #footer>
        <Paginator :rows="logs.per_page" :totalRecords="logs.total" :first="(logs.current_page-1)*logs.per_page" @page="goPage($event.page+1)" />
      </template>
    </DataCard>
  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader      from '@/Components/ui/PageHeader.vue';
import DataCard        from '@/Components/ui/DataCard.vue';
import EmptyState      from '@/Components/ui/EmptyState.vue';
import SearchFilter    from '@/Components/ui/SearchFilter.vue';
import TableSkeleton   from '@/Components/ui/TableSkeleton.vue';

const props = defineProps({
  logs:    { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const loading      = ref(false);
const search       = ref(props.filters.search || '');
const filterAction = ref(props.filters.action || null);
const filterDate   = ref(props.filters.date ? new Date(props.filters.date) : null);

const actionOptions = [
  { label: 'Login exitoso',    value: 'login_success'       },
  { label: 'Login fallido',    value: 'login_failed'        },
  { label: 'Registro',         value: 'registered'          },
  { label: 'Cambio de rol',    value: 'admin_role_changed'  },
  { label: 'Usuario desactivado', value: 'admin_user_toggled' },
  { label: 'Reembolso',        value: 'admin_order_refunded'},
  { label: 'Categoría creada', value: 'admin_category_created' },
  { label: 'Categoría eliminada', value: 'admin_category_deleted' },
  { label: 'Logout',           value: 'logout'              },
];

function applySearch() {
  loading.value = true;
  const params = { search: search.value, action: filterAction.value };
  if (filterDate.value) params.date = filterDate.value.toISOString().slice(0, 10);
  const cleanParams = Object.fromEntries(Object.entries(params).filter(([,v]) => v));
  router.get(route('admin.audit-logs'), cleanParams, { preserveState: true, replace: true, onFinish: () => { loading.value = false; } });
}
function goPage(page) {
  const params = { search: search.value, action: filterAction.value, page };
  if (filterDate.value) params.date = filterDate.value.toISOString().slice(0, 10);
  router.get(route('admin.audit-logs'), params, { preserveState: true });
}

function initials(u) { return (u?.name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2); }
function fmtDate(d)  { return d ? new Date(d).toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' }) : '—'; }
function fmtTime(d)  { return d ? new Date(d).toLocaleTimeString('es-PE', { hour:'2-digit', minute:'2-digit' }) : ''; }

function formatEvent(ev) {
  const map = {
    login_success: 'Login exitoso', login_failed: 'Login fallido', logout: 'Logout',
    registered: 'Registro', registered_oauth: 'Registro OAuth', login_oauth: 'Login OAuth',
    admin_role_changed: 'Cambio de rol', admin_user_toggled: 'Toggle usuario',
    admin_order_refunded: 'Reembolso', admin_category_created: 'Cat. creada',
    admin_category_updated: 'Cat. actualizada', admin_category_deleted: 'Cat. eliminada',
  };
  return map[ev] ?? ev?.replace(/_/g, ' ');
}
function eventIcon(ev) {
  if (ev?.includes('login')) return 'pi-sign-in';
  if (ev?.includes('logout')) return 'pi-sign-out';
  if (ev?.includes('register')) return 'pi-user-plus';
  if (ev?.includes('role')) return 'pi-shield';
  if (ev?.includes('order') || ev?.includes('refund')) return 'pi-shopping-bag';
  if (ev?.includes('category')) return 'pi-folder';
  if (ev?.includes('user')) return 'pi-user';
  return 'pi-info-circle';
}
function eventIconClass(ev) {
  if (ev?.includes('failed') || ev?.includes('delete') || ev?.includes('refund')) return 'icon-danger';
  if (ev?.includes('login_success') || ev?.includes('register')) return 'icon-success';
  if (ev?.includes('role') || ev?.includes('toggle')) return 'icon-warn';
  return 'icon-info';
}
function metaPreview(props) {
  const exclude = ['ip', 'user_agent'];
  return Object.fromEntries(
    Object.entries(props)
      .filter(([k]) => !exclude.includes(k))
      .slice(0, 3)
      .map(([k, v]) => [k.replace(/_/g, ' '), String(v).slice(0, 40)])
  );
}
</script>

<style scoped>
.results-badge  { background:var(--c-primary-muted); color:var(--c-primary); font-size:0.78rem; font-weight:700; padding:0.25rem 0.75rem; border-radius:8px; }
.toolbar-row    { display:flex; align-items:center; gap:0.75rem; padding:0.875rem 1.125rem; flex-wrap:wrap; }
.toolbar-search { flex:1; min-width:220px; }
.event-cell     { display:flex; align-items:center; gap:0.625rem; }
.event-icon     { width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:0.75rem; flex-shrink:0; }
.icon-danger    { background:rgba(239,68,68,0.1);    color:#ef4444; }
.icon-success   { background:rgba(16,185,129,0.1);   color:#10b981; }
.icon-warn      { background:rgba(245,158,11,0.1);   color:#f59e0b; }
.icon-info      { background:rgba(99,102,241,0.1);   color:var(--c-primary); }
.event-label    { font-size:0.82rem; font-weight:600; color:var(--c-text); }
.cell-user      { display:flex; align-items:center; gap:0.5rem; }
.cell-name      { font-size:0.82rem; font-weight:600; color:var(--c-text); margin:0; }
.cell-email     { font-size:0.7rem; color:var(--c-text-muted); margin:0; }
.system-badge   { font-size:0.72rem; color:var(--c-primary); background:var(--c-primary-muted); padding:0.15rem 0.5rem; border-radius:6px; }
.no-target      { color:var(--c-text-subtle); font-size:0.82rem; }
.meta-cell      { display:flex; flex-wrap:wrap; gap:0.25rem; max-width:260px; }
:deep(.meta-tag .p-tag) { font-size:0.65rem !important; }
.ip-code        { font-family:monospace; font-size:0.75rem; color:var(--c-text-muted); background:var(--c-card); padding:0.15rem 0.4rem; border-radius:5px; }
.date-cell      { display:flex; flex-direction:column; }
.date-val       { font-size:0.78rem; color:var(--c-text-muted); }
.time-val       { font-size:0.72rem; color:var(--c-text-subtle); }
</style>
