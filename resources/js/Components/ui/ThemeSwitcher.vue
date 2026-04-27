<template>
  <!-- Theme Switcher: 4 theme dots + dark/light toggle -->
  <div class="theme-switcher">

    <!-- Theme dots -->
    <div class="theme-dots">
      <button
        v-for="t in THEMES"
        :key="t.id"
        v-tooltip.bottom="t.label"
        :class="['theme-dot', t.dot, { active: theme === t.id }]"
        :aria-label="t.label"
        @click="setTheme(t.id)"
      />
    </div>

    <!-- Vertical separator -->
    <span class="dot-sep"></span>

    <!-- Dark/Light toggle -->
    <ToggleButton
      v-model="isDark"
      onIcon="pi pi-moon"
      offIcon="pi pi-sun"
      onLabel=""
      offLabel=""
      :pt="{
        root: { style: 'width:32px;height:28px;padding:0;border:none;background:transparent;border-radius:7px;' }
      }"
      @change="handleModeToggle"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useTheme, THEMES } from '@/composables/useTheme'

const { theme, mode, setTheme, toggleMode } = useTheme()

const isDark = computed({
  get: () => mode.value === 'dark',
  set: () => {},
})

function handleModeToggle() {
  toggleMode()
}
</script>

<style scoped>
.theme-switcher {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.55rem;
  border-radius: 10px;
  background: var(--nx-surface-2, rgba(255,255,255,0.05));
  border: 1px solid var(--nx-border, rgba(255,255,255,0.08));
}

.theme-dots {
  display: flex;
  gap: 0.3rem;
  align-items: center;
}

.theme-dot {
  width: 13px;
  height: 13px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  transition: transform 0.15s ease, border-color 0.15s ease;
  padding: 0;
  outline: none;
  flex-shrink: 0;
}

.theme-dot:hover { transform: scale(1.25); }

.theme-dot.active {
  border-color: var(--nx-text, #f4f4f5);
  transform: scale(1.15);
}

.theme-dot-nexo     { background: linear-gradient(135deg, #6366f1, #818cf8); }
.theme-dot-midnight { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
.theme-dot-ocean    { background: linear-gradient(135deg, #0891b2, #22d3ee); }
.theme-dot-ember    { background: linear-gradient(135deg, #ea580c, #fbbf24); }

.dot-sep {
  width: 1px;
  height: 18px;
  background: var(--nx-border, rgba(255,255,255,0.1));
  flex-shrink: 0;
}
</style>
