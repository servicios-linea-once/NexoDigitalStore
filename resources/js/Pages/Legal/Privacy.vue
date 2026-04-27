<template>
  <AppLayout>
    <Head :title="t('legal.privacy.title') + ' — Nexo Digital Store'" />
    <div class="legal-page py-12">
      <div class="max-w-3xl mx-auto px-4">
        <h1 class="text-4xl font-black text-white font-display mb-2" v-motion-slide-visible-top>{{ t('legal.privacy.title') }}</h1>
        <p class="text-surface-500 text-sm mb-12">{{ t('legal.privacy.lastUpdated') }}</p>

        <div class="legal-content glass p-8 rounded-3xl border border-surface-800 leading-relaxed text-surface-300 space-y-8" v-motion-fade-visible>
          <p>{{ t('legal.privacy.text') }}</p>
          <section>
            <h2 class="text-xl font-bold text-white mb-4">Recolección de datos</h2>
            <p>Recopilamos información necesaria para procesar sus pedidos y mejorar su experiencia...</p>
          </section>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();
</script>

<style scoped>
.legal-page { background: radial-gradient(circle at top left, rgba(6, 182, 212, 0.05), transparent 600px); }
</style>
