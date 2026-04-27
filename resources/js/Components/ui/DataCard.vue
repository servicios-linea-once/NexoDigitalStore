<!-- Components/ui/DataCard.vue — Card contenedora con header, acciones y slot body -->
<template>
  <div class="data-card" v-motion-fade-visible-once>
    <div v-if="title || $slots.header" class="data-card-header">
      <slot name="header">
        <div class="data-card-title-row">
          <div class="data-card-title-wrap">
            <div v-if="icon" class="data-card-icon">
              <i :class="['pi', icon]" />
            </div>
            <div>
              <h3 class="data-card-title">{{ title }}</h3>
              <p v-if="subtitle" class="data-card-subtitle">{{ subtitle }}</p>
            </div>
          </div>
          <div v-if="$slots.actions" class="data-card-actions">
            <slot name="actions" />
          </div>
        </div>
      </slot>
    </div>
    <div class="data-card-body" :class="{ 'no-padding': noPadding }">
      <slot />
    </div>
    <div v-if="$slots.footer" class="data-card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title:     { type: String, default: '' },
  subtitle:  { type: String, default: '' },
  icon:      { type: String, default: '' },
  noPadding: { type: Boolean, default: false },
});
</script>

<style scoped>
.data-card {
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: 16px;
  overflow: hidden;
}
.data-card-header {
  padding: 1.125rem 1.25rem;
  border-bottom: 1px solid var(--c-border);
}
.data-card-title-row {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
}
.data-card-title-wrap { display: flex; align-items: center; gap: 0.75rem; }
.data-card-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: var(--c-primary-muted); color: var(--c-primary);
  font-size: 1rem; flex-shrink: 0;
}
.data-card-title    { font-size: 0.95rem; font-weight: 700; color: var(--c-text); margin: 0; }
.data-card-subtitle { font-size: 0.75rem; color: var(--c-text-muted); margin: 0.1rem 0 0; }
.data-card-actions  { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }

.data-card-body { padding: 1.25rem; }
.data-card-body.no-padding { padding: 0; }

.data-card-footer {
  padding: 0.875rem 1.25rem;
  border-top: 1px solid var(--c-border);
  background: var(--c-card);
}
</style>
