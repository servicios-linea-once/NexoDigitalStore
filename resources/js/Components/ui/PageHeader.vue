<!-- Components/ui/PageHeader.vue — Encabezado de página reutilizable con breadcrumb y acciones -->
<template>
  <div class="page-header-wrap" v-motion-fade-visible-once>
    <Breadcrumb v-if="breadcrumb.length" :model="breadcrumb" class="nx-breadcrumb" />

    <div class="page-header-row">
      <div class="page-header-text">
        <div v-if="icon" class="page-header-icon">
          <i :class="['pi', icon]" />
        </div>
        <div>
          <h1 class="page-header-title font-display">{{ title }}</h1>
          <p v-if="subtitle" class="page-header-sub">{{ subtitle }}</p>
        </div>
      </div>

      <div v-if="$slots.actions" class="page-header-actions">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title:      { type: String, required: true },
  subtitle:   { type: String, default: '' },
  icon:       { type: String, default: '' },
  breadcrumb: { type: Array,  default: () => [] },
});
</script>

<style scoped>
.page-header-wrap {
  margin-bottom: 1.75rem;
}
.nx-breadcrumb {
  background: transparent !important;
  padding: 0 0 0.625rem !important;
  border: none !important;
  font-size: 0.78rem;
}
.page-header-row {
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; flex-wrap: wrap;
}
.page-header-text {
  display: flex; align-items: center; gap: 0.875rem;
}
.page-header-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  background: var(--c-primary-muted);
  color: var(--c-primary); font-size: 1.15rem; flex-shrink: 0;
}
.page-header-title {
  font-size: 1.5rem; font-weight: 800;
  color: var(--c-text); line-height: 1.2; margin: 0;
}
.page-header-sub { font-size: 0.85rem; color: var(--c-text-muted); margin: 0.1rem 0 0; }
.page-header-actions { display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap; }
</style>
