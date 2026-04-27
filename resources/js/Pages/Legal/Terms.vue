<template>
  <AppLayout>
    <Head :title="t('legal.terms.title') + ' — Nexo Digital Store'" />
    <div class="legal-page py-12">
      <div class="max-w-3xl mx-auto px-4">
        <h1 class="text-4xl font-black text-white font-display mb-2" v-motion-slide-visible-top>{{ t('legal.terms.title') }}</h1>
        <p class="text-surface-500 text-sm mb-12">{{ t('legal.terms.lastUpdated') }}</p>

        <div class="legal-content glass p-8 rounded-3xl border border-surface-800 leading-relaxed text-surface-300 space-y-8" v-motion-fade-visible>
          
          <section>
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-brand-500/10 flex items-center justify-center border border-brand-500/20 text-brand-400">1</div>
              Aceptación de términos
            </h2>
            <p>Al acceder y utilizar este sitio web, usted acepta cumplir con estos términos y condiciones de uso...</p>
          </section>

          <section class="bg-amber-500/5 p-6 rounded-2xl border border-amber-500/20">
            <h2 class="text-xl font-bold text-amber-500 mb-4 flex items-center gap-2">
              <i class="pi pi-exclamation-triangle" />
              {{ t('legal.terms.refundPolicy') }}
            </h2>
            <p class="font-semibold text-white">
              {{ t('legal.terms.refundText') }}
            </p>
          </section>

          <section>
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-brand-500/10 flex items-center justify-center border border-brand-500/20 text-brand-400">2</div>
              Uso de licencias
            </h2>
            <p>Las licencias digitales adquiridas en Nexo son para uso personal y no pueden ser revendidas sin autorización previa...</p>
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
.legal-page { background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.05), transparent 600px); }
</style>
