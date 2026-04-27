<template>
  <DashboardLayout :title="`Usuario: ${user.name}`" active="users">
    <!-- Header -->
    <PageHeader
      :title="user.name"
      :subtitle="user.email"
      icon="pi-user"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Usuarios', href: route('admin.users.index') }, { label: user.name }]"
    >
      <template #actions>
        <div class="flex gap-2">
          <Button
            :label="user.is_active ? 'Suspender' : 'Activar'"
            :icon="user.is_active ? 'pi pi-ban' : 'pi pi-check'"
            :severity="user.is_active ? 'warn' : 'success'"
            outlined size="small"
            @click="confirmToggleStatus"
          />
          <Button
            label="Cambiar Rol"
            icon="pi pi-shield"
            severity="secondary"
            outlined size="small"
            @click="showRoleModal = true"
          />
          <Button
            label="Eliminar"
            icon="pi pi-trash"
            severity="danger"
            size="small"
            @click="confirmDelete"
          />
        </div>
      </template>
    </PageHeader>

    <!-- Hero Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
      <StatsCard title="Órdenes" :value="user.orders_count ?? 0" icon="pi-shopping-bag" color="primary" />
      <StatsCard title="Balance Wallet" :value="`${user.wallet?.balance ?? 0} NT`" icon="pi-wallet" color="success" />
      <StatsCard title="Registro" :value="formatDate(user.created_at)" icon="pi-calendar" color="info" />
      <StatsCard title="Último Acceso" :value="user.last_login_at ? formatDate(user.last_login_at, 'relative') : 'Nunca'" icon="pi-clock" color="warn" />
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Recent Orders -->
      <DataCard title="Últimas Órdenes" icon="pi pi-receipt">
        <div v-if="user.orders?.length" class="space-y-3">
          <div v-for="order in user.orders" :key="order.id" class="flex items-center justify-between p-2 border-b border-surface-700/50 last:border-0">
            <div class="flex flex-col min-w-0">
              <Link :href="route('admin.store.orders.show', order.ulid)" class="text-brand-400 font-bold hover:underline truncate">
                #{{ order.ulid.slice(-8).toUpperCase() }}
              </Link>
              <span class="text-[10px] text-surface-400">{{ formatDate(order.created_at) }}</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="font-mono text-sm whitespace-nowrap">{{ order.currency }} {{ Number(order.total).toFixed(2) }}</span>
              <StatusBadge :status="order.status" class="scale-90" />
            </div>
          </div>
        </div>
        <EmptyState v-else icon="pi-receipt" title="Sin órdenes" description="Este usuario aún no ha realizado compras." />
      </DataCard>

      <!-- User Information -->
      <DataCard title="Información de Cuenta" icon="pi pi-info-circle">
        <div class="info-list">
          <div class="info-row">
            <span class="info-key">ID (ULID)</span>
            <span class="info-val mono text-xs">{{ user.ulid }}</span>
          </div>
          <div class="info-row">
            <span class="info-key">Username</span>
            <span class="info-val">{{ user.username }}</span>
          </div>
          <div class="info-row">
            <span class="info-key">Email</span>
            <span class="info-val">{{ user.email }}</span>
          </div>
          <div class="info-row">
             <span class="info-key">Verificado</span>
             <Tag :value="user.email_verified_at ? 'SÍ' : 'NO'" :severity="user.email_verified_at ? 'success' : 'warn'" rounded />
          </div>
        </div>
      </DataCard>

      <!-- Audit Logs -->
      <DataCard title="Log de Auditoría" icon="pi pi-history" class="lg:col-span-2">
        <div v-if="auditLogs.length" class="audit-list max-h-[400px] overflow-y-auto pr-2">
          <div v-for="log in auditLogs" :key="log.id" class="flex flex-col sm:flex-row sm:items-center gap-2 py-2 border-b border-surface-700/50 last:border-0">
            <span class="font-bold text-sm min-w-[140px]">{{ log.action }}</span>
            <span class="text-xs text-surface-400 min-w-[150px]">{{ formatDate(log.created_at) }}</span>
            <span v-if="log.properties" class="text-xs text-surface-500 truncate italic">
              {{ JSON.stringify(log.properties) }}
            </span>
          </div>
        </div>
        <EmptyState v-else icon="pi-history" title="Sin registros" description="No hay eventos registrados para este usuario." />
      </DataCard>

    </div>

    <!-- Role Modal -->
    <Dialog v-model:visible="showRoleModal" modal header="Cambiar Rol" :style="{ width: '350px' }">
      <div class="flex flex-col gap-4 py-2">
        <p class="text-sm text-surface-400">Selecciona el nuevo rol para <b>{{ user.name }}</b></p>
        <div class="flex flex-col gap-2">
          <div v-for="r in ['buyer', 'seller', 'admin']" :key="r" class="flex items-center gap-2 p-2 rounded hover:bg-surface-800 cursor-pointer" @click="selectedRole = r">
            <RadioButton v-model="selectedRole" :value="r" />
            <span class="capitalize">{{ r }}</span>
            <Tag :value="r" :severity="getRoleSeverity(r)" class="ml-auto" />
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" text severity="secondary" @click="showRoleModal = false" />
        <Button label="Guardar Cambios" severity="primary" @click="applyRole" />
      </template>
    </Dialog>

  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useUtils } from '@/composables/useUtils';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataCard from '@/Components/ui/DataCard.vue';
import StatsCard from '@/Components/ui/StatsCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';

const props = defineProps({
  user:      { type: Object, required: true },
  auditLogs: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const toast = useToast();
const { formatDate, getRoleSeverity } = useUtils();

const showRoleModal = ref(false);
const selectedRole  = ref(props.user.role);

function confirmToggleStatus() {
  const action = props.user.is_active ? 'suspender' : 'activar';
  confirm.require({
    header: `¿${action.charAt(0).toUpperCase() + action.slice(1)} usuario?`,
    message: `¿Estás seguro que deseas ${action} la cuenta de ${props.user.name}?`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Confirmar',
    acceptClass: props.user.is_active ? 'p-button-warning' : 'p-button-success',
    accept: () => {
      router.patch(route('admin.users.toggle-status', props.user.id), {}, {
        onSuccess: () => toast.add({ severity: 'success', summary: `Usuario ${props.user.is_active ? 'activado' : 'suspendido'}`, life: 3000 })
      });
    }
  });
}

function applyRole() {
  if (selectedRole.value === props.user.role) { showRoleModal.value = false; return; }
  router.patch(route('admin.users.role', props.user.id), { role: selectedRole.value }, {
    onSuccess: () => { 
      showRoleModal.value = false;
      toast.add({ severity: 'success', summary: 'Rol actualizado', life: 3000 });
    },
    preserveScroll: true,
  });
}

function confirmDelete() {
  confirm.require({
    header: '¿Eliminar usuario?',
    message: `Se eliminará permanentemente la cuenta de ${props.user.name}. Esta acción no se puede deshacer.`,
    icon: 'pi pi-trash',
    acceptLabel: 'Sí, eliminar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route('admin.users.destroy', props.user.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Usuario eliminado', life: 3000 })
      });
    }
  });
}
</script>

<style scoped>
.info-list { display: flex; flex-direction: column; gap: 0.75rem; }
.info-row  { display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; }
.info-key  { color: var(--c-text-subtle); }
.info-val  { color: var(--c-text); }

/* Custom scrollbar for audit logs */
.audit-list::-webkit-scrollbar { width: 4px; }
.audit-list::-webkit-scrollbar-thumb { background: var(--c-border); border-radius: 10px; }
</style>
