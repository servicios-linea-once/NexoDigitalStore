<template>
  <DashboardLayout title="Nueva Promoción" active="promotions">
    <PageHeader
      title="Nueva Promoción"
      subtitle="Crea un código de descuento para tus productos"
      icon="pi-tag"
      :breadcrumb="[{ label: 'Vendedor' }, { label: 'Promociones', route: 'seller.promotions.index' }, { label: 'Nueva' }]"
    />

    <div class="form-wrap">
      <DataCard>
        <form @submit.prevent="save" class="form-section">
          <!-- Basic info -->
          <div class="form-row">
            <div class="field flex-2">
              <label class="field-label">Nombre de la promoción *</label>
              <InputText v-model="form.name" placeholder="Ej: Descuento de verano" :invalid="!!errors.name" fluid />
              <Message v-if="errors.name" severity="error" size="small" variant="simple">{{ errors.name }}</Message>
            </div>
            <div class="field flex-1">
              <label class="field-label">Código de cupón *</label>
              <div class="code-row">
                <InputText v-model="form.code" placeholder="VERANO25" :invalid="!!errors.code" fluid style="text-transform:uppercase" />
                <Button icon="pi pi-refresh" text severity="secondary" v-tooltip.top="'Generar aleatorio'" @click="generateCode" type="button" />
              </div>
              <Message v-if="errors.code" severity="error" size="small" variant="simple">{{ errors.code }}</Message>
            </div>
          </div>

          <!-- Discount type + value -->
          <div class="form-row">
            <div class="field flex-1">
              <label class="field-label">Tipo de descuento *</label>
              <Select v-model="form.type" :options="discountTypes" optionLabel="label" optionValue="value" fluid />
            </div>
            <div class="field flex-1">
              <label class="field-label">Valor del descuento *</label>
              <InputNumber
                v-model="form.value"
                :prefix="form.type === 'fixed' ? '$' : ''"
                :suffix="form.type === 'percent' ? '%' : ''"
                :min="0.01" :max="form.type === 'percent' ? 100 : 9999"
                :minFractionDigits="form.type === 'fixed' ? 2 : 0"
                :invalid="!!errors.value"
                fluid
              />
              <Message v-if="errors.value" severity="error" size="small" variant="simple">{{ errors.value }}</Message>
            </div>
            <div class="field flex-1">
              <label class="field-label">Usos máximos</label>
              <InputNumber v-model="form.max_uses" placeholder="Sin límite" :min="1" fluid />
              <small class="field-hint">Déjalo vacío para usos ilimitados</small>
            </div>
          </div>

          <!-- Validity -->
          <div class="form-row">
            <div class="field flex-1">
              <label class="field-label">Fecha de inicio</label>
              <DatePicker v-model="form.starts_at" showButtonBar fluid placeholder="Hoy" />
            </div>
            <div class="field flex-1">
              <label class="field-label">Fecha de vencimiento</label>
              <DatePicker v-model="form.expires_at" showButtonBar fluid placeholder="Sin vencimiento" :minDate="form.starts_at" />
            </div>
          </div>

          <!-- Products scope -->
          <div class="field">
            <label class="field-label">Aplicar a productos (opcional)</label>
            <MultiSelect
              v-model="form.product_ids"
              :options="products"
              optionLabel="name"
              optionValue="id"
              placeholder="Todos mis productos"
              filter
              :showToggleAll="false"
              fluid
            />
            <small class="field-hint">Si no seleccionas ninguno, aplica a todos tus productos</small>
          </div>

          <!-- Min purchase -->
          <div class="field">
            <label class="field-label">Compra mínima (USD)</label>
            <InputNumber v-model="form.min_purchase" prefix="$" :minFractionDigits="2" :min="0" placeholder="Sin mínimo" fluid />
          </div>

          <!-- Active toggle -->
          <div class="field-toggle">
            <div>
              <p class="toggle-label">Activar inmediatamente</p>
              <p class="toggle-sub">El código estará disponible en cuanto se cree</p>
            </div>
            <ToggleSwitch v-model="form.is_active" />
          </div>

          <!-- Preview -->
          <div v-if="form.code && form.value" class="preview-card">
            <div class="preview-code">{{ form.code.toUpperCase() }}</div>
            <div class="preview-info">
              <p>{{ form.type === 'percent' ? `${form.value}% de descuento` : `$${Number(form.value).toFixed(2)} de descuento` }}</p>
              <p v-if="form.expires_at" class="preview-meta">Válido hasta {{ fmtDate(form.expires_at) }}</p>
            </div>
          </div>

          <div class="form-actions">
            <Button label="Cancelar" severity="secondary" text type="button" @click="$inertia.visit(route('admin.store.promotions.index'))" />
            <Button label="Crear promoción" icon="pi pi-check" :loading="saving" type="submit" />
          </div>
        </form>
      </DataCard>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router }        from '@inertiajs/vue3';
