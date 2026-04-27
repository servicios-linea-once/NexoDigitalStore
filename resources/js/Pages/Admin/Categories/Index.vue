<template>
  <DashboardLayout title="Categorías" active="categories">
    <PageHeader
      title="Categorías"
      subtitle="Organiza el catálogo de productos"
      icon="pi-folder"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Categorías' }]"
    >
      <template #actions>
        <Button v-if="can('categories.create')" label="Nueva categoría" icon="pi pi-plus" @click="openCreate" />
      </template>
    </PageHeader>

    <DataCard :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <SearchFilter v-model="filters.global" placeholder="Buscar categoría..." @search="applyFilters" class="toolbar-search" />
          <span class="results-badge">{{ categories.total }} categorías</span>
        </div>
      </template>

      <DataTable :value="categories.data" size="small" :rowHover="true" :loading="loading">
        <Column header="Categoría" style="min-width: 200px">
          <template #body="{ data }">
            <div class="cell-cat">
              <div class="cat-icon" :style="data.color ? `background: ${data.color}22; color: ${data.color}` : ''">
                <i :class="['pi', data.icon || 'pi-tag']" />
              </div>
              <div>
                <p class="cell-name">{{ data.name }}</p>
                <p class="cell-slug">{{ data.slug }}</p>
              </div>
            </div>
          </template>
        </Column>
        <Column header="Productos" style="width: 110px">
          <template #body="{ data }">
            <Tag :value="String(data.products_count ?? 0)" severity="secondary" rounded />
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="cell-actions">
              <Button v-if="can('categories.edit')"   icon="pi pi-pencil" size="small" text severity="info"    v-tooltip.top="'Editar'"   @click="openEdit(data)" />
              <Button v-if="can('categories.delete')" icon="pi pi-trash"  size="small" text severity="danger"  v-tooltip.top="'Eliminar'" @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
        <template #empty>
          <EmptyState icon="pi-folder" title="Sin categorías" description="Crea la primera categoría para organizar el catálogo." action-label="Nueva categoría" @action="openCreate" />
        </template>
      </DataTable>

      <template #footer>
        <Paginator :rows="categories.per_page" :totalRecords="categories.total" :first="(categories.current_page - 1) * categories.per_page" @page="goPage($event.page + 1)" />
      </template>
    </DataCard>

    <!-- Create / Edit Dialog -->
    <Dialog v-model:visible="dialog" modal :header="editing ? 'Editar categoría' : 'Nueva categoría'" :style="{ width: '420px' }" :draggable="false">
      <form @submit.prevent="save" class="dialog-form">
        <div class="field">
          <label class="field-label">Nombre *</label>
          <InputText v-model="form.name" placeholder="Ej: Juegos PC" :invalid="!!errors.name" fluid />
          <Message v-if="errors.name" severity="error" size="small" variant="simple">{{ errors.name }}</Message>
        </div>
        <div class="field">
          <label class="field-label">Ícono PrimeIcons</label>
          <IconField>
            <InputIcon class="pi pi-tag" />
            <InputText v-model="form.icon" placeholder="pi-gamepad, pi-star, pi-tag..." fluid />
          </IconField>
          <small class="field-hint">Ver íconos en <a href="https://primevue.org/icons" target="_blank">primevue.org/icons</a></small>
        </div>
        <div class="field">
          <label class="field-label">Color de acento</label>
          <div class="color-row">
            <ColorPicker v-model="form.color" />
            <InputText v-model="form.color" placeholder="#6366f1" fluid />
          </div>
        </div>
        <!-- Preview -->
        <div class="cat-preview" v-if="form.name">
          <div class="cat-icon-preview" :style="form.color ? `background: #${form.color}22; color: #${form.color}` : ''">
            <i :class="['pi', form.icon || 'pi-tag']" />
          </div>
          <span>{{ form.name }}</span>
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="dialog = false" />
        <Button :label="editing ? 'Actualizar' : 'Crear'" icon="pi pi-check" :loading="saving" @click="save" />
      </template>
    </Dialog>

    <ConfirmDialog />
    <Toast />
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast }   from 'primevue/usetoast';
import { usePermissions } from '@/composables/usePermissions';
import { useFilters }     from '@/composables/useFilters';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader    from '@/Components/ui/PageHeader.vue';
import DataCard      from '@/Components/ui/DataCard.vue';
import EmptyState    from '@/Components/ui/EmptyState.vue';
import SearchFilter  from '@/Components/ui/SearchFilter.vue';

