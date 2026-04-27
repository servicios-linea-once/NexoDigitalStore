<template>
  <DashboardLayout title="Ajustes Generales" active="settings">
    <PageHeader
      title="Ajustes Generales"
      subtitle="Configura los aspectos globales de tu tienda"
      icon="pi-cog"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Ajustes' }, { label: 'General' }]"
    />

    <div class="settings-wrap">
      <DataCard>
        <Tabs value="0">
          <TabList>
            <Tab value="0"><i class="pi pi-desktop mr-2" /> Tienda</Tab>
            <Tab value="1"><i class="pi pi-image mr-2" /> Marca</Tab>
            <Tab value="2"><i class="pi pi-search mr-2" /> SEO</Tab>
            <Tab value="3"><i class="pi pi-envelope mr-2" /> Contacto</Tab>
            <Tab value="4"><i class="pi pi-share-alt mr-2" /> Redes Sociales</Tab>
          </TabList>

          <TabPanels>
            <!-- ── Panel: Tienda ────────────────────────────────────── -->
            <TabPanel value="0">
              <form @submit.prevent="save" class="settings-form">
                <div class="field">
                  <label class="field-label">Nombre del sitio</label>
                  <InputText v-model="form.site_name" :invalid="!!errors.site_name" fluid />
                  <Message v-if="errors.site_name" severity="error" size="small" variant="simple">{{ errors.site_name }}</Message>
                </div>

                <div class="field field-toggle">
                  <div class="field-meta">
                    <p class="toggle-label">Estado de la tienda</p>
                    <p class="toggle-sub">Si está desactivado, el sitio mostrará un mensaje de mantenimiento.</p>
                  </div>
                  <ToggleSwitch v-model="form.site_active" />
                </div>

                <div class="form-actions">
                  <Button label="Guardar cambios" icon="pi pi-check" :loading="saving" type="submit" />
                </div>
              </form>
            </TabPanel>

            <!-- ── Panel: Marca (Logo/Favicon) ────────────────────────── -->
            <TabPanel value="1">
              <form @submit.prevent="save" class="settings-form">
                <div class="branding-grid">
                  <!-- Logo -->
                  <div class="brand-item">
                    <label class="field-label">Logotipo principal</label>
                    <div class="image-preview-box">
                      <img v-if="logoPreview || settings.site_logo" :src="logoPreview || settings.site_logo" class="preview-img logo-preview" />
                      <div v-else class="preview-ph"><i class="pi pi-image" /></div>
                    </div>
                    <FileUpload mode="basic" auto chooseLabel="Elegir Logo" accept="image/*" @select="onLogoSelect" class="p-button-sm" />
                  </div>

                  <!-- Favicon -->
                  <div class="brand-item">
                    <label class="field-label">Favicon (Icono de pestaña)</label>
                    <div class="image-preview-box">
                      <img v-if="faviconPreview || settings.site_favicon" :src="faviconPreview || settings.site_favicon" class="preview-img favicon-preview" />
                      <div v-else class="preview-ph"><i class="pi pi-image" /></div>
                    </div>
                    <FileUpload mode="basic" auto chooseLabel="Elegir Favicon" accept="image/*" @select="onFaviconSelect" class="p-button-sm" />
                  </div>
                </div>

                <div class="form-actions">
                  <Button label="Guardar cambios" icon="pi pi-check" :loading="saving" type="submit" />
                </div>
              </form>
            </TabPanel>

            <!-- ── Panel: SEO ───────────────────────────────────────── -->
            <TabPanel value="2">
              <form @submit.prevent="save" class="settings-form">
                <div class="field">
                  <label class="field-label">Título SEO (Meta Title)</label>
                  <InputText v-model="form.seo_title" :invalid="!!errors.seo_title" fluid placeholder="Ej: Mi Tienda | Mejores productos" />
                  <small class="field-hint">Máx. 70 caracteres. Se muestra en pestañas y Google.</small>
                  <Message v-if="errors.seo_title" severity="error" size="small" variant="simple">{{ errors.seo_title }}</Message>
                </div>

                <div class="field">
                  <label class="field-label">Descripción SEO (Meta Description)</label>
                  <Textarea v-model="form.seo_description" rows="3" :invalid="!!errors.seo_description" fluid placeholder="Describe brevemente tu tienda..." />
                  <small class="field-hint">Máx. 160 caracteres. Resumen para motores de búsqueda.</small>
                  <Message v-if="errors.seo_description" severity="error" size="small" variant="simple">{{ errors.seo_description }}</Message>
                </div>

                <div class="field">
                  <label class="field-label">Palabras clave (Tags)</label>
                  <Chips v-model="form.seo_keywords" separator="," placeholder="Escribe y presiona enter..." fluid />
                  <small class="field-hint">Separadas por comas. Ayudan al posicionamiento.</small>
                </div>

                <div class="form-actions">
                  <Button label="Guardar cambios" icon="pi pi-check" :loading="saving" type="submit" />
                </div>
              </form>
            </TabPanel>

            <!-- ── Panel: Contacto ───────────────────────────────────── -->
            <TabPanel value="3">
              <form @submit.prevent="save" class="settings-form">
                <div class="field">
                  <label class="field-label">Email de soporte</label>
                  <div class="input-wrap">
                    <i class="pi pi-envelope input-icon" />
                    <InputText v-model="form.contact_email" type="email" :invalid="!!errors.contact_email" class="with-icon" fluid />
                  </div>
                  <Message v-if="errors.contact_email" severity="error" size="small" variant="simple">{{ errors.contact_email }}</Message>
                </div>

                <div class="form-row">
                  <div class="field flex-1">
                    <label class="field-label">Enlace de Telegram</label>
                    <InputGroup>
                      <InputGroupAddon><i class="pi pi-telegram" /></InputGroupAddon>
                      <InputText v-model="form.telegram_link" placeholder="https://t.me/..." fluid />
                    </InputGroup>
                  </div>
                  <div class="field flex-1">
                    <label class="field-label">WhatsApp de contacto</label>
                    <InputGroup>
                      <InputGroupAddon><i class="pi pi-whatsapp" /></InputGroupAddon>
                      <InputText v-model="form.whatsapp_contact" placeholder="+51..." fluid />
                    </InputGroup>
                  </div>
                </div>

                <div class="form-actions">
                  <Button label="Guardar cambios" icon="pi pi-check" :loading="saving" type="submit" />
                </div>
              </form>
            </TabPanel>

            <!-- ── Panel: Redes Sociales ──────────────────────────────── -->
            <TabPanel value="4">
              <form @submit.prevent="save" class="settings-form">
                <div class="field">
                  <label class="field-label">Facebook URL</label>
                  <InputGroup>
                    <InputGroupAddon><i class="pi pi-facebook" /></InputGroupAddon>
                    <InputText v-model="form.social_links.facebook" placeholder="https://facebook.com/..." fluid />
                  </InputGroup>
                </div>

                <div class="field">
                  <label class="field-label">Instagram URL</label>
                  <InputGroup>
                    <InputGroupAddon><i class="pi pi-instagram" /></InputGroupAddon>
                    <InputText v-model="form.social_links.instagram" placeholder="https://instagram.com/..." fluid />
                  </InputGroup>
                </div>

                <div class="field">
                  <label class="field-label">Twitter / X URL</label>
                  <InputGroup>
                    <InputGroupAddon><i class="pi pi-twitter" /></InputGroupAddon>
                    <InputText v-model="form.social_links.twitter" placeholder="https://twitter.com/..." fluid />
                  </InputGroup>
                </div>

                <div class="form-actions">
                  <Button label="Guardar cambios" icon="pi pi-check" :loading="saving" type="submit" />
                </div>
              </form>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </DataCard>
    </div>
    <Toast />
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataCard from '@/Components/ui/DataCard.vue';

