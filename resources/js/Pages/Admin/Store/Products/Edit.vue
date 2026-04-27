<template>
  <DashboardLayout :title="`Editar: ${product.name}`" active="products">
    <PageHeader
      :title="`Editar: ${product.name}`"
      subtitle="Actualiza la información y precios de tu producto"
      icon="pi-pencil"
      :breadcrumb="[{ label: 'Vendedor' }, { label: 'Productos' }, { label: product.name }]"
    />

    <div class="form-wrap">
      <DataCard>
        <form @submit.prevent="save" class="form-section">
          <!-- Cover preview + upload -->
          <div class="cover-section">
            <div class="cover-current">
              <img v-if="previewUrl || product.cover_image" :src="previewUrl || product.cover_image" class="cover-img" alt="Portada" />
              <div v-else class="cover-ph"><i class="pi pi-image" /></div>
            </div>
            <div class="cover-controls">
              <p class="field-label">Imagen de portada</p>
              <FileUpload mode="basic" accept="image/*" :maxFileSize="2000000" chooseLabel="Cambiar imagen" @select="onFileSelect" :auto="false" />
              <small class="field-hint">Recomendado: 600×450px, máx. 2MB</small>
            </div>
          </div>

          <!-- Basic info -->
          <div class="field">
            <label class="field-label">Nombre del producto *</label>
            <InputText v-model="form.name" :invalid="!!errors.name" fluid />
            <Message v-if="errors.name" severity="error" size="small" variant="simple">{{ errors.name }}</Message>
          </div>

          <div class="form-row">
            <div class="field flex-1">
              <label class="field-label">Categoría *</label>
              <Select v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id" fluid />
            </div>
            <div class="field flex-1">
              <label class="field-label">Plataforma</label>
              <Select v-model="form.platform" :options="platformOptions" optionLabel="label" optionValue="value" fluid />
            </div>
            <div class="field flex-1">
              <label class="field-label">Región</label>
              <Select v-model="form.region" :options="regionOptions" optionLabel="label" optionValue="value" fluid />
            </div>
          </div>

          <div class="field">
            <label class="field-label">Descripción</label>
            <Textarea v-model="form.description" rows="4" fluid />
          </div>

          <!-- Price -->
          <div class="form-row">
            <div class="field flex-1">
              <label class="field-label">Precio USD *</label>
              <InputNumber v-model="form.price_usd" prefix="$ " :minFractionDigits="2" :min="0.01" :invalid="!!errors.price_usd" fluid />
              <small class="field-hint">MercadoPago (USD) · PayPal</small>
              <Message v-if="errors.price_usd" severity="error" size="small" variant="simple">{{ errors.price_usd }}</Message>
            </div>
            <div class="field flex-1">
              <label class="field-label">Precio PEN *</label>
              <InputNumber v-model="form.price_pen" prefix="S/ " :minFractionDigits="2" :min="0.01" :invalid="!!errors.price_pen" fluid />
              <small class="field-hint">MercadoPago (soles)</small>
              <Message v-if="errors.price_pen" severity="error" size="small" variant="simple">{{ errors.price_pen }}</Message>
            </div>
            <div class="field flex-1">
              <label class="field-label">Precio con descuento (USD)</label>
              <InputNumber v-model="form.sale_price" prefix="$ " :minFractionDigits="2" :min="0" placeholder="Sin descuento" fluid />
            </div>
            <div class="field flex-1">
              <label class="field-label">Cashback NT (%)</label>
              <InputNumber v-model="form.cashback_percent" suffix="%" :min="0" :max="20" fluid />
            </div>
          </div>

          <!-- Delivery + Status -->
          <div class="form-row">
            <div class="field flex-1">
              <label class="field-label">Modo de entrega *</label>
              <Select v-model="form.delivery_type" :options="deliveryTypes" optionLabel="label" optionValue="value" :invalid="!!errors.delivery_type" fluid />
              <Message v-if="errors.delivery_type" severity="error" size="small" variant="simple">{{ errors.delivery_type }}</Message>
            </div>
            <div class="field flex-1">
              <label class="field-label">Estado *</label>
              <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" :invalid="!!errors.status" fluid />
              <Message v-if="errors.status" severity="error" size="small" variant="simple">{{ errors.status }}</Message>
            </div>
          </div>

          <!-- Key stats -->
          <div class="key-stats-card">
            <div class="kstat">
              <i class="pi pi-key" />
              <div>
                <p class="kstat-val">{{ product.keys_available ?? 0 }}</p>
                <p class="kstat-label">Claves disponibles</p>
              </div>
            </div>
            <div class="kstat">
              <i class="pi pi-check-circle" style="color:var(--p-green-400)" />
              <div>
                <p class="kstat-val">{{ product.keys_used ?? 0 }}</p>
                <p class="kstat-label">Claves vendidas</p>
              </div>
            </div>
            <Button
              label="Gestionar claves"
              icon="pi pi-key"
              severity="secondary"
              outlined
              @click="$inertia.visit(route('admin.store.keys.index', { product: product.ulid }))"
              class="ml-auto"
            />
          </div>

          <!-- Variants Section -->
          <div class="variants-section">
            <div class="pcard-header">
              <span class="pcard-icon"><i class="pi pi-clone" /></span>
              <div>
                <h2 class="pcard-title">Variantes del producto</h2>
                <p class="pcard-sub">Crea diferentes versiones (ej: Phone, Online, Retail)</p>
              </div>
              <Button label="Nueva variante" icon="pi pi-plus" size="small" outlined class="ml-auto" @click="addVariant" />
            </div>

            <div class="variants-list">
              <div v-for="(variant, idx) in form.variants" :key="idx" class="variant-item">
                <div class="variant-grid">
                  <div class="field">
                    <label class="field-label">Nombre de variante</label>
                    <InputText v-model="variant.variant_name" placeholder="Ej: OEM Phone" fluid />
                  </div>
                  <div class="field">
                    <label class="field-label">Precio USD</label>
                    <InputNumber v-model="variant.price_usd" prefix="$ " :minFractionDigits="2" fluid />
                  </div>
                  <div class="field">
                    <label class="field-label">Precio PEN</label>
                    <InputNumber v-model="variant.price_pen" prefix="S/ " :minFractionDigits="2" fluid />
                  </div>
                  <div class="field">
                    <label class="field-label">Estado</label>
                    <Select v-model="variant.status" :options="statusOptions" optionLabel="label" optionValue="value" fluid />
                  </div>
                  <div class="variant-actions">
                    <Button icon="pi pi-key" text rounded severity="secondary" v-tooltip.top="'Claves'" @click="manageVariantKeys(variant)" v-if="variant.ulid" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="removeVariant(idx)" />
                  </div>
                </div>
              </div>
              <div v-if="!form.variants.length" class="variants-empty">
                <i class="pi pi-info-circle" />
                <span>Este producto no tiene variantes. Se venderá como producto único.</span>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <Button label="Cancelar" severity="secondary" text type="button" @click="$inertia.visit(route('admin.store.products.index'))" />
            <Button label="Guardar cambios" icon="pi pi-check" :loading="saving" type="submit" />
          </div>
        </form>
      </DataCard>
    </div>
    <Toast />
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
  product:    { type: Object, required: true },
  categories: { type: Array,  default: () => [] },
});

