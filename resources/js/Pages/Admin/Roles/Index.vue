<template>
  <DashboardLayout title="Gestión de Roles" active="roles">
    <PageHeader
      title="Roles y Permisos"
      subtitle="Administra los perfiles de acceso y sus permisos de Spatie"
      icon="pi-shield"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Roles' }]"
    >
      <template #actions>
        <Button v-if="can('roles.create')" label="Nuevo Rol" icon="pi pi-plus" @click="openCreateDialog" />
      </template>
    </PageHeader>

    <!-- Role Cards -->
    <div class="roles-grid">
      <div
        v-for="(role, idx) in roles"
        :key="role.id"
        class="role-card"
        v-motion
        :initial="{ opacity: 0, y: 24 }"
        :visibleOnce="{ opacity: 1, y: 0, transition: { duration: 350, delay: idx * 80 } }"
      >
        <div class="role-card-header" :class="`role-${role.color}`">
          <div class="role-icon">
            <i :class="['pi', roleIcon(role.name)]" />
          </div>
          <div class="role-name-wrap">
            <h3 class="role-name">{{ role.name.toUpperCase() }}</h3>
            <p class="role-desc">Gestiona accesos específicos</p>
          </div>
          <div class="role-stats">
            <span class="role-count">{{ role.total }}</span>
            <span class="role-count-label">usuarios</span>
          </div>
        </div>

        <div class="role-permissions">
          <p class="perm-title">Permisos ({{ role.permissions.length }})</p>
          <div class="perm-badges">
            <Tag v-for="perm in role.permissions.slice(0, 6)" :key="perm" :value="perm" severity="secondary" class="perm-tag" />
            <span v-if="role.permissions.length > 6" class="perm-more">+{{ role.permissions.length - 6 }} más</span>
          </div>
        </div>

        <div class="role-footer">
          <Button v-if="can('roles.edit')" icon="pi pi-pencil" text rounded severity="secondary" @click="openEditDialog(role)" v-tooltip.top="'Editar rol y permisos'" />
          <Button v-if="can('roles.delete')" icon="pi pi-trash" text rounded severity="danger" 
            :disabled="['admin', 'seller', 'buyer'].includes(role.name)" 
            @click="confirmDelete(role)" v-tooltip.top="'Eliminar rol'" />
          <div class="ml-auto">
             <span class="stat-badge stat-active">{{ role.active }} activos</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Assign Role Section -->
    <DataCard icon="pi-user-edit" title="Asignar Rol a Usuario" subtitle="Busca un usuario y cambia su rol" class="mt-6">
      <div class="assign-form">
        <div class="assign-search">
          <label class="field-label">Buscar usuario</label>
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="searchQuery" placeholder="Nombre o correo..." fluid @input="searchUsers" />
          </IconField>
        </div>

        <div v-if="searchResults.length" class="search-results">
          <div v-for="user in searchResults" :key="user.id" class="user-result" :class="{ selected: selectedUser?.id === user.id }" @click="selectedUser = user">
            <Avatar :label="userInitials(user)" shape="circle" size="small" />
            <div class="user-result-info">
              <p class="user-result-name">{{ user.name }}</p>
              <p class="user-result-email">{{ user.email }}</p>
            </div>
            <Tag :value="user.role" :severity="roleSeverity(user.role)" rounded size="small" />
          </div>
        </div>

        <div v-if="selectedUser" class="assign-action">
          <Divider />
          <p class="field-label">Nuevo rol para <strong>{{ selectedUser.name }}</strong></p>
          <div class="role-options">
            <div v-for="role in roles" :key="role.id" class="role-option" :class="{ selected: newRole === role.name }" @click="newRole = role.name">
              <i :class="['pi', roleIcon(role.name)]" />
              <span>{{ role.name }}</span>
              <i v-if="newRole === role.name" class="pi pi-check check-indicator" />
            </div>
          </div>
          <Button label="Confirmar cambio de rol" icon="pi pi-check" :disabled="!newRole || newRole === selectedUser.role" :loading="assigning" @click="doAssign" />
        </div>
      </div>
    </DataCard>

    <!-- Dialog: Create/Edit Role -->
    <Dialog v-model:visible="roleDialog" :header="isEditing ? 'Editar Rol' : 'Nuevo Rol'" modal class="role-modal" :style="{width: '500px'}">
      <div class="dialog-form">
        <div class="field">
          <label class="field-label">Nombre del Rol</label>
          <InputText v-model="roleForm.name" placeholder="Ej: moderador" fluid :disabled="isEditing && ['admin', 'seller', 'buyer'].includes(roleForm.name)" />
        </div>
        <div class="field">
          <label class="field-label">Permisos de Spatie</label>
          <div class="permissions-selector">
            <div v-for="perm in allPermissions" :key="perm" class="perm-check">
               <Checkbox v-model="roleForm.permissions" :value="perm" :inputId="perm" />
               <label :for="perm">{{ perm }}</label>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="roleDialog = false" />
        <Button :label="isEditing ? 'Actualizar' : 'Crear'" icon="pi pi-check" :loading="saving" @click="saveRole" />
      </template>
    </Dialog>

    <ConfirmDialog /><Toast />
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataCard from '@/Components/ui/DataCard.vue';

const props = defineProps({
  roles: Array,
  allPermissions: Array,
  recentChanges: Array,
});

const confirm = useConfirm();
const toast   = useToast();
const { can } = usePermissions();

