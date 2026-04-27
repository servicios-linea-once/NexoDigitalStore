<template>
  <DashboardLayout title="Suscripciones" active="subscriptions">
    <PageHeader
      title="Gestión de Suscripciones"
      subtitle="Asigna y administra los planes de los usuarios"
      icon="pi-crown"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Suscripciones' }]"
    >
      <template #actions>
        <Button v-if="can('subscriptions.assign')" label="Asignar plan" icon="pi pi-plus" @click="openAssign" />
      </template>
    </PageHeader>

    <!-- Stats -->
    <div class="stats-row">
      <StatsCard v-for="stat in stats" :key="stat.label" :label="stat.label" :value="stat.value" :icon="stat.icon" :color="stat.color" />
    </div>

    <DataCard class="mb-4">
      <template #header>
        <div class="card-head"><i class="pi pi-cog" /> Planes disponibles</div>
      </template>
      <DataTable :value="plans" size="small">
        <Column header="Plan" style="min-width:140px">
          <template #body="{ data }">
            <div class="plan-cell">
              <div class="plan-dot" :class="`dot-${data.slug}`" />
              <span class="cell-bold">{{ data.name }}</span>
            </div>
          </template>
        </Column>
        <Column header="Precio USD" style="width:120px">
          <template #body="{ data }">
            <span class="cell-money" v-if="data.price_usd > 0">${{ Number(data.price_usd).toFixed(2) }}/mes</span>
            <span class="cell-free" v-else>Gratis</span>
          </template>
        </Column>
        <Column header="Precio PEN" style="width:110px">
          <template #body="{ data }">
            <span class="cell-nt" v-if="data.price_pen > 0">S/{{ Number(data.price_pen).toFixed(2) }}</span>
            <span class="text-muted" v-else>—</span>
          </template>
        </Column>
        <Column header="Descuento" style="width:100px">
          <template #body="{ data }">
            <Tag :value="`${data.discount_percent}%`" severity="success" size="small" rounded />
          </template>
        </Column>
        <Column header="" style="width:60px">
          <template #body="{ data }">
            <Button v-if="can('subscriptions.assign')" icon="pi pi-pencil" size="small" text severity="info" v-tooltip.top="'Editar'" @click="openEditPlan(data)" />
          </template>
        </Column>
        <Column header="Visible" style="width:90px">
          <template #body="{ data }">
            <div class="visibility-cell">
              <Tag v-if="data.is_active"
                value="Pública" severity="success" size="small" rounded
                v-tooltip.top="'Los usuarios pueden comprar este plan'" />
              <Tag v-else
                value="Solo admin" severity="secondary" size="small" rounded
                v-tooltip.top="'Solo el admin puede asignar este plan'" />
            </div>
          </template>
        </Column>
      </DataTable>
    </DataCard>

    <!-- Active subscriptions -->
    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <div class="card-head-inline"><i class="pi pi-list" /> Suscripciones activas</div>
          <SearchFilter v-model="search" placeholder="Buscar usuario..." @search="applySearch" class="toolbar-search" />
          <Select v-model="filterPlan" :options="planOptions" optionLabel="label" optionValue="value" placeholder="Todos los planes" showClear @change="applySearch" style="min-width:160px" />
        </div>
      </template>

      <DataTable :value="subscriptions.data" :loading="loading" size="small" :rowHover="true">
        <Column header="Usuario" style="min-width:200px">
          <template #body="{ data }">
            <div class="cell-user">
              <Avatar :label="initials(data.user)" shape="circle" size="small" />
              <div>
                <p class="cell-name">{{ data.user?.name }}</p>
                <p class="cell-email">{{ data.user?.email }}</p>
              </div>
            </div>
          </template>
        </Column>
        <Column header="Plan" style="width:120px">
          <template #body="{ data }">
            <div class="plan-cell">
              <div class="plan-dot" :class="`dot-${data.plan?.slug}`" />
              <span>{{ data.plan?.name }}</span>
            </div>
          </template>
        </Column>
        <Column header="Estado" style="width:110px">
          <template #body="{ data }">
            <Tag :value="subStatusLabel(data.status)" :severity="subStatusSeverity(data.status)" size="small" rounded />
          </template>
        </Column>
        <Column header="Inicio" style="width:120px">
          <template #body="{ data }"><span class="date-text">{{ fmtDate(data.starts_at) }}</span></template>
        </Column>
        <Column header="Vencimiento" style="width:130px">
          <template #body="{ data }"><span class="date-text">{{ data.expires_at ? fmtDate(data.expires_at) : 'Sin vencimiento' }}</span></template>
        </Column>
        <Column header="Acciones" style="width:80px">
          <template #body="{ data }">
            <Button
              v-if="can('subscriptions.revoke') && data.status === 'active'"
              icon="pi pi-ban"
              size="small" text severity="danger"
              v-tooltip.top="'Revocar'"
              @click="confirmRevoke(data)"
            />
          </template>
        </Column>
        <template #empty>
          <EmptyState icon="pi-crown" title="Sin suscripciones" description="No hay suscripciones que coincidan." />
        </template>
        <template #loading><TableSkeleton :rows="8" :cols="6" /></template>
      </DataTable>

      <template #footer>
        <Paginator :rows="subscriptions.per_page" :totalRecords="subscriptions.total" :first="(subscriptions.current_page-1)*subscriptions.per_page" @page="goPage($event.page+1)" />
      </template>
    </DataCard>

    <!-- Assign Dialog -->
    <Dialog v-model:visible="assignDialog" modal header="Asignar plan" :style="{ width: '520px' }" :draggable="false">
      <form @submit.prevent="saveAssign" class="dialog-form">
        <div class="field">
          <label class="field-label">Usuario (email)</label>
          <InputText v-model="assignForm.user_search" placeholder="usuario@email.com" fluid />
        </div>

        <div class="field">
          <label class="field-label">Seleccionar plan</label>
          <div class="plan-cards">
            <div v-for="p in plans" :key="p.id"
              :class="['plan-card', { selected: assignForm.plan_id === p.id }]"
              @click="assignForm.plan_id = p.id">
              <div class="plan-card-top">
                <div class="plan-dot" :class="`dot-${p.slug}`" />
                <span class="plan-card-name">{{ p.name }}</span>
                <Tag v-if="p.discount_percent > 0" :value="`-${p.discount_percent}%`" severity="success" size="small" rounded />
              </div>
              <div class="plan-card-prices">
                <span v-if="p.price_usd > 0" class="price-badge price-usd"><i class="pi pi-credit-card" /> ${{ Number(p.price_usd).toFixed(2) }}/mes</span>
                <span v-else class="price-badge price-free">Gratis</span>
                <span v-if="p.price_pen > 0" class="price-badge price-nt"><i class="pi pi-money-bill" /> S/{{ Number(p.price_pen).toFixed(2) }}/mes</span>
              </div>
            </div>
          </div>
        </div>

        <div class="field">
          <label class="field-label">Vencimiento (opcional)</label>
          <DatePicker v-model="assignForm.expires_at" placeholder="Sin vencimiento — plan permanente" showButtonBar fluid />
        </div>

        <Message v-if="assignForm.plan_id" severity="info" :closable="false" size="small">
          Asignación manual — sin cargo. El usuario recibirá el plan directamente.
        </Message>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="assignDialog = false" />
        <Button label="Asignar plan" icon="pi pi-check" :loading="saving" :disabled="!assignForm.plan_id || !assignForm.user_search" @click="saveAssign" />
      </template>
    </Dialog>

    <!-- Edit Plan Dialog -->
    <Dialog v-model:visible="editPlanDialog" modal :header="`Editar plan: ${editingPlan?.name}`" :style="{ width: '400px' }" :draggable="false">
      <form class="dialog-form">
        <div class="field">
          <label class="field-label">Precio USD/mes</label>
          <InputNumber v-model="planForm.price_usd" prefix="$" :minFractionDigits="2" fluid />
        </div>
        <div class="field">
          <label class="field-label">Precio PEN/mes</label>
          <InputNumber v-model="planForm.price_pen" prefix="S/" :minFractionDigits="2" fluid />
        </div>
        <div class="field">
          <label class="field-label">Descuento en compras (%)</label>
          <InputNumber v-model="planForm.discount_percent" suffix="%" :min="0" :max="100" fluid />
        </div>
        <div class="field">
          <label class="field-label">Duración del plan (días)</label>
          <InputNumber v-model="planForm.duration_days" :min="0" suffix=" días" fluid />
          <small class="field-hint">0 = Plan vitalicio (sin vencimiento).</small>
        </div>
        <Divider />
        <div class="field field-row">
          <div class="field-meta">
            <span class="field-label">Visible para compradores</span>
            <small class="field-hint">Si está activo, los usuarios pueden comprar este plan. Si está desactivado, solo el admin puede asignarlo.</small>
          </div>
          <ToggleSwitch v-model="planForm.is_active" />
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="editPlanDialog = false" />
        <Button label="Guardar" icon="pi pi-check" :loading="saving" @click="savePlan" />
      </template>
    </Dialog>

    <ConfirmDialog />
    <Toast />
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router }          from '@inertiajs/vue3';
import { useConfirm }      from 'primevue/useconfirm';
import { useToast }        from 'primevue/usetoast';
import { usePermissions }  from '@/composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader      from '@/Components/ui/PageHeader.vue';
import DataCard        from '@/Components/ui/DataCard.vue';
import EmptyState      from '@/Components/ui/EmptyState.vue';
import SearchFilter    from '@/Components/ui/SearchFilter.vue';
import TableSkeleton   from '@/Components/ui/TableSkeleton.vue';
import StatsCard       from '@/Components/ui/StatsCard.vue';

