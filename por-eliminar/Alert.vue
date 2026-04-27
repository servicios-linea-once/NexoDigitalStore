<template>
  <div
    class="alert"
    :class="`alert-${type}`"
    role="alert"
  >
    <i class="pi alert-icon" :class="iconClass" />
    <span>{{ message }}</span>
    <button v-if="dismissible" class="alert-close" @click="$emit('dismiss')">
      <i class="pi pi-times" />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type:       { type: String, default: 'info' }, // success | error | warning | info
  message:    { type: String, required: true },
  dismissible: { type: Boolean, default: false },
});

defineEmits(['dismiss']);

const iconClass = computed(() => ({
  success: 'pi-check-circle',
  error:   'pi-times-circle',
  warning: 'pi-exclamation-triangle',
  info:    'pi-info-circle',
}[props.type] ?? 'pi-info-circle'));
</script>

<style scoped>
.alert {
  display: flex; align-items: flex-start; gap: 0.625rem;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.875rem;
  border: 1px solid;
}
.alert-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }

.alert-success {
  background: rgba(34,197,94,0.08);
  border-color: rgba(34,197,94,0.25);
  color: #86efac;
}
.alert-success .alert-icon { color: #22c55e; }

.alert-error {
  background: rgba(239,68,68,0.08);
  border-color: rgba(239,68,68,0.25);
  color: #fca5a5;
}
.alert-error .alert-icon { color: #ef4444; }

.alert-warning {
  background: rgba(245,158,11,0.08);
  border-color: rgba(245,158,11,0.25);
  color: #fcd34d;
}
.alert-warning .alert-icon { color: #f59e0b; }

.alert-info {
  background: rgba(99,102,241,0.08);
  border-color: rgba(99,102,241,0.25);
  color: #a5b4fc;
}
.alert-info .alert-icon { color: #6366f1; }

.alert-close {
  margin-left: auto;
  background: none; border: none; cursor: pointer;
  color: inherit; opacity: 0.6;
  padding: 0 0.25rem;
  transition: opacity 0.2s;
}
.alert-close:hover { opacity: 1; }
</style>
