<template>
  <DashboardLayout title="Gestión de Claves" active="keys">
    <Head title="Claves Digitales — Vendedor" />
    <PageHeader title="Claves Digitales" subtitle="Importa y gestiona tus licencias" icon="pi-key"
      :breadcrumb="[{ label:'Vendedor' }, { label:'Claves' }]">
      <template #actions>
        <Button v-if="can('keys.import')" label="Importar claves" icon="pi pi-upload" @click="openDialog" />
      </template>
    </PageHeader>

    <!-- Stats -->
    <div class="key-stats">
      <div class="key-stat"><span class="ks-value">{{ stats.total ?? 0 }}</span><span class="ks-label">Total</span></div>
      <div class="key-stat key-stat-available"><span class="ks-value">{{ stats.available ?? 0 }}</span><span class="ks-label">Disponibles</span></div>
      <div class="key-stat key-stat-sold"><span class="ks-value">{{ stats.sold ?? 0 }}</span><span class="ks-label">Vendidas</span></div>
      <div class="key-stat key-stat-expired"><span class="ks-value">{{ stats.expired ?? 0 }}</span><span class="ks-label">Expiradas</span></div>
    </div>

    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <Select v-model="filters.product_id" :options="products" optionLabel="name" optionValue="id" placeholder="Todos los productos" showClear @change="apply" class="prod-select" />
          <Select v-model="filters.status" :options="statusOpts" optionLabel="label" optionValue="value" placeholder="Estado" showClear @change="apply" />
        </div>
      </template>

      <DataTable :value="keys.data" size="small" :rowHover="true" v-model:selection="selected" selectionMode="multiple">
        <Column selectionMode="multiple" style="width:48px" />
        <Column header="Producto">
          <template #body="{ data }"><span class="cell-name">{{ data.product?.name ?? '—' }}</span></template>
        </Column>
        <Column header="Clave" style="min-width:180px">
          <template #body="{ data }">
            <div class="key-cell">
              <code class="key-code">{{ masked(data.key_value) }}</code>
              <Button icon="pi pi-copy" size="small" text v-tooltip.top="'Copiar'" @click="copy(data.key_value)" />
            </div>
          </template>
        </Column>
        <Column header="Licencia" style="width:120px">
          <template #body="{ data }">
            <Tag :value="licenseLabel(data.license_type)" severity="info" size="small" />
          </template>
        </Column>
        <Column header="Estado" style="width:110px">
          <template #body="{ data }"><StatusBadge :status="data.status" /></template>
        </Column>
        <Column header="Agregada" style="width:120px">
          <template #body="{ data }"><span class="date-text">{{ fmtDate(data.created_at) }}</span></template>
        </Column>
        <Column v-if="can('keys.delete')" style="width:60px">
          <template #body="{ data }">
            <Button icon="pi pi-trash" size="small" text severity="danger" v-tooltip.top="'Eliminar'" @click="confirmDelete(data)" />
          </template>
        </Column>
        <template #empty><EmptyState icon="pi-key" title="Sin claves" description="Importa claves para tus productos." /></template>
      </DataTable>

      <template #footer>
        <div class="table-footer">
          <Button v-if="can('keys.delete') && selected.length" :label="`Eliminar seleccionadas (${selected.length})`" icon="pi pi-trash" severity="danger" size="small" text @click="bulkDelete" />
          <Paginator :rows="keys.per_page" :totalRecords="keys.total" :first="(keys.current_page-1)*keys.per_page" @page="goPage($event.page+1)" />
        </div>
      </template>
    </DataCard>

    <!-- Import Dialog con tabs Individual / Masivo -->
    <Dialog v-model:visible="importDialog" modal header="Agregar claves" :style="{width:'560px'}" :draggable="false">
      <div class="dialog-form">
        <!-- Producto -->
        <div class="field">
          <label class="field-label">Producto *</label>
          <Select v-model="form.product_id" :options="products" optionLabel="name" optionValue="id" placeholder="Selecciona el producto" fluid :invalid="!!errs.product_id" />
          <Message v-if="errs.product_id" severity="error" size="small" variant="simple">{{ errs.product_id }}</Message>
        </div>

        <!-- Licencia + Activaciones -->
        <div class="form-row">
          <div class="field flex-1">
            <label class="field-label">Tipo de licencia *</label>
            <Select v-model="form.license_type" :options="licenseTypes" optionLabel="label" optionValue="value" fluid :invalid="!!errs.license_type" />
            <Message v-if="errs.license_type" severity="error" size="small" variant="simple">{{ errs.license_type }}</Message>
          </div>
          <div class="field flex-1">
            <label class="field-label">Activaciones máx. *</label>
            <InputNumber v-model="form.max_activations" :min="1" :max="10" showButtons fluid :invalid="!!errs.max_activations" />
            <Message v-if="errs.max_activations" severity="error" size="small" variant="simple">{{ errs.max_activations }}</Message>
          </div>
        </div>

        <!-- Tabs Individual / Masivo -->
        <Tabs v-model:value="form.mode" :pt="{ tabpanels: { class: 'pt-2' } }">
          <TabList>
            <Tab value="single"><i class="pi pi-plus-circle mr-1" />Individual</Tab>
            <Tab value="bulk"><i class="pi pi-list mr-1" />Masivo</Tab>
          </TabList>
          <TabPanels>
            <!-- Modo individual -->
            <TabPanel value="single">
              <div class="field">
                <label class="field-label">Clave *</label>
                <InputText v-model="form.key_value" placeholder="AAAAA-BBBBB-CCCCC-DDDDD" fluid :invalid="!!errs.key_value" />
                <small class="field-hint">Ingresa una sola clave. Se cifrará antes de guardarse.</small>
                <Message v-if="errs.key_value" severity="error" size="small" variant="simple">{{ errs.key_value }}</Message>
              </div>
            </TabPanel>
            <!-- Modo masivo -->
            <TabPanel value="bulk">
              <div class="field">
                <label class="field-label">Claves (una por línea) *</label>
                <Textarea v-model="form.keys_text" rows="7" placeholder="XXXX-XXXX-XXXX&#10;YYYY-YYYY-YYYY&#10;..." fluid :invalid="!!errs.keys_text" />
                <small class="field-hint">{{ bulkCount }} clave(s) detectada(s). También puedes adjuntar archivo .txt o .csv:</small>
                <Message v-if="errs.keys_text" severity="error" size="small" variant="simple">{{ errs.keys_text }}</Message>
              </div>
              <div class="field">
                <label class="field-label">Archivo (opcional)</label>
                <FileUpload mode="basic" accept=".txt,.csv" :maxFileSize="2097152" chooseLabel="Subir archivo" :auto="false" @select="onFileSelect" />
                <small v-if="form.keys_file" class="field-hint">{{ form.keys_file.name }} seleccionado.</small>
              </div>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="importDialog=false" />
        <Button :label="submitLabel" :icon="form.mode === 'single' ? 'pi pi-check' : 'pi pi-upload'"
                :loading="importing" :disabled="!canSubmit" @click="doImport" />
      </template>
    </Dialog>

    <ConfirmDialog /><Toast />
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast }   from 'primevue/usetoast';
import { usePermissions } from '@/composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader   from '@/Components/ui/PageHeader.vue';
import DataCard     from '@/Components/ui/DataCard.vue';
import EmptyState   from '@/Components/ui/EmptyState.vue';
import StatusBadge  from '@/Components/ui/StatusBadge.vue';