import { useToast }      from 'primevue/usetoast';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader      from '@/Components/ui/PageHeader.vue';
import DataCard        from '@/Components/ui/DataCard.vue';

const props = defineProps({ products: { type: Array, default: () => [] } });
const toast  = useToast();
const saving = ref(false);
const errors = ref({});

const form = reactive({
  name:        '',
  code:        '',
  type:        'percent',
  value:       null,
  max_uses:    null,
  starts_at:   new Date(),
  expires_at:  null,
  product_ids: [],
  min_purchase:null,
  is_active:   true,
});

const discountTypes = [
  { label: 'Porcentaje (%)', value: 'percent' },
  { label: 'Monto fijo ($)', value: 'fixed'   },
];

function generateCode() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  form.code = Array.from({ length: 8 }, () => chars[Math.floor(Math.random() * chars.length)]).join('');
}

function save() {
  saving.value = true;
  errors.value = {};
  const data = { ...form, starts_at: form.starts_at?.toISOString().slice(0, 10), expires_at: form.expires_at?.toISOString().slice(0, 10) };
  router.post(route('admin.store.promotions.store'), data, {
    onSuccess: () => toast.add({ severity:'success', summary:'Promoción creada', life:3000 }),
    onError:   (e) => { errors.value = e; },
    onFinish:  () => { saving.value = false; },
  });
}

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' }) : ''; }
</script>

<style scoped>
.form-wrap { max-width: 840px; }
.form-section { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row   { display: flex; gap: 1rem; flex-wrap: wrap; }
.field      { display: flex; flex-direction: column; gap: 0.375rem; min-width: 200px; }
.flex-1     { flex: 1; }
.flex-2     { flex: 2; }
.field-label { font-size: 0.8rem; font-weight: 600; color: var(--c-text-muted); }
.field-hint  { font-size: 0.72rem; color: var(--c-text-subtle); }
.code-row    { display: flex; align-items: center; gap: 0.25rem; }
.field-toggle { display: flex; justify-content: space-between; align-items: center; background: var(--c-card); border-radius: 10px; padding: 1rem; }
.toggle-label { font-size: 0.85rem; font-weight: 600; color: var(--c-text); margin: 0; }
.toggle-sub   { font-size: 0.75rem; color: var(--c-text-muted); margin: 0.2rem 0 0; }
.preview-card { background: linear-gradient(135deg, var(--c-primary-muted), rgba(99,102,241,0.05)); border: 1px solid rgba(99,102,241,0.25); border-radius: 14px; padding: 1.25rem; display: flex; align-items: center; gap: 1.25rem; }
.preview-code { font-family: monospace; font-size: 1.5rem; font-weight: 900; color: var(--c-primary); background: var(--c-surface); padding: 0.5rem 1rem; border-radius: 10px; letter-spacing: 0.1em; }
.preview-info p { font-size: 0.9rem; font-weight: 600; color: var(--c-text); margin: 0; }
.preview-meta   { font-size: 0.78rem; color: var(--c-text-muted); margin-top: 0.2rem!important; }
.form-actions { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 0.5rem; }
</style>