const props = defineProps({
  subscriptions: { type: Object, required: true },
  plans:         { type: Array,  default: () => [] },
  stats:         { type: Object, default: () => ({}) },
  filters:       { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const toast   = useToast();
const { can } = usePermissions();

const loading        = ref(false);
const assignDialog   = ref(false);
const editPlanDialog = ref(false);
const saving         = ref(false);
const search         = ref(props.filters.search || '');
const filterPlan     = ref(props.filters.plan_id || null);
const editingPlan    = ref(null);
const assignForm = reactive({ user_search: '', plan_id: null, expires_at: null });
const planForm   = reactive({ price_usd: 0, price_pen: 0, discount_percent: 0, duration_days: 30, is_active: true });

const planOptions = computed(() => props.plans.map(p => ({ label: p.name, value: p.id })));
const stats = computed(() => [
  { label: 'Total activas',    value: props.stats.total_active ?? 0,    icon: 'pi-crown',   color: 'primary' },
  { label: 'Plan Free',        value: props.stats.free_count ?? 0,      icon: 'pi-gift',    color: 'success' },
  { label: 'Plan Pro',         value: props.stats.pro_count ?? 0,       icon: 'pi-star',    color: 'warning' },
  { label: 'Plan Business',    value: props.stats.business_count ?? 0,  icon: 'pi-building',color: 'danger'  },
  { label: 'Plan Ultimate',    value: props.stats.ultimate_count ?? 0,  icon: 'pi-bolt',    color: 'info'    },
]);

function applySearch() {
  loading.value = true;
  const params = Object.fromEntries(Object.entries({ search: search.value, plan_id: filterPlan.value }).filter(([,v]) => v));
  router.get(route('admin.subscriptions.index'), params, { preserveState: true, replace: true, onFinish: () => { loading.value = false; } });
}
function goPage(page) { router.get(route('admin.subscriptions.index'), { search: search.value, plan_id: filterPlan.value, page }, { preserveState: true }); }

function openAssign() { Object.assign(assignForm, { user_search: '', plan_id: null, expires_at: null }); assignDialog.value = true; }
function saveAssign() {
  saving.value = true;
  router.post(route('admin.subscriptions.assign'), assignForm, {
    onSuccess: () => { assignDialog.value = false; toast.add({ severity:'success', summary:'Plan asignado', life:3000 }); },
    onError:   () => toast.add({ severity:'error', summary:'Error al asignar', life:3000 }),
    onFinish:  () => { saving.value = false; },
  });
}

function openEditPlan(plan) {
  editingPlan.value = plan;
  planForm.price_usd        = Number(plan.price_usd);
  planForm.price_pen        = Number(plan.price_pen ?? 0);
  planForm.discount_percent = Number(plan.discount_percent);
  planForm.duration_days    = Number(plan.duration_days ?? 30);
  planForm.is_active        = !!plan.is_active;
  editPlanDialog.value = true;
}
function savePlan() {
  saving.value = true;
  router.put(route('admin.subscriptions.plans.update', editingPlan.value.id), planForm, {
    onSuccess: () => { editPlanDialog.value = false; toast.add({ severity:'success', summary:'Plan actualizado', life:3000 }); },
    onError:   () => toast.add({ severity:'error', summary:'Error al actualizar', life:3000 }),
    onFinish:  () => { saving.value = false; },
  });
}

function confirmRevoke(sub) {
  confirm.require({
    header:      '¿Revocar suscripción?',
    message:     `Esto desactivará el plan ${sub.plan?.name} de ${sub.user?.name}.`,
    icon:        'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, revocar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => router.delete(route('admin.subscriptions.revoke', sub.id), {
      onSuccess: () => toast.add({ severity:'success', summary:'Suscripción revocada', life:3000 }),
    }),
  });
}

function initials(u) { return (u?.name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2); }
function fmtDate(d)  { return d ? new Date(d).toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' }) : '—'; }
const subStatusLabel    = s => ({ active:'Activo', cancelled:'Cancelado', expired:'Expirado', pending:'Pendiente' }[s] ?? s);
const subStatusSeverity = s => ({ active:'success', cancelled:'secondary', expired:'danger', pending:'warn' }[s] ?? 'secondary');
</script>

<style scoped>
.stats-row  { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem; }
@media (max-width:600px) { .stats-row { grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); } }
.mb-4       { margin-bottom:1rem; }
.card-head  { display:flex; align-items:center; gap:0.5rem; padding:0.875rem 1.125rem; font-size:0.85rem; font-weight:700; color:var(--c-text); }
.card-head-inline { display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; font-weight:700; color:var(--c-text); }
.toolbar-row { display:flex; align-items:center; gap:0.75rem; padding:0.875rem 1.125rem; flex-wrap:wrap; }
.toolbar-search { flex:1; min-width:200px; }

/* Table responsive fix */
:deep(.p-datatable-wrapper) { overflow-x: auto; -webkit-overflow-scrolling: touch; }

.plan-cell  { display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:var(--c-text); }
.plan-dot   { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.dot-free     { background:#10b981; }
.dot-pro      { background:var(--c-primary); }
.dot-business { background:#f59e0b; }
.dot-ultimate { background:var(--c-info); }
.cell-bold  { font-weight:600; color:var(--c-text); }
.cell-money { font-size:0.85rem; font-weight:600; color:var(--c-text); }
.cell-number{ font-size:0.85rem; font-weight:700; color:var(--c-text); }
.cell-user  { display:flex; align-items:center; gap:0.625rem; }
.cell-name  { font-size:0.85rem; font-weight:600; color:var(--c-text); margin:0; }
.cell-email { font-size:0.72rem; color:var(--c-text-muted); margin:0; }
.date-text  { font-size:0.78rem; color:var(--c-text-muted); }
.dialog-form { display:flex; flex-direction:column; gap:1rem; padding:0.5rem 0; }
.field      { display:flex; flex-direction:column; gap:0.375rem; }
.field-label { font-size:0.8rem; font-weight:600; color:var(--p-text-muted-color); }
.field-hint  { font-size:0.72rem; color:var(--p-text-muted-color); }
.field-row   { flex-direction:row; align-items:flex-start; justify-content:space-between; gap:1rem; }
.field-meta  { flex:1; }

/* Payment method badges */
.pay-methods { display:flex; align-items:center; gap:0.375rem; flex-wrap:wrap; }
.pay-badge   { display:inline-flex; align-items:center; gap:0.25rem; font-size:0.68rem; font-weight:700; padding:0.2rem 0.5rem; border-radius:6px; }
.pay-mp      { background:color-mix(in srgb,#009ee3 15%,transparent); color:#009ee3; border:1px solid color-mix(in srgb,#009ee3 30%,transparent); }
.pay-pp      { background:color-mix(in srgb,#003087 15%,transparent); color:#6da0f0; border:1px solid color-mix(in srgb,#003087 30%,transparent); }
.pay-nt      { background:color-mix(in srgb,var(--p-primary-color) 15%,transparent); color:var(--p-primary-color); border:1px solid color-mix(in srgb,var(--p-primary-color) 30%,transparent); }
.text-muted  { font-size:0.8rem; color:var(--p-text-muted-color); }
.cell-free   { font-size:0.8rem; color:#10b981; font-weight:600; }
.cell-nt     { font-size:0.8rem; color:var(--p-primary-color); font-weight:600; }
.visibility-cell { display:flex; align-items:center; }

/* Plan cards in assign dialog */
.plan-cards  { display:flex; flex-direction:column; gap:0.625rem; }
.plan-card   {
  padding:0.75rem 1rem; border-radius:10px; cursor:pointer;
  border:1.5px solid var(--p-surface-700);
  background:color-mix(in srgb, var(--p-surface-800) 50%, transparent);
  transition:all 0.15s;
}
.plan-card:hover { border-color:color-mix(in srgb,var(--p-primary-color) 50%,transparent); }
.plan-card.selected {
  border-color:var(--p-primary-color);
  background:color-mix(in srgb,var(--p-primary-color) 10%,transparent);
}
.plan-card-top   { display:flex; align-items:center; gap:0.5rem; margin-bottom:0.375rem; }
.plan-card-name  { font-size:0.88rem; font-weight:700; color:var(--p-text-color); flex:1; }
.plan-card-prices { display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.375rem; }
.price-badge { display:inline-flex; align-items:center; gap:0.25rem; font-size:0.72rem; font-weight:600; padding:0.15rem 0.5rem; border-radius:6px; }
.price-usd   { background:color-mix(in srgb,#22c55e 12%,transparent); color:#22c55e; }
.price-nt    { background:color-mix(in srgb,var(--p-primary-color) 12%,transparent); color:var(--p-primary-color); }
.price-free  { background:color-mix(in srgb,#94a3b8 12%,transparent); color:#94a3b8; }
.plan-card-methods { display:flex; gap:0.375rem; flex-wrap:wrap; }
</style>
