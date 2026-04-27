<!-- resources/js/Components/Admin/QuickActions.vue -->
<template>
  <div class="quick-dial-container">
    <!-- SpeedDial flotante en la esquina inferior derecha -->
    <SpeedDial
      :model="items"
      direction="up"
      :transitionDelay="80"
      showIcon="pi pi-bolt"
      hideIcon="pi pi-times"
      buttonClass="p-button-primary p-button-rounded p-button-raised custom-dial-btn"
      :tooltipOptions="{ position: 'left' }"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SpeedDial from 'primevue/speeddial';

const items = ref([
  {
    label: 'Nuevo Producto',
    icon: 'pi pi-plus',
    command: () => router.visit(route('admin.store.products.create')),
  },
  {
    label: 'Gestionar Claves',
    icon: 'pi pi-key',
    command: () => router.visit(route('admin.store.keys.index')),
  },
  {
    label: 'Crear Promoción',
    icon: 'pi pi-percentage',
    command: () => router.visit(route('admin.store.promotions.create')),
  },
  {
    label: 'Entregas Pendientes',
    icon: 'pi pi-send',
    command: () => router.visit(route('admin.store.deliveries.index')),
  },
  {
    label: 'Usuarios',
    icon: 'pi pi-users',
    command: () => router.visit(route('admin.users.index')),
  },
  {
    label: 'Configuración',
    icon: 'pi pi-cog',
    command: () => router.visit(route('admin.settings')),
  }
]);
</script>

<style scoped>
.quick-dial-container {
  position: fixed;
  right: 2rem;
  bottom: 2rem;
  height: 300px; /* Espacio para que se despliegue hacia arriba */
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  pointer-events: none; /* Dejar pasar clicks fuera de los botones */
}

/* Habilitar eventos solo para el SpeedDial */
.quick-dial-container :deep(.p-speeddial) {
  pointer-events: auto;
}

:deep(.custom-dial-btn) {
  width: 3.5rem !important;
  height: 3.5rem !important;
  background: linear-gradient(135deg, var(--p-primary-color), var(--p-primary-600)) !important;
  border: none !important;
  box-shadow: 0 8px 25px rgba(var(--p-primary-500-rgb), 0.4) !important;
}

:deep(.p-speeddial-action) {
  background: var(--p-surface-800) !important;
  border: 1px solid var(--p-surface-700) !important;
  color: var(--p-text-color) !important;
  width: 3rem !important;
  height: 3rem !important;
}

:deep(.p-speeddial-action:hover) {
  background: var(--p-surface-700) !important;
  color: var(--p-primary-color) !important;
}

@media (max-width: 768px) {
  .quick-dial-container {
    right: 1.25rem;
    bottom: 1.25rem;
  }
}
</style>