const toast  = useToast();
const saving = ref(false);
const errors = ref({});
const previewUrl = ref(null);
const coverFile  = ref(null);

const form = reactive({
  name:             props.product.name,
  category_id:      props.product.category_id,
  platform:         props.product.platform,
  region:           props.product.region,
  description:      props.product.description ?? '',
  price_usd:        props.product.price_usd ?? props.product.price,
  price_pen:        props.product.price_pen,
  cashback_percent: props.product.cashback_percent ?? 0,
  delivery_type:    props.product.delivery_type ?? 'auto',
  status:           props.product.status ?? 'draft',
  variants:         props.product.variants ? JSON.parse(JSON.stringify(props.product.variants)) : [],
});

function addVariant() {
  form.variants.push({
    variant_name: '',
    price_usd:    form.price_usd,
    price_pen:    form.price_pen,
    status:       'active',
  });
}

function removeVariant(idx) {
  form.variants.splice(idx, 1);
}

function manageVariantKeys(variant) {
  router.visit(route('admin.store.keys.index', { product: variant.ulid }));
}

const platformOptions = ['PC', 'PlayStation', 'Xbox', 'Nintendo Switch', 'Steam', 'Epic Games', 'Mobile', 'Multi-plataforma'].map(p => ({ label: p, value: p }));
const regionOptions   = ['Global', 'LATAM', 'USA', 'Europa', 'Asia', 'España'].map(r => ({ label: r, value: r }));
const deliveryTypes   = [
  { label: 'Automática (al confirmar pago)', value: 'auto' },
  { label: 'Manual (tú confirmas la entrega)', value: 'manual' },
  { label: 'API (integración externa)', value: 'api' },
];
const statusOptions   = [
  { label: 'Borrador',  value: 'draft'  },
  { label: 'Publicado', value: 'active' },
  { label: 'Pausado',   value: 'paused' },
];

