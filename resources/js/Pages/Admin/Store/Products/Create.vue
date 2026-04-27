<template>
  <DashboardLayout title="Nuevo Producto" active="products">
    <PageHeader
      title="Publicar nuevo producto"
      subtitle="Completa el formulario para poner tu producto a la venta"
      icon="pi-plus-circle"
      :breadcrumb="[{ label: 'Vendedor' }, { label: 'Productos' }, { label: 'Nuevo' }]"
    />

    <div class="form-wrap">
      <!-- Stepper -->
      <Stepper v-model:value="step" linear>
        <StepList>
          <Step value="1"><i class="pi pi-info-circle" /> Información básica</Step>
          <Step value="2"><i class="pi pi-image" /> Media y precio</Step>
          <Step value="3"><i class="pi pi-key" /> Claves y entrega</Step>
        </StepList>
        <StepPanels>
          <!-- Step 1: Basic info -->
          <StepPanel value="1">
            <DataCard class="step-card">
              <div class="form-section">
                <div class="field">
                  <label class="field-label">Nombre del producto *</label>
                  <InputText v-model="form.name" placeholder="Ej: Windows 11 Pro — Clave digital" :invalid="!!errors.name" fluid />
                  <Message v-if="errors.name" severity="error" size="small" variant="simple">{{ errors.name }}</Message>
                </div>

                <div class="form-row">
                  <div class="field flex-1">
                    <label class="field-label">Categoría *</label>
                    <Select v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id" placeholder="Seleccionar" :invalid="!!errors.category_id" fluid />
                  </div>
                  <div class="field flex-1">
                    <label class="field-label">Plataforma</label>
                    <Select v-model="form.platform" :options="platformOptions" optionLabel="label" optionValue="value" placeholder="Seleccionar" fluid />
                  </div>
                  <div class="field flex-1">
                    <label class="field-label">Región</label>
                    <Select v-model="form.region" :options="regionOptions" optionLabel="label" optionValue="value" placeholder="Global" fluid />
                  </div>
                </div>

                <div class="field">
                  <label class="field-label">Descripción</label>
                  <Textarea v-model="form.description" rows="4" placeholder="Describe tu producto, incluye instrucciones de activación..." fluid />
                </div>

                <div class="field">
                  <label class="field-label">Tags (separados por coma)</label>
                  <InputText v-model="form.tags" placeholder="Ej: windows, licencia, digital" fluid />
                </div>
              </div>
              <div class="step-nav">
                <span />
                <Button label="Siguiente" icon="pi pi-arrow-right" iconPos="right" @click="step = '2'" :disabled="!form.name || !form.category_id" />
              </div>
            </DataCard>
          </StepPanel>

          <!-- Step 2: Media + Price -->
          <StepPanel value="2">
            <DataCard class="step-card">
              <div class="form-section">
                <!-- Cover image upload -->
                <div class="field">
                  <label class="field-label">Imagen de portada</label>
                  <FileUpload
                    mode="basic"
                    accept="image/*"
                    :maxFileSize="2000000"
                    chooseLabel="Subir imagen"
                    @select="onFileSelect"
                    :auto="false"
                  />
                  <div v-if="previewUrl" class="cover-preview">
                    <img :src="previewUrl" alt="Preview" class="cover-img" />
                    <Button icon="pi pi-times" text severity="danger" size="small" @click="clearImage" v-tooltip.top="'Quitar imagen'" />
                  </div>
                  <small class="field-hint">Recomendado: 600×450px, máx. 2MB (JPG/PNG/WEBP)</small>
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
                    <label class="field-label">Cashback NT</label>
                    <InputNumber v-model="form.cashback_percent" suffix="%" :min="0" :max="20" placeholder="0%" fluid />
                    <small class="field-hint">% del precio en NT como recompensa</small>
                  </div>
                </div>

                <!-- License type -->
                <div class="form-row">
                  <div class="field flex-1">
                    <label class="field-label">Tipo de licencia *</label>
                    <Select v-model="form.license_type" :options="licenseTypes" optionLabel="label" optionValue="value" fluid />
                  </div>
                  <div class="field flex-1">
                    <label class="field-label">Activaciones máximas</label>
                    <InputNumber v-model="form.max_activations" :min="1" placeholder="1" fluid />
                  </div>
                </div>

                <!-- Visibility -->
                <div class="field-toggle">
                  <div>
                    <p class="toggle-label">Publicar inmediatamente</p>
                    <p class="toggle-sub">El producto será visible en el catálogo al crear</p>
                  </div>
                  <ToggleSwitch v-model="form.is_active" />
                </div>
              </div>
              <div class="step-nav">
                <Button label="Atrás" icon="pi pi-arrow-left" severity="secondary" outlined @click="step = '1'" />
                <Button label="Siguiente" icon="pi pi-arrow-right" iconPos="right" @click="step = '3'" :disabled="!form.price_usd || !form.price_pen" />
              </div>
            </DataCard>
          </StepPanel>

          <!-- Step 3: Keys -->
          <StepPanel value="3">
            <DataCard class="step-card">
              <div class="form-section">
                <div class="field">
                  <label class="field-label">Claves de activación *</label>
                  <p class="field-desc">Ingresa una clave por línea. Estas se cifrarán automáticamente antes de guardarse.</p>
                  <Textarea v-model="form.keys_text" rows="8" placeholder="AAAAA-BBBBB-CCCCC-DDDDD&#10;EEEEE-FFFFF-GGGGG-HHHHH&#10;..." fluid :invalid="!!errors.keys" />
                  <Message v-if="errors.keys" severity="error" size="small" variant="simple">{{ errors.keys }}</Message>
                </div>
                <div class="key-stats">
                  <Tag :value="`${keyCount} clave${keyCount !== 1 ? 's' : ''} detectada${keyCount !== 1 ? 's' : ''}`" severity="info" />
                  <small class="field-hint">Cada línea no vacía cuenta como una clave</small>
                </div>

                <!-- Delivery mode -->
                <div class="field">
                  <label class="field-label">Modo de entrega</label>
                  <Select v-model="form.delivery_mode" :options="deliveryModes" optionLabel="label" optionValue="value" fluid />
                </div>

                <!-- Review box -->
                <div class="review-box">
                  <h3 class="review-title">Resumen del producto</h3>
                  <div class="review-grid">
                    <div class="review-row"><span>Nombre</span><span>{{ form.name || '—' }}</span></div>
                    <div class="review-row"><span>Precio USD</span><span>${{ form.price_usd?.toFixed(2) ?? '—' }}</span></div>
                    <div class="review-row"><span>Precio PEN</span><span>S/ {{ form.price_pen?.toFixed(2) ?? '—' }}</span></div>
                    <div class="review-row"><span>Licencia</span><span>{{ licenseLabel(form.license_type) }}</span></div>
                    <div class="review-row"><span>Claves</span><span>{{ keyCount }}</span></div>
                    <div class="review-row"><span>Estado</span><Tag :value="form.is_active ? 'Publicado' : 'Borrador'" :severity="form.is_active ? 'success' : 'secondary'" size="small" rounded /></div>
                  </div>
                </div>
              </div>

              <div class="step-nav">
                <Button label="Atrás" icon="pi pi-arrow-left" severity="secondary" outlined @click="step = '2'" />
                <Button label="Publicar producto" icon="pi pi-check" :loading="saving" @click="save" :disabled="keyCount === 0" />
              </div>
            </DataCard>
          </StepPanel>
        </StepPanels>
      </Stepper>
    </div>

    <Toast />
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router }                  from '@inertiajs/vue3';
import { useToast }                from 'primevue/usetoast';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader      from '@/Components/ui/PageHeader.vue';
import DataCard        from '@/Components/ui/DataCard.vue';

