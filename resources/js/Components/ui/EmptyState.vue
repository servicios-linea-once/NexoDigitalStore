<!-- Components/ui/EmptyState.vue — Estado vacío reutilizable -->
<template>
  <div class="empty-state" v-motion-fade-visible-once>
    <div class="empty-icon" :class="`empty-icon--${color}`">
      <i :class="['pi', icon]" />
    </div>
    <h3 class="empty-title font-display">{{ title }}</h3>
    <p class="empty-desc">{{ description }}</p>
    <div v-if="$slots.actions || actionLabel" class="empty-actions">
      <slot name="actions">
        <Button v-if="actionLabel" :label="actionLabel" :icon="actionIcon" @click="$emit('action')" />
      </slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon:        { type: String, default: 'pi-inbox' },
  title:       { type: String, default: 'Sin resultados' },
  description: { type: String, default: 'No hay elementos que mostrar.' },
  actionLabel: { type: String, default: '' },
  actionIcon:  { type: String, default: 'pi-plus' },
  color:       { type: String, default: 'primary' },
});
defineEmits(['action']);
</script>

<style scoped>
.empty-state {
  display: flex; flex-direction: column; align-items: center;
  padding: 3rem 2rem; text-align: center;
}
.empty-icon {
  width: 80px; height: 80px; border-radius: 22px;
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; margin-bottom: 1.25rem;
}
.empty-icon--primary { background: var(--c-primary-muted); color: var(--c-primary); }
.empty-icon--success { background: rgba(16,185,129,0.1); color: #10b981; }
.empty-icon--warning { background: rgba(245,158,11,0.1);  color: #f59e0b; }

.empty-title { font-size: 1.1rem; font-weight: 700; color: var(--c-text); margin: 0 0 0.5rem; }
.empty-desc  { font-size: 0.875rem; color: var(--c-text-muted); margin: 0 0 1.5rem; max-width: 340px; line-height: 1.6; }
.empty-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; justify-content: center; }
</style>
