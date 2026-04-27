<template>
  <DashboardLayout :title="`Editar: ${promotion.name}`" active="promotions">
    <PageHeader
      :title="`Editar: ${promotion.name}`"
      subtitle="Modifica los parámetros de tu promoción"
      icon="pi-tag"
      :breadcrumb="[{ label: 'Vendedor' }, { label: 'Promociones' }, { label: promotion.name }]"
    />

    <div class="form-wrap">
      <DataCard>
        <form @submit.prevent="save" class="form-section">
          <div class="form-row">
            <div class="field flex-2">
              <label class="field-label">Nombre de la promoción *</label>
              <InputText v-model="form.name" :invalid="!!errors.name" fluid />
              <Message v-if="errors.name" severity="error" size="small" variant="simple">{{ errors.name }}</Message>
            </div>
            <div class="field flex-1">
              <label class="field-label">Código de cupón *</label>
              <InputText v-model="form.code" :invalid="!!errors.code" fluid style="text-transform:uppercase" />
              <Message v-if="errors.code" severity="error" size="small" variant="simple">{{ errors.code }}</Message>
            </div>
          </div>

          <div class="form-row">
            <div class="field flex-1">
              <label class="field-label">Tipo de descuento</label>
              <Select v-model="form.type" :options="discountTypes" optionLabel="label" optionValue="value" fluid />
            </div>
            <div class="field flex-1">
              <label class="field-label">Valor *</label>
              <InputNumber v-model="form.value" :prefix="form.type === 'fixed' ? '$' : ''" :suffix="form.type === 'percent' ? '%' : ''" :min="0.01" :max="form.type === 'percent' ? 100 : 9999" fluid />
            </div>
            <div class="field flex-1">
              <label class="field-label">Usos máximos</label>
              <InputNumber v-model="form.max_uses" placeholder="Sin límite" :min="1" fluid />
            </div>
          </div>

          <div class="form-row">
            <div class="field flex-1">
              <label class="field-label">Fecha de inicio</label>
              <DatePicker v-model="form.starts_at" showButtonBar fluid />
            </div>
            <div class="field flex-1">
              <label class="field-label">Fecha de vencimiento</label>
              <DatePicker v-model="form.expires_at" showButtonBar fluid placeholder="Sin vencimiento" />
            </div>
          </div>

          <div class="field">
            <label class="field-label">Aplicar a productos</label>
            <MultiSelect v-model="form.product_ids" :options="products" optionLabel="name" optionValue="id" placeholder="Todos mis productos" filter fluid />
          </div>

          <div class="field-toggle">
            <div>
              <p class="toggle-label">Promoción activa</p>
              <p class="toggle-sub">Desactívala para ocultarla temporalmente</p>
            </div>
            <ToggleSwitch v-model="form.is_active" />
          </div>

          <!-- Stats -->
          <div class="stats-row">
            <div class="stat-chip"><i class="pi pi-users" /> {{ promotion.uses_count }} usos realizados</div>
            <div class="stat-chip"><i class="pi pi-calendar" /> Creada el {{ fmtDate(promotion.created_at) }}</div>
          </div>

          <div class="form-actions">
            <Button label="Cancelar" severity="secondary" text type="button" @click="$inertia.visit(route('admin.store.promotions.index'))" />
            <Button label="Guardar cambios" icon="pi pi-check" :loading="saving" type="submit" />
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

const props = defineProps({
  promotion: { type: Object, required: true },
  products:  { type: Array, default: () => [] },
});

const toast  = useToast();
const saving = ref(false);
const errors = ref({});

const form = reactive({
  name:        props.promotion.name,
  code:        props.promotion.code,
  type:        props.promotion.type,
  value:       props.promotion.value,
  max_uses:    props.promotion.max_uses,
  starts_at:   props.promotion.starts_at ? new Date(props.promotion.starts_at) : null,
  expires_at:  props.promotion.expires_at ? new Date(props.promotion.expires_at) : null,
  product_ids: props.promotion.product_ids ?? [],
  min_purchase:props.promotion.min_purchase,
  is_active:   props.promotion.is_active,
});

const discountTypes = [
  { label: 'Porcentaje (%)', value: 'percent' },
  { label: 'Monto fijo ($)', value: 'fixed'   },
];

function save() {
  saving.value = true;
  errors.value = {};
  const data = { ...form, starts_at: form.starts_at?.toISOString().slice(0, 10), expires_at: form.expires_at?.toISOString().slice(0, 10) };
  router.put(route('admin.store.promotions.update', props.promotion.id), data, {
    onSuccess: () => toast.add({ severity:'success', summary:'Promoción actualizada', life:3000 }),
    onError:   (e) => { errors.value = e; },
    onFinish:  () => { saving.value = false; },
  });
}

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' }) : ''; }
</script>

<style scoped>
.form-wrap   { max-width: 840px; }
.form-section { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row    { display: flex; gap: 1rem; flex-wrap: wrap; }
.field       { display: flex; flex-direction: column; gap: 0.375rem; min-width: 200px; }
.flex-1      { flex: 1; }
.flex-2      { flex: 2; }
.field-label { font-size: 0.8rem; font-weight: 600; color: var(--c-text-muted); }
.field-toggle { display: flex; justify-content: space-between; align-items: center; background: var(--c-card); border-radius: 10px; padding: 1rem; }
.toggle-label { font-size: 0.85rem; font-weight: 600; color: var(--c-text); margin: 0; }
.toggle-sub   { font-size: 0.75rem; color: var(--c-text-muted); margin: 0.2rem 0 0; }
.stats-row   { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.stat-chip   { display: flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; color: var(--c-text-muted); background: var(--c-card); padding: 0.4rem 0.75rem; border-radius: 8px; }
.form-actions { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 0.5rem; }
</style>