function onFileSelect(e) {
  const file = e.files[0];
  if (!file) return;
  coverFile.value = file;
  previewUrl.value = URL.createObjectURL(file);
}

function save() {
  saving.value = true;
  errors.value = {};
  const formData = new FormData();
  formData.append('_method', 'PUT');
  Object.entries(form).forEach(([k, v]) => {
    if (v === null || v === undefined) return;
    formData.append(k, typeof v === 'boolean' ? (v ? '1' : '0') : v);
  });
  // UpdateController acepta new_images[] — la primera se usará como portada si aún no hay ninguna
  if (coverFile.value) formData.append('new_images[]', coverFile.value);

  router.post(route('admin.store.products.update', props.product.ulid), formData, {
    forceFormData: true,
    onSuccess: () => toast.add({ severity:'success', summary:'Producto actualizado', life:3000 }),
    onError:   (e) => { errors.value = e; },
    onFinish:  () => { saving.value = false; },
  });
}
</script>

<style scoped>
.form-wrap    { max-width: 860px; }
.form-section { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row     { display: flex; gap: 1rem; flex-wrap: wrap; }
.field        { display: flex; flex-direction: column; gap: 0.375rem; min-width: 180px; }
.flex-1       { flex: 1; }
.field-label  { font-size: 0.8rem; font-weight: 600; color: var(--c-text-muted); }
.field-hint   { font-size: 0.72rem; color: var(--c-text-subtle); }
.cover-section { display: flex; align-items: flex-start; gap: 1.25rem; flex-wrap: wrap; }
.cover-current { flex-shrink: 0; }
.cover-img     { width: 140px; height: 105px; border-radius: 12px; object-fit: cover; border: 1px solid var(--c-border); }
.cover-ph      { width: 140px; height: 105px; border-radius: 12px; background: var(--c-card); display: flex; align-items: center; justify-content: center; color: var(--c-text-subtle); font-size: 2.5rem; }
.cover-controls { display: flex; flex-direction: column; gap: 0.5rem; }
.field-toggle  { display: flex; justify-content: space-between; align-items: center; background: var(--c-card); border-radius: 10px; padding: 1rem; }
.toggle-label  { font-size: 0.85rem; font-weight: 600; color: var(--c-text); margin: 0; }
.toggle-sub    { font-size: 0.75rem; color: var(--c-text-muted); margin: 0.2rem 0 0; }
.key-stats-card { background: var(--c-card); border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 2rem; flex-wrap: wrap; }
.kstat         { display: flex; align-items: center; gap: 0.75rem; }
.kstat i       { font-size: 1.25rem; color: var(--c-primary); }
.kstat-val     { font-size: 1.25rem; font-weight: 800; color: var(--c-text); margin: 0; }
.kstat-label   { font-size: 0.72rem; color: var(--c-text-muted); margin: 0; }
.ml-auto       { margin-left: auto; }
.form-actions  { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 0.5rem; }

/* ── Variants ───────────────────────────────────────────────────────── */
.variants-section {
  border-top: 1px solid var(--c-border);
  padding-top: 1.5rem;
  margin-top: 0.5rem;
}
.pcard-header { display: flex; align-items: flex-start; gap: 0.85rem; margin-bottom: 1.25rem; }
.pcard-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(139,92,246,0.12); border: 1px solid rgba(139,92,246,0.2); display: flex; align-items: center; justify-content: center; color: #a78bfa; font-size: 0.95rem; flex-shrink: 0; }
.pcard-title { margin: 0 0 0.15rem; font-size: 0.9rem; font-weight: 700; color: var(--c-text); }
.pcard-sub   { margin: 0; font-size: 0.75rem; color: var(--c-text-muted); }

.variants-list { display: flex; flex-direction: column; gap: 0.75rem; }
.variant-item {
  background: var(--c-card);
  border: 1px solid var(--c-border);
  border-radius: 12px;
  padding: 1rem;
}
.variant-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr auto;
  gap: 1rem;
  align-items: flex-end;
}
.variant-actions { display: flex; gap: 0.25rem; }
.variants-empty {
  padding: 2rem;
  text-align: center;
  border: 2px dashed var(--c-border);
  border-radius: 12px;
  color: var(--c-text-subtle);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}
.variants-empty i { font-size: 1.5rem; opacity: 0.5; }

@media (max-width: 900px) {
  .variant-grid { grid-template-columns: 1fr 1fr; }
}
</style>