const props = defineProps({
  settings: { type: Object, required: true },
});

const toast = useToast();
const saving = ref(false);
const errors = ref({});

const logoPreview    = ref(null);
const faviconPreview = ref(null);

const form = reactive({
  site_name:        props.settings.site_name,
  site_active:      !!props.settings.site_active,
  contact_email:    props.settings.contact_email,
  telegram_link:    props.settings.telegram_link,
  whatsapp_contact: props.settings.whatsapp_contact,
  social_links:     { ...props.settings.social_links },
  // Brand files
  logo_file:        null,
  favicon_file:     null,
  // SEO
  seo_title:        props.settings.seo_title,
  seo_description:  props.settings.seo_description,
  seo_keywords:     props.settings.seo_keywords || [],
});

function onLogoSelect(e) {
  form.logo_file = e.files[0];
  logoPreview.value = URL.createObjectURL(form.logo_file);
}

function onFaviconSelect(e) {
  form.favicon_file = e.files[0];
  faviconPreview.value = URL.createObjectURL(form.favicon_file);
}

function save() {
  saving.value = true;
  errors.value = {};

  // Usamos router.post con _method PUT para manejar archivos correctamente en Laravel
  const formData = new FormData();
  formData.append('_method', 'PUT');

  // Appending basic fields
  formData.append('site_name', form.site_name);
  formData.append('site_active', form.site_active ? '1' : '0');
  formData.append('contact_email', form.contact_email);
  formData.append('telegram_link', form.telegram_link || '');
  formData.append('whatsapp_contact', form.whatsapp_contact || '');
  formData.append('seo_title', form.seo_title);
  formData.append('seo_description', form.seo_description);

  // Redes sociales (objeto)
  Object.entries(form.social_links).forEach(([key, val]) => {
    formData.append(`social_links[${key}]`, val || '');
  });

  // SEO keywords (array)
  form.seo_keywords.forEach((tag, index) => {
    formData.append(`seo_keywords[${index}]`, tag);
  });

  // Files
  if (form.logo_file) formData.append('logo_file', form.logo_file);
  if (form.favicon_file) formData.append('favicon_file', form.favicon_file);

  router.post(route('settings.general.update'), formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Ajustes guardados', life: 3000 });
      form.logo_file = null;
      form.favicon_file = null;
    },
    onError:   (e) => errors.value = e,
    onFinish:  () => saving.value = false,
  });
}
</script>

