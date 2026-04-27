<!-- Components/ui/StatsCard.vue — Tarjeta de métrica reutilizable -->
<template>
  <div
    class="stats-card"
    :class="[`stats-card--${color}`]"
    v-motion
    :initial="{ opacity: 0, y: 20 }"
    :visibleOnce="{ opacity: 1, y: 0, transition: { duration: 400, delay } }"
  >
    <div class="stats-card-body">
      <div class="stats-icon">
        <i :class="['pi', icon]" />
      </div>
      <div class="stats-content">
        <p class="stats-label">{{ label }}</p>
        <p class="stats-value">{{ value }}</p>
        <div v-if="trend !== null" class="stats-trend" :class="trend >= 0 ? 'trend-up' : 'trend-down'">
          <i :class="['pi', trend >= 0 ? 'pi-arrow-up-right' : 'pi-arrow-down-right']" />
          <span>{{ Math.abs(trend) }}% este mes</span>
        </div>
      </div>
    </div>
    <div v-if="footer" class="stats-footer">{{ footer }}</div>
  </div>
</template>

<script setup>
defineProps({
  label:  { type: String,  required: true },
  value:  { type: [String, Number], required: true },
  icon:   { type: String,  default: 'pi-chart-bar' },
  color:  { type: String,  default: 'primary' }, // primary | success | warning | danger | info
  trend:  { type: Number,  default: null },
  footer: { type: String,  default: '' },
  delay:  { type: Number,  default: 0 },
});
</script>

<style scoped>
.stats-card {
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: 16px;
  padding: 1.25rem;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  cursor: default;
}
.stats-card:hover { transform: translateY(-2px); box-shadow: 0 8px 32px var(--c-shadow); }

.stats-card-body { display: flex; align-items: flex-start; gap: 1rem; }
.stats-icon {
  width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
}
.stats-content { flex: 1; min-width: 0; }
.stats-label  { font-size: 0.78rem; font-weight: 600; color: var(--c-text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 0.25rem; }
.stats-value  { font-size: 1.75rem; font-weight: 800; color: var(--c-text); margin: 0; line-height: 1.1; }
.stats-trend  { display: flex; align-items: center; gap: 0.25rem; font-size: 0.72rem; font-weight: 600; margin-top: 0.375rem; }
.trend-up     { color: #10b981; }
.trend-down   { color: #ef4444; }

.stats-footer { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--c-border); font-size: 0.75rem; color: var(--c-text-muted); }

/* Color variants for icon bg */
.stats-card--primary   .stats-icon { background: var(--c-primary-muted); color: var(--c-primary); }
.stats-card--success   .stats-icon { background: rgba(16,185,129,0.12); color: #10b981; }
.stats-card--warning   .stats-icon { background: rgba(245,158,11,0.12); color: #f59e0b; }
.stats-card--danger    .stats-icon { background: rgba(239,68,68,0.12);  color: #ef4444; }
.stats-card--info      .stats-icon { background: rgba(6,182,212,0.12);  color: #06b6d4; }
</style>
