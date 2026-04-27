<template>
  <div class="lang-switcher">
    <SelectButton
      v-model="currentLocale"
      :options="langs"
      optionLabel="label"
      optionValue="value"
      
      @change="handleChange"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SelectButton from 'primevue/selectbutton'
import { useI18n } from 'vue-i18n'
import { useTheme } from '@/composables/useTheme'

const { locale } = useI18n()
const { setLocale } = useTheme()

const langs = [
  { label: 'ES', value: 'es' },
  { label: 'EN', value: 'en' },
]

const currentLocale = computed({
  get: () => locale.value,
  set: (v) => { locale.value = v },
})

function handleChange(e) {
  locale.value = e.value
  document.documentElement.setAttribute('lang', e.value)
  localStorage.setItem('nexo-locale', e.value)
  setLocale(e.value) // persist to DB if authenticated
}
</script>

<style scoped>
:deep(.lang-root) {
  gap: 2px !important;
  background: var(--nx-surface-2) !important;
  border: 1px solid var(--nx-border) !important;
  border-radius: 8px !important;
  padding: 2px !important;
}

:deep(.lang-btn) {
  font-size: 0.7rem !important;
  font-weight: 700 !important;
  padding: 0.25rem 0.5rem !important;
  border-radius: 6px !important;
  border: none !important;
  color: var(--nx-text-muted) !important;
  background: transparent !important;
  transition: all 0.15s !important;
}

:deep(.lang-btn.p-highlight) {
  background: var(--nx-primary) !important;
  color: white !important;
}

:deep(.lang-btn:not(.p-highlight):hover) {
  background: var(--nx-primary-muted) !important;
  color: var(--nx-primary) !important;
}
</style>