<style scoped>
.settings-wrap { max-width: 850px; }
.settings-form { display: flex; flex-direction: column; gap: 1.5rem; padding-top: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.5rem; }
.field-label { font-size: 0.85rem; font-weight: 600; color: var(--c-text-muted); }
.field-hint { font-size: 0.75rem; color: var(--c-text-subtle); margin-top: 0.1rem; }
.form-row { display: flex; gap: 1rem; flex-wrap: wrap; }
.flex-1 { flex: 1; }

.field-toggle {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid var(--c-border);
  border-radius: 12px;
  padding: 1rem 1.25rem;
}
.toggle-label { font-size: 0.9rem; font-weight: 700; color: var(--c-text); margin: 0; }
.toggle-sub { font-size: 0.75rem; color: var(--c-text-muted); margin: 0.2rem 0 0; }

/* Branding */
.branding-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
.brand-item { display: flex; flex-direction: column; gap: 1rem; }
.image-preview-box {
  width: 100%; height: 160px; border-radius: 14px; background: rgba(0,0,0,0.2);
  border: 1px dashed var(--c-border); display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.preview-img { max-width: 90%; max-height: 90%; object-fit: contain; }
.logo-preview { max-height: 70%; }
.favicon-preview { width: 48px; height: 48px; }
.preview-ph { font-size: 2.5rem; color: var(--c-border); }

.input-wrap { position: relative; }
.input-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 0.9rem; color: var(--c-text-subtle); z-index: 1; }
:deep(.p-inputtext.with-icon) { padding-left: 2.25rem; }

.form-actions { display: flex; justify-content: flex-end; border-top: 1px solid var(--c-border); padding-top: 1.5rem; margin-top: 0.5rem; }

.mr-2 { margin-right: 0.5rem; }

@media (max-width: 640px) {
  .branding-grid { grid-template-columns: 1fr; }
}
</style>