const props = defineProps({
  categories: { type: Array, default: () => [] },
});

const toast  = useToast();
const step   = ref('1');
const saving = ref(false);
const errors = ref({});
const previewUrl = ref(null);
const coverFile  = ref(null);

const form = reactive({
  name:            '',
  category_id:     null,
  platform:        null,
  region:          'Global',
  description:     '',
  tags:            '',
  price_usd:       null,
  price_pen:       null,
  sale_price:      null,
  cashback_percent: 0,
  license_type:    'perpetual',
  max_activations: 1,
  is_active:       false,
  delivery_mode:   'auto',
  keys_text:       '',
});

const platformOptions = ['PC', 'PlayStation', 'Xbox', 'Nintendo Switch', 'Steam', 'Epic Games', 'Mobile', 'Multi-plataforma'].map(p => ({ label: p, value: p }));
const regionOptions   = ['Global', 'LATAM', 'USA', 'Europa', 'Asia', 'España'].map(r => ({ label: r, value: r }));
const licenseTypes    = [
  { label: 'Permanente', value: 'perpetual' },
  { label: 'Suscripción', value: 'subscription' },
  { label: 'Trial',       value: 'trial' },
];
const deliveryModes = [
  { label: 'Automática (al confirmar pago)', value: 'auto' },
  { label: 'Manual (tú confirmas la entrega)', value: 'manual' },
];

