<!-- Components/ui/StatusBadge.vue — Badge de estado semántico -->
<template>
  <Tag
    :severity="severity"
    :value="label || statusMap[status]?.label || status"
    :icon="icon || statusMap[status]?.icon"
    rounded
  />
</template>

<script setup>
const props = defineProps({
  status: { type: String, required: true },
  label:  { type: String, default: '' },
  icon:   { type: String, default: '' },
});

const statusMap = {
  // Orders / Payments
  pending:    { label: 'Pendiente',   icon: 'pi pi-clock',        severity: 'warn' },
  paid:       { label: 'Pagado',      icon: 'pi pi-check-circle', severity: 'success' },
  processing: { label: 'Procesando',  icon: 'pi pi-spin pi-spinner', severity: 'info' },
  completed:  { label: 'Completado',  icon: 'pi pi-check',        severity: 'success' },
  cancelled:  { label: 'Cancelado',   icon: 'pi pi-times',        severity: 'danger' },
  refunded:   { label: 'Reembolsado', icon: 'pi pi-refresh',      severity: 'secondary' },
  failed:     { label: 'Fallido',     icon: 'pi pi-exclamation-triangle', severity: 'danger' },
  // Products
  active:     { label: 'Activo',      icon: 'pi pi-check-circle', severity: 'success' },
  inactive:   { label: 'Inactivo',    icon: 'pi pi-ban',          severity: 'secondary' },
  draft:      { label: 'Borrador',    icon: 'pi pi-pencil',       severity: 'warn' },
  // Users
  verified:   { label: 'Verificado',  icon: 'pi pi-verified',     severity: 'success' },
  banned:     { label: 'Baneado',     icon: 'pi pi-ban',          severity: 'danger' },
  // Subscriptions
  approved:   { label: 'Aprobado',    icon: 'pi pi-check',        severity: 'success' },
  rejected:   { label: 'Rechazado',   icon: 'pi pi-times',        severity: 'danger' },
  expired:    { label: 'Expirado',    icon: 'pi pi-calendar-times', severity: 'secondary' },
};

const severity = statusMap[props.status]?.severity || 'secondary';
</script>
