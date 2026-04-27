<template>
  <DashboardLayout title="Usuarios" active="users">
    <PageHeader
      title="Gestión de Usuarios"
      subtitle="Administra todos los usuarios del sistema"
      icon="pi-users"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Usuarios' }]"
    >
      <template #actions>
        <span class="results-badge">{{ users.total }} usuarios</span>
      </template>
    </PageHeader>

    <!-- Toolbar -->
    <DataCard class="mb-4" :noPadding="true">
      <template #header>
        <div class="toolbar-row">
          <!-- Search -->
          <SearchFilter v-model="filters.global" placeholder="Buscar por nombre o email..." @search="applyFilters" class="toolbar-search" />

          <!-- Filters -->
          <div class="toolbar-filters">
            <Select v-model="filters.role" :options="roleOptions" optionLabel="label" optionValue="value" placeholder="Todos los roles" showClear @change="applyFilters" />
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Todos los estados" showClear @change="applyFilters" />
          </div>
        </div>
      </template>

      <!-- DataTable -->
      <DataTable
        :value="users.data"
        :loading="loading"
        size="small"
        :rowHover="true"
        scrollable
        scrollHeight="calc(100vh - 340px)"
      >
        <!-- User -->
        <Column header="Usuario" style="min-width: 220px">
          <template #body="{ data }">
            <div class="cell-user">
              <Avatar :label="getInitials(data.name)" :image="data.avatar" shape="circle" size="small" />
              <div>
                <p class="cell-name">{{ data.name }}</p>
                <p class="cell-email">{{ data.email }}</p>
              </div>
            </div>
          </template>
        </Column>

        <!-- Role -->
        <Column header="Rol" style="width: 120px">
          <template #body="{ data }">
            <Tag :value="data.role" :severity="getRoleSeverity(data.role)" rounded size="small" />
          </template>
        </Column>

        <!-- Status -->
        <Column header="Estado" style="width: 110px">
          <template #body="{ data }">
            <Tag
              :value="data.is_active ? 'Activo' : 'Suspendido'"
              :severity="data.is_active ? 'success' : 'secondary'"
              :icon="data.is_active ? 'pi pi-check-circle' : 'pi pi-ban'"
              rounded size="small"
            />
          </template>
        </Column>

        <!-- Orders -->
        <Column header="Órdenes" style="width: 90px">
          <template #body="{ data }">
            <span class="cell-number">{{ data.orders_count ?? 0 }}</span>
          </template>
        </Column>

        <!-- Actions -->
        <Column header="Acciones" style="width: 160px" frozen alignFrozen="right">
          <template #body="{ data }">
            <div class="cell-actions">
              <Button v-if="can('users.view')"
                icon="pi pi-eye" v-tooltip.top="'Ver detalle'"
                size="small" text severity="secondary"
                @click="$inertia.visit(route('admin.users.show', data.id))"
              />
              <Button v-if="can('users.edit')"
                icon="pi pi-pencil" v-tooltip.top="'Editar'"
                size="small" text severity="info"
                @click="openEdit(data)"
              />
              <Button v-if="can('users.edit')"
                :icon="data.is_active ? 'pi pi-ban' : 'pi pi-check'"
                v-tooltip.top="data.is_active ? 'Suspender' : 'Activar'"
                size="small" text :severity="data.is_active ? 'warn' : 'success'"
                @click="toggleStatus(data)"
              />
              <Button v-if="can('users.delete')"
                icon="pi pi-trash" v-tooltip.top="'Eliminar'"
                size="small" text severity="danger"
                @click="confirmDelete(data)"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <EmptyState icon="pi-users" title="Sin usuarios" description="No hay usuarios que coincidan con los filtros." />
        </template>
        <template #loading>
          <TableSkeleton :rows="8" :cols="5" />
        </template>
      </DataTable>

      <!-- Pagination -->
      <template #footer>
        <Paginator
          :rows="users.per_page"
          :totalRecords="users.total"
          :first="(users.current_page - 1) * users.per_page"
          @page="goPage($event.page + 1)"
          class="table-paginator"
        />
      </template>
    </DataCard>

    <!-- Edit Dialog -->
    <Dialog v-model:visible="editDialog" modal header="Editar usuario" :style="{ width: '440px' }" :draggable="false">
      <form @submit.prevent="saveEdit" class="dialog-form">
        <div class="field">
          <label class="field-label">Nombre</label>
          <InputText v-model="editForm.name" :invalid="!!editErrors.name" fluid />
          <Message v-if="editErrors.name" severity="error" size="small" variant="simple">{{ editErrors.name }}</Message>
        </div>
        <div class="field">
          <label class="field-label">Email</label>
          <InputText v-model="editForm.email" type="email" :invalid="!!editErrors.email" fluid />
          <Message v-if="editErrors.email" severity="error" size="small" variant="simple">{{ editErrors.email }}</Message>
        </div>
        <div v-if="can('users.role')" class="field">
          <label class="field-label">Rol</label>
          <Select v-model="editForm.role" :options="roleOptions.filter(r => r.value)" optionLabel="label" optionValue="value" fluid />
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="editDialog = false" />
        <Button label="Guardar" icon="pi pi-check" :loading="saving" @click="saveEdit" />
      </template>
    </Dialog>
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast }   from 'primevue/usetoast';
import { usePermissions } from '@/composables/usePermissions';
import { useFilters }     from '@/composables/useFilters';
import { useUtils }       from '@/composables/useUtils';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader   from '@/Components/ui/PageHeader.vue';
import DataCard     from '@/Components/ui/DataCard.vue';
import EmptyState   from '@/Components/ui/EmptyState.vue';
import SearchFilter from '@/Components/ui/SearchFilter.vue';
import TableSkeleton from '@/Components/ui/TableSkeleton.vue';