const props = defineProps({
  categories: { type: Object, required: true },
  filters:    { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const toast   = useToast();
const { can } = usePermissions();

const { filters, loading, applyFilters, goPage } = useFilters('admin.categories.index', {
  global: props.filters.filter?.global || '',
});

const dialog  = ref(false);
const editing = ref(null);
const saving  = ref(false);
const errors  = ref({});
const form    = reactive({ name: '', icon: '', color: '' });

function openCreate() { editing.value = null; Object.assign(form, { name: '', icon: '', color: '' }); errors.value = {}; dialog.value = true; }
function openEdit(cat) { editing.value = cat; Object.assign(form, { name: cat.name, icon: cat.icon || '', color: (cat.color || '').replace('#', '') }); errors.value = {}; dialog.value = true; }

function save() {
  saving.value = true;
  const data = { name: form.name, icon: form.icon || null, color: form.color ? `#${form.color.replace('#', '')}` : null };
  
  if (editing.value) {
    router.put(route('admin.categories.update', editing.value.id), data, { onSuccess: ok, onError: err, onFinish: fin });
  } else {
    router.post(route('admin.categories.store'), data, { onSuccess: ok, onError: err, onFinish: fin });
  }
}
function ok()  { dialog.value = false; toast.add({ severity: 'success', summary: 'Categoría guardada', life: 3000 }); }
function err(e){ errors.value = e; }
function fin() { saving.value = false; }

function confirmDelete(cat) {
  confirm.require({
    header:       '¿Eliminar categoría?',
    message:      cat.products_count > 0 ? `"${cat.name}" tiene ${cat.products_count} productos. No se puede eliminar.` : `¿Eliminar "${cat.name}"? Esta acción no se puede deshacer.`,
    icon:         'pi pi-exclamation-triangle',
    acceptLabel:  cat.products_count > 0 ? 'Entendido' : 'Sí, eliminar',
    rejectLabel:  cat.products_count > 0 ? null : 'Cancelar',
    acceptClass:  'p-button-danger',
    accept: () => {
      if (cat.products_count > 0) return;
      router.delete(route('admin.categories.destroy', cat.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Categoría eliminada', life: 3000 }),
      });
    },
  });
}
</script>

<style scoped>
.toolbar-row { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding: 0.875rem 1.125rem; }
.toolbar-search { flex: 1; min-width: 240px; }
.results-badge { background: var(--c-primary-muted); color: var(--c-primary); font-size: 0.78rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 8px; margin-left: auto; }

.cell-cat    { display: flex; align-items: center; gap: 0.75rem; }
.cat-icon    { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; background: var(--c-primary-muted); color: var(--c-primary); }
.cell-name   { font-size: 0.85rem; font-weight: 600; color: var(--c-text); margin: 0; }
.cell-slug   { font-size: 0.72rem; color: var(--c-text-muted); margin: 0; }
.cell-actions { display: flex; gap: 0.25rem; }

.dialog-form  { display: flex; flex-direction: column; gap: 1rem; padding: 0.5rem 0; }
.field        { display: flex; flex-direction: column; gap: 0.375rem; }
.field-label  { font-size: 0.8rem; font-weight: 600; color: var(--c-text-muted); }
.field-hint   { font-size: 0.72rem; color: var(--c-text-subtle); }
.field-hint a { color: var(--c-primary); text-decoration: none; }

.color-row { display: flex; align-items: center; gap: 0.75rem; }
.cat-preview { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--c-card); border-radius: 10px; font-size: 0.85rem; font-weight: 600; color: var(--c-text); }
.cat-icon-preview { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: var(--c-primary-muted); color: var(--c-primary); }
</style>