const keyCount = computed(() => form.keys_text.split('\n').filter(l => l.trim()).length);
const licenseLabel = (t) => licenseTypes.find(l => l.value === t)?.label ?? t;

function onFileSelect(e) {
  const file = e.files[0];
  if (!file) return;
  coverFile.value = file;
  previewUrl.value = URL.createObjectURL(file);
}
function clearImage() { previewUrl.value = null; coverFile.value = null; }

function save() {
  saving.value = true;
  errors.value = {};
  const formData = new FormData();

  // Map form fields to backend expected field names
  formData.append('name',             form.name);
  formData.append('category_id',      form.category_id);
  formData.append('platform',         form.platform ?? '');
  formData.append('region',           form.region ?? 'Global');
  formData.append('description',      form.description ?? '');
  formData.append('delivery_type',    form.delivery_mode);
  formData.append('price_usd',        form.price_usd);
  formData.append('price_pen',        form.price_pen);
  formData.append('cashback_percent', form.cashback_percent ?? 0);
  formData.append('is_active',        form.is_active ? '1' : '0');

  // Tags: convert CSV string to individual array entries
  const tagList = form.tags.split(',').map(t => t.trim()).filter(Boolean);
  tagList.forEach(tag => formData.append('tags[]', tag));

  // Keys
  formData.append('keys_text', form.keys_text);

  // Imagen de portada: el backend espera images[] (array). La primera se marca como cover.
  if (coverFile.value) formData.append('images[]', coverFile.value);

  router.post(route('admin.store.products.store'), formData, {
    forceFormData: true,
    onSuccess: () => toast.add({ severity:'success', summary:'Producto publicado', life:3000 }),
    onError:   (e) => { errors.value = e; step.value = Object.keys(e)[0] === 'keys' ? '3' : '2'; },
    onFinish:  () => { saving.value = false; },
  });
}
</script>

<style scoped>
.form-wrap    { max-width: 900px; }
.step-card    { margin-top: 0; }
.form-section { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row     { display: flex; gap: 1rem; flex-wrap: wrap; }
.field        { display: flex; flex-direction: column; gap: 0.375rem; min-width: 180px; }
.flex-1       { flex: 1; }
.field-label  { font-size: 0.8rem; font-weight: 600; color: var(--c-text-muted); }
.field-desc   { font-size: 0.78rem; color: var(--c-text-muted); margin: 0; }
.field-hint   { font-size: 0.72rem; color: var(--c-text-subtle); }
.cover-preview{ display: flex; align-items: flex-start; gap: 0.75rem; margin-top: 0.5rem; }
.cover-img    { width: 120px; height: 90px; border-radius: 10px; object-fit: cover; border: 1px solid var(--c-border); }
.field-toggle { display: flex; justify-content: space-between; align-items: center; background: var(--c-card); border-radius: 10px; padding: 1rem; }
.toggle-label { font-size: 0.85rem; font-weight: 600; color: var(--c-text); margin: 0; }
.toggle-sub   { font-size: 0.75rem; color: var(--c-text-muted); margin: 0.2rem 0 0; }
.key-stats    { display: flex; align-items: center; gap: 0.75rem; }
.review-box   { background: var(--c-card); border-radius: 14px; padding: 1.25rem; }
.review-title { font-size: 0.85rem; font-weight: 700; color: var(--c-text); margin: 0 0 0.875rem; }
.review-grid  { display: flex; flex-direction: column; gap: 0; }
.review-row   { display: flex; justify-content: space-between; align-items: center; font-size: 0.83rem; color: var(--c-text-muted); padding: 0.4rem 0; border-bottom: 1px solid var(--c-border); }
.review-row:last-child { border-bottom: none; }
.step-nav     { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--c-border); }
</style>
