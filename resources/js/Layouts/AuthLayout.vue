<template>
  <div class="auth-root">
    <Head :title="title + ' — Nexo Digital Store'" />

    <!-- Background -->
    <div class="auth-bg">
      <div class="auth-orb auth-orb-1" />
      <div class="auth-orb auth-orb-2" />
      <div class="auth-grid" />
    </div>

    <!-- Logo top -->
    <div class="auth-top">
      <Link :href="route('home')" class="auth-logo">
        <i class="pi pi-shield" />
        <span class="font-display">NexoDS</span>
      </Link>
    </div>

    <!-- Card -->
    <div class="auth-wrapper">
      <div class="auth-card">
        <!-- Header -->
        <div class="auth-header">
          <h1 class="auth-title font-display">{{ title }}</h1>
          <p class="auth-subtitle">{{ subtitle }}</p>
        </div>

        <!-- Slot content -->
        <slot />
      </div>

      <!-- Trust badges -->
      <div class="auth-trust">
        <div class="trust-item">
          <i class="pi pi-lock" />
          <span>SSL seguro</span>
        </div>
        <div class="trust-divider" />
        <div class="trust-item">
          <i class="pi pi-shield" />
          <span>2FA disponible</span>
        </div>
        <div class="trust-divider" />
        <div class="trust-item">
          <i class="pi pi-eye-slash" />
          <span>Privacidad protegida</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
  title:    { type: String, required: true },
  subtitle: { type: String, default: '' },
});
</script>

<style scoped>
/* ── Root ─────────────────────────────────────────────────────────────────── */
.auth-root {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1.5rem 1rem;
  position: relative;
  background: var(--p-surface-950);
  color: var(--p-text-color);
}

/* ── Background decoration ────────────────────────────────────────────────── */
.auth-bg   { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
.auth-orb  { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.15; }
.auth-orb-1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, var(--p-primary-color), transparent);
  top: -150px; right: -100px;
}
.auth-orb-2 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, var(--p-primary-400, var(--p-primary-color)), transparent);
  bottom: -100px; left: -100px;
}
.auth-grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(color-mix(in srgb, var(--p-primary-color) 5%, transparent) 1px, transparent 1px),
    linear-gradient(90deg, color-mix(in srgb, var(--p-primary-color) 5%, transparent) 1px, transparent 1px);
  background-size: 40px 40px;
}

/* ── Logo ─────────────────────────────────────────────────────────────────── */
.auth-top  { position: relative; z-index: 1; margin-bottom: 1.5rem; }
.auth-logo {
  display: flex; align-items: center; gap: 0.5rem;
  text-decoration: none;
  color: var(--p-text-color);
  font-size: 1.25rem; font-weight: 700;
}
.auth-logo .pi { color: var(--p-primary-color); font-size: 1.4rem; }

/* ── Wrapper + Card ───────────────────────────────────────────────────────── */
.auth-wrapper { position: relative; z-index: 1; width: 100%; max-width: 420px; }

.auth-card {
  border-radius: var(--p-border-radius-xl, 20px);
  padding: 2rem;
  background: color-mix(in srgb, var(--p-surface-800) 70%, transparent);
  border: 1px solid var(--p-surface-700);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

/* ── Header ───────────────────────────────────────────────────────────────── */
.auth-header  { text-align: center; margin-bottom: 1.75rem; }
.auth-title   { font-size: 1.625rem; font-weight: 800; color: var(--p-text-color); margin: 0 0 0.375rem; }
.auth-subtitle { font-size: 0.875rem; color: var(--p-text-muted-color); }

/* ── Trust strip ──────────────────────────────────────────────────────────── */
.auth-trust {
  display: flex; align-items: center; justify-content: center;
  gap: 0.75rem; margin-top: 1.25rem;
}
.trust-item {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.72rem; color: var(--p-text-muted-color); opacity: 0.75;
}
.trust-item .pi { font-size: 0.72rem; }
.trust-divider  { width: 1px; height: 12px; background: var(--p-surface-600); }
</style>