const roleDialog = ref(false);
const isEditing  = ref(false);
const saving     = ref(false);
const roleForm   = reactive({ id: null, name: '', permissions: [] });

const searchQuery   = ref('');
const searchResults = ref([]);
const selectedUser  = ref(null);
const newRole       = ref('');
const assigning     = ref(false);

function openCreateDialog() {
  isEditing.value = false;
  Object.assign(roleForm, { id: null, name: '', permissions: [] });
  roleDialog.value = true;
}

function openEditDialog(role) {
  isEditing.value = true;
  Object.assign(roleForm, { id: role.id, name: role.name, permissions: [...role.permissions] });
  roleDialog.value = true;
}

function saveRole() {
  saving.value = true;
  const method = isEditing.value ? 'put' : 'post';
  const url    = isEditing.value ? route('admin.roles.update', roleForm.id) : route('admin.roles.store');

  router[method](url, roleForm, {
    onSuccess: () => {
      toast.add({ severity: 'success', summary: isEditing.value ? 'Rol actualizado' : 'Rol creado' });
      roleDialog.value = false;
    },
    onFinish: () => saving.value = false,
  });
}

function confirmDelete(role) {
  confirm.require({
    header: 'Eliminar Rol',
    message: `¿Estás seguro de eliminar el rol "${role.name}"? Esta acción no se puede deshacer.`,
    icon: 'pi pi-exclamation-trash',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route('admin.roles.destroy', role.id), {
        onSuccess: () => toast.add({ severity: 'info', summary: 'Rol eliminado' }),
      });
    }
  });
}

function searchUsers() {
  if (searchQuery.value.length < 2) { searchResults.value = []; return; }
  fetch(`/api/v1/search?q=${encodeURIComponent(searchQuery.value)}`)
    .then(r => r.json())
    .then(data => {
       // Filtramos solo usuarios de la búsqueda global
       searchResults.value = data.filter(i => i.type === 'User').map(i => ({ id: i.id, name: i.title, email: i.url.split('/').pop() }));
    });
}

function doAssign() {
  assigning.value = true;
  router.post(route('admin.roles.assign'), { user_id: selectedUser.value.id, role: newRole.value }, {
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Rol asignado' });
      selectedUser.value = null;
    },
    onFinish: () => assigning.value = false
  });
}

function roleIcon(name) {
  if (name.includes('admin')) return 'pi-shield';
  if (name.includes('seller')) return 'pi-shop';
  return 'pi-user';
}

function roleSeverity(role) {
  return { admin: 'danger', seller: 'success', buyer: 'info' }[role] || 'secondary';
}

function userInitials(u) { return (u?.name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2); }
</script>

<style scoped>
.roles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
.role-card { background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 16px; display: flex; flex-direction: column; }
.role-card-header { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border-bottom: 1px solid var(--c-border); }
.role-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

.role-danger .role-icon { background: rgba(239,68,68,0.1); color: #ef4444; }
.role-success .role-icon { background: rgba(16,185,129,0.1); color: #10b981; }
.role-info .role-icon { background: rgba(6,182,212,0.1); color: #06b6d4; }
.role-primary .role-icon { background: rgba(139,92,246,0.1); color: #a78bfa; }

.role-name-wrap { min-width: 0; flex: 1; overflow: hidden; }
.role-name { font-size: 1rem; font-weight: 800; color: var(--c-text); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.role-desc { font-size: 0.75rem; color: var(--c-text-muted); margin: 0; }
.role-stats { margin-left: auto; text-align: right; }
.role-count { font-size: 1.4rem; font-weight: 900; display: block; }
.role-count-label { font-size: 0.65rem; color: var(--c-text-muted); text-transform: uppercase; }

.role-permissions { padding: 1.25rem; flex: 1; }
.perm-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--c-text-subtle); margin-bottom: 0.75rem; }
.perm-badges { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.perm-tag { font-size: 0.65rem !important; }
.perm-more { font-size: 0.7rem; color: var(--c-text-muted); font-style: italic; }

.role-footer { padding: 0.75rem 1.25rem; background: rgba(0,0,0,0.1); border-top: 1px solid var(--c-border); display: flex; align-items: center; gap: 0.5rem; }
.stat-badge { font-size: 0.7rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 6px; background: rgba(16,185,129,0.1); color: #10b981; }

.permissions-selector { max-height: 250px; overflow-y: auto; border: 1px solid var(--c-border); border-radius: 8px; padding: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem; }
.perm-check { display: flex; align-items: center; gap: 0.75rem; font-size: 0.85rem; }

.assign-form { display: flex; flex-direction: column; gap: 1rem; }
.user-result { display: flex; align-items: center; gap: 1rem; padding: 0.75rem; border-radius: 10px; cursor: pointer; border: 1px solid transparent; }
.user-result:hover { background: rgba(255,255,255,0.05); }
.user-result.selected { border-color: var(--c-primary); background: rgba(139,92,246,0.05); }
.user-result-info { flex: 1; }
.user-result-name { font-size: 0.9rem; font-weight: 700; margin: 0; }
.user-result-email { font-size: 0.75rem; color: var(--c-text-muted); margin: 0; }

.role-options { display: flex; flex-wrap: wrap; gap: 0.75rem; }
.role-option { padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid var(--c-border); cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.85rem; }
.role-option.selected { border-color: var(--c-primary); color: var(--c-primary); background: rgba(139,92,246,0.1); }

.mt-6 { margin-top: 1.5rem; }
.ml-auto { margin-left: auto; }
</style>