const props = defineProps({
  users:   { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const toast   = useToast();
const { can } = usePermissions();
const { getInitials, getRoleSeverity } = useUtils();

const { filters, loading, applyFilters, goPage } = useFilters('admin.users.index', {
  global: props.filters.filter?.global || '',
  role:   props.filters.filter?.role   || null,
  status: props.filters.filter?.status || null,
});

const editDialog = ref(false);
const saving     = ref(false);
const editErrors = ref({});
const editTarget = ref(null);
const editForm   = reactive({ name: '', email: '', role: '' });

const roleOptions = [
  { label: 'Todos los roles', value: null },
  { label: '🛡 Administrador', value: 'admin' },
  { label: '🛍 Vendedor',      value: 'seller' },
  { label: '👤 Comprador',     value: 'buyer' },
];
const statusOptions = [
  { label: 'Todos',      value: null },
  { label: 'Activos',    value: 'active' },
  { label: 'Suspendidos',value: 'inactive' },
];

function openEdit(user) {
  editTarget.value = user;
  editForm.name  = user.name;
  editForm.email = user.email;
  editForm.role  = user.role;
  editErrors.value = {};
  editDialog.value = true;
}

function saveEdit() {
  saving.value = true;
  router.put(route('admin.users.update', editTarget.value.id), {
    name:  editForm.name,
    email: editForm.email,
  }, {
    onSuccess: () => { editDialog.value = false; toast.add({ severity: 'success', summary: 'Usuario actualizado', life: 3000 }); },
    onError:   (e) => { editErrors.value = e; },
    onFinish:  () => { saving.value = false; },
  });

  // Change role separately if changed
  if (can('users.role') && editForm.role !== editTarget.value.role) {
    router.patch(route('admin.users.role', editTarget.value.id), { role: editForm.role });
  }
}

function toggleStatus(user) {
  router.patch(route('admin.users.toggle-status', user.id), {}, {
    onSuccess: () => toast.add({ severity: 'info', summary: `Usuario ${user.is_active ? 'suspendido' : 'activado'}`, life: 3000 }),
  });
}

function confirmDelete(user) {
  confirm.require({
    header:       '¿Eliminar usuario?',
    message:      `Se eliminará permanentemente a "${user.name}". Esta acción no se puede deshacer.`,
    icon:         'pi pi-exclamation-triangle',
    acceptLabel:  'Sí, eliminar',
    rejectLabel:  'Cancelar',
    acceptClass:  'p-button-danger',
    accept: () => {
      router.delete(route('admin.users.destroy', user.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Usuario eliminado', life: 3000 }),
      });
    },
  });
}
</script>

<style scoped>
.mb-4 { margin-bottom: 1rem; }
.results-badge {
  background: var(--c-primary-muted); color: var(--c-primary);
  font-size: 0.78rem; font-weight: 700; padding: 0.25rem 0.75rem; border-radius: 8px;
}
.toolbar-row {
  display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
  padding: 0.875rem 1.125rem;
}
.toolbar-search  { flex: 1; min-width: 240px; }
.toolbar-filters { display: flex; gap: 0.625rem; flex-wrap: wrap; }

.cell-user  { display: flex; align-items: center; gap: 0.625rem; }
.cell-name  { font-size: 0.85rem; font-weight: 600; color: var(--c-text); margin: 0; }
.cell-email { font-size: 0.75rem; color: var(--c-text-muted); margin: 0; }
.cell-number { font-size: 0.85rem; font-weight: 600; color: var(--c-text); }
.cell-actions { display: flex; gap: 0.25rem; align-items: center; }

.table-paginator { border-top: 1px solid var(--c-border); }

.dialog-form { display: flex; flex-direction: column; gap: 1rem; padding: 0.5rem 0; }
.field { display: flex; flex-direction: column; gap: 0.375rem; }
.field-label { font-size: 0.8rem; font-weight: 600; color: var(--c-text-muted); }
</style>