const props = defineProps({
  keys:     { type: Object, default: () => ({ data: [], per_page: 50, total: 0, current_page: 1 }) },
  products: { type: Array,  default: () => [] },
  stats:    { type: Object, default: () => ({}) },
  filters:  { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const toast   = useToast();
const { can } = usePermissions();

const selected      = ref([]);
const importDialog  = ref(false);
const importing     = ref(false);
const errs          = ref({});

const form = reactive({
  mode:            'single',
  product_id:      null,
  license_type:    'perpetual',
  max_activations: 1,
  key_value:       '',
  keys_text:       '',
  keys_file:       null,
});

const filters    = reactive({ product_id: props.filters.product_id || null, status: props.filters.status || null });
const statusOpts = [
  { label: 'Disponible', value: 'available' },
  { label: 'Vendida',    value: 'sold' },
  { label: 'Reservada',  value: 'reserved' },
  { label: 'Expirada',   value: 'expired' },
];
const licenseTypes = [
  { label: 'Permanente',   value: 'perpetual'    },
  { label: 'Suscripción',  value: 'subscription' },
  { label: 'Trial',        value: 'trial'        },
];

const bulkCount = computed(() => form.keys_text.trim().split('\n').filter(l => l.trim()).length);
const canSubmit = computed(() => {
  if (!form.product_id) return false;
  if (form.mode === 'single') return form.key_value.trim().length >= 4;
  return bulkCount.value > 0 || !!form.keys_file;
});
const submitLabel = computed(() =>
  form.mode === 'single'
    ? 'Agregar clave'
    : `Importar ${bulkCount.value || '0'} clave(s)`,
);

function openDialog() {
  errs.value = {};
  Object.assign(form, {
    mode: 'single', product_id: null, license_type: 'perpetual', max_activations: 1,
    key_value: '', keys_text: '', keys_file: null,
  });
  importDialog.value = true;
}

function onFileSelect(e) { form.keys_file = e.files[0] || null; }

function masked(v)    { if (!v) return ''; return v.length > 8 ? v.slice(0, 4) + '****' + v.slice(-4) : v; }
function licenseLabel(t) { return licenseTypes.find(l => l.value === t)?.label ?? t ?? '—'; }
function fmtDate(d)   { return d ? new Date(d).toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'; }
async function copy(v){ await navigator.clipboard.writeText(v); toast.add({ severity:'success', summary:'Copiado', life:1500 }); }

function apply()      { const p = Object.fromEntries(Object.entries(filters).filter(([,v]) => v)); router.get(route('admin.store.keys.index'), p, { preserveState:true, replace:true }); }
function goPage(pg)   { router.get(route('admin.store.keys.index'), { ...Object.fromEntries(Object.entries(filters).filter(([,v]) => v)), page: pg }, { preserveState:true }); }

function confirmDelete(k) {
  confirm.require({
    header: '¿Eliminar clave?',
    message: `Se eliminará la clave del producto "${k.product?.name}".`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar', rejectLabel: 'Cancelar', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('admin.store.keys.destroy', k.id), {
      onSuccess: () => toast.add({ severity:'success', summary:'Clave eliminada', life:2000 }),
    }),
  });
}

function bulkDelete() {
  confirm.require({
    header: `¿Eliminar ${selected.value.length} claves?`,
    message: 'Esta acción no se puede deshacer.',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar', acceptClass: 'p-button-danger',
    accept: () => {
      selected.value.forEach(k => router.delete(route('admin.store.keys.destroy', k.id), { preserveState: true }));
      selected.value = [];
    },
  });
}

function doImport() {
  importing.value = true;
  errs.value = {};

  const fd = new FormData();
  fd.append('product_id',      form.product_id);
  fd.append('license_type',    form.license_type);
  fd.append('max_activations', form.max_activations);
  fd.append('mode',            form.mode);

  if (form.mode === 'single') {
    fd.append('key_value', form.key_value.trim());
  } else {
    if (form.keys_text.trim()) fd.append('keys_text', form.keys_text.trim());
    if (form.keys_file)        fd.append('keys_file', form.keys_file);
  }

  router.post(route('admin.store.keys.import'), fd, {
    forceFormData: true,
    onSuccess: () => {
      importDialog.value = false;
      toast.add({ severity:'success', summary: form.mode === 'single' ? 'Clave agregada' : 'Claves importadas', life:3000 });
    },
    onError:   (e) => { errs.value = e; },
    onFinish:  () => { importing.value = false; },
  });
}
</script>

<style scoped>
.key-stats        { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
.key-stat         { background:var(--c-surface); border:1px solid var(--c-border); border-radius:12px; padding:1rem 1.25rem; display:flex; flex-direction:column; align-items:center; gap:0.25rem; }
.ks-value         { font-size:1.6rem; font-weight:800; color:var(--c-text); }
.ks-label         { font-size:0.72rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--c-text-subtle); }
.key-stat-available .ks-value { color:#10b981; }
.key-stat-sold      .ks-value { color:var(--c-primary); }
.key-stat-expired   .ks-value { color:#ef4444; }
.toolbar-row      { display:flex; gap:0.75rem; padding:0.875rem 1.125rem; flex-wrap:wrap; }
.prod-select      { min-width:220px; }
.cell-name        { font-size:0.83rem; font-weight:500; color:var(--c-text); }
.key-cell         { display:flex; align-items:center; gap:0.25rem; }
.key-code         { font-family:monospace; font-size:0.78rem; background:var(--c-card); padding:0.2rem 0.5rem; border-radius:4px; color:var(--c-text-muted); }
.date-text        { font-size:0.78rem; color:var(--c-text-muted); }
.table-footer     { display:flex; align-items:center; justify-content:space-between; padding:0 0.5rem; }
.dialog-form      { display:flex; flex-direction:column; gap:1rem; padding:0.25rem 0; }
.form-row         { display:flex; gap:0.75rem; }
.flex-1           { flex:1; }
.field            { display:flex; flex-direction:column; gap:0.375rem; }
.field-label      { font-size:0.8rem; font-weight:600; color:var(--c-text-muted); }
.field-hint       { font-size:0.72rem; color:var(--c-text-subtle); }
.mr-1             { margin-right:0.25rem; }
.pt-2             { padding-top:0.5rem; }
</style>
