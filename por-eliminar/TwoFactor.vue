<template>
  <AppLayout>
    <Head title="Configurar 2FA — Nexo Digital Store" />

    <div class="twofa-wrap">
      <div class="twofa-card glass">
        <!-- Header -->
        <div class="twofa-header">
          <div class="twofa-icon">
            <i class="pi pi-shield" />
          </div>
          <h1 class="twofa-title font-display">Autenticación de Dos Factores</h1>
          <p class="twofa-sub">Agrega una capa extra de seguridad a tu cuenta usando una app autenticadora.</p>
        </div>

        <!-- Enabled state -->
        <div v-if="twoFactorEnabled" class="state-block state-enabled glass">
          <i class="pi pi-check-circle state-icon" style="color:#34d399" />
          <div>
            <p class="state-title">2FA está activado</p>
            <p class="state-desc">Tu cuenta está protegida con autenticación de dos factores.</p>
          </div>
          <button class="btn btn-danger" @click="showDisable = true">Desactivar</button>
        </div>

        <!-- Disabled state — setup flow -->
        <div v-else>
          <!-- Step 1: Install app -->
          <div class="step-block" :class="{ 'step-done': currentStep > 1 }">
            <div class="step-num">1</div>
            <div class="step-content">
              <p class="step-title">Instala una app autenticadora</p>
              <p class="step-desc">Recomendamos Google Authenticator, Authy o Microsoft Authenticator.</p>
              <div class="app-badges">
                <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" class="app-badge">
                  <i class="pi pi-android" /> Google Authenticator
                </a>
                <a href="https://authy.com/download/" target="_blank" class="app-badge">
                  <i class="pi pi-mobile" /> Authy
                </a>
              </div>
            </div>
          </div>

          <!-- Step 2: Scan QR -->
          <div class="step-block" :class="{ 'step-done': currentStep > 2 }">
            <div class="step-num">2</div>
            <div class="step-content">
              <p class="step-title">Escanea el código QR</p>
              <p class="step-desc">Abre tu app autenticadora y escanea este código.</p>

              <div v-if="!qrCodeSvg" class="qr-placeholder glass">
                <button class="btn btn-primary" :disabled="loading" @click="generateQr">
                  <i class="pi pi-qrcode" />
                  {{ loading ? 'Generando...' : 'Generar código QR' }}
                </button>
              </div>

              <div v-else class="qr-block">
                <div class="qr-img" v-html="qrCodeSvg" />
                <p class="qr-manual-label">¿No puedes escanear? Introduce este código manualmente:</p>
                <div class="secret-box">
                  <code class="secret-code">{{ secretKey }}</code>
                  <button class="copy-btn" title="Copiar" @click="copySecret">
                    <i class="pi pi-copy" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Verify code -->
          <div v-if="qrCodeSvg" class="step-block">
            <div class="step-num">3</div>
            <div class="step-content">
              <p class="step-title">Verifica el código</p>
              <p class="step-desc">Ingresa el código de 6 dígitos que muestra tu app autenticadora.</p>

              <form class="verify-form" @submit.prevent="enableTwoFactor">
                <div class="otp-wrap">
                  <input
                    v-model="otpCode"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="000000"
                    class="otp-input"
                    autocomplete="one-time-code"
                  />
                </div>
                <p v-if="error" class="form-error">{{ error }}</p>
                <button type="submit" class="btn btn-primary" :disabled="otpCode.length !== 6 || loading">
                  <i class="pi pi-lock" />
                  {{ loading ? 'Verificando...' : 'Activar 2FA' }}
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Recovery codes (shown after enabling) -->
        <div v-if="recoveryCodes.length" class="recovery-block glass">
          <div class="recovery-header">
            <i class="pi pi-exclamation-triangle" style="color:#fbbf24" />
            <p class="recovery-title">Códigos de recuperación</p>
          </div>
          <p class="recovery-desc">
            Guarda estos códigos en un lugar seguro. Cada uno puede usarse una sola vez si pierdes acceso a tu app autenticadora.
          </p>
          <div class="codes-grid">
            <code v-for="code in recoveryCodes" :key="code" class="recovery-code">{{ code }}</code>
          </div>
          <button class="btn btn-ghost mt-3" @click="downloadCodes">
            <i class="pi pi-download" /> Descargar códigos
          </button>
        </div>
      </div>

      <!-- Disable confirm modal -->
      <div v-if="showDisable" class="modal-overlay" @click.self="showDisable = false">
        <div class="modal-box glass">
          <h3 class="modal-title">¿Desactivar 2FA?</h3>
          <p class="modal-text">Tu cuenta quedará menos protegida. Necesitarás ingresar tu contraseña para confirmar.</p>
          <input v-model="disablePassword" type="password" class="field-input" placeholder="Contraseña actual" />
          <p v-if="error" class="form-error">{{ error }}</p>
          <div class="modal-actions">
            <button class="btn btn-ghost" @click="showDisable = false">Cancelar</button>
            <button class="btn btn-danger" :disabled="loading" @click="disableTwoFactor">
              {{ loading ? 'Desactivando...' : 'Desactivar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
  twoFactorEnabled: { type: Boolean, default: false },
});

const currentStep  = ref(1);
const qrCodeSvg    = ref('');
const secretKey    = ref('');
const otpCode      = ref('');
const recoveryCodes = ref([]);
const loading      = ref(false);
const error        = ref('');
const showDisable  = ref(false);
const disablePassword = ref('');

async function generateQr() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await axios.post('/2fa/setup');
    qrCodeSvg.value = data.qr_svg;
    secretKey.value = data.secret;
    currentStep.value = 2;
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Error generando QR. Intenta de nuevo.';
  } finally {
    loading.value = false;
  }
}

async function enableTwoFactor() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await axios.post('/2fa/confirm', { code: otpCode.value });
    recoveryCodes.value = data.recovery_codes ?? [];
    router.reload({ only: [] }); // refresh twoFactorEnabled prop
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Código inválido. Intenta de nuevo.';
  } finally {
    loading.value = false;
    otpCode.value = '';
  }
}

async function disableTwoFactor() {
  loading.value = true;
  error.value = '';
  try {
    await axios.delete('/2fa/disable', { data: { password: disablePassword.value } });
    showDisable.value = false;
    router.reload();
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Contraseña incorrecta.';
  } finally {
    loading.value = false;
  }
}

function copySecret() {
  navigator.clipboard?.writeText(secretKey.value);
}

function downloadCodes() {
  const text = recoveryCodes.value.join('\n');
  const blob = new Blob([text], { type: 'text/plain' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'nexo-recovery-codes.txt';
  a.click();
}
</script>

<style scoped>
.twofa-wrap  { max-width: 680px; margin: 2rem auto; padding: 0 1rem; }
.twofa-card  { border-radius: 20px; padding: 2rem; }
.twofa-header { text-align: center; margin-bottom: 2rem; }
.twofa-icon  { width: 56px; height: 56px; background: rgba(99,102,241,0.15); border-radius: 16px;
               display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
.twofa-icon .pi { font-size: 1.6rem; color: var(--color-brand-400); }
.twofa-title { font-size: 1.4rem; font-weight: 800; color: white; margin: 0 0 0.5rem; }
.twofa-sub   { font-size: 0.85rem; color: var(--color-surface-400); margin: 0; }

/* State blocks */
.state-block { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; }
.state-enabled { border: 1px solid rgba(52,211,153,0.2); }
.state-icon  { font-size: 1.8rem; flex-shrink: 0; }
.state-title { font-size: 0.9rem; font-weight: 700; color: white; margin: 0 0 0.2rem; }
.state-desc  { font-size: 0.78rem; color: var(--color-surface-400); margin: 0; }

/* Step blocks */
.step-block { display: flex; gap: 1rem; margin-bottom: 1.75rem; padding-bottom: 1.75rem;
              border-bottom: 1px solid var(--color-surface-800); }
.step-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.step-num   { width: 32px; height: 32px; border-radius: 50%; background: var(--color-brand-600);
              color: white; font-weight: 800; font-size: 0.85rem;
              display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.step-done .step-num { background: rgba(52,211,153,0.2); color: #34d399; }
.step-title { font-size: 0.9rem; font-weight: 700; color: white; margin: 0 0 0.35rem; }
.step-desc  { font-size: 0.8rem; color: var(--color-surface-400); margin: 0 0 1rem; }
.step-content { flex: 1; }

/* App badges */
.app-badges { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.app-badge  { display: flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.85rem;
              background: var(--color-surface-800); border: 1px solid var(--color-surface-700);
              border-radius: 8px; color: var(--color-surface-300); font-size: 0.78rem;
              text-decoration: none; transition: all 0.2s; }
.app-badge:hover { border-color: var(--color-brand-500); color: var(--color-brand-400); }

/* QR */
.qr-placeholder { padding: 2rem; border-radius: 12px; text-align: center; }
.qr-block  { display: flex; flex-direction: column; align-items: flex-start; gap: 0.75rem; }
.qr-img    { background: white; padding: 12px; border-radius: 12px; display: inline-block; }
.qr-img svg, .qr-img img { width: 180px; height: 180px; display: block; }
.qr-manual-label { font-size: 0.78rem; color: var(--color-surface-400); margin: 0; }
.secret-box { display: flex; align-items: center; gap: 0.5rem; background: var(--color-surface-900);
              border: 1px solid var(--color-surface-700); border-radius: 8px; padding: 0.5rem 0.75rem; }
.secret-code { font-size: 0.8rem; color: var(--color-brand-300); font-family: monospace; letter-spacing: 0.05em; }
.copy-btn   { background: none; border: none; color: var(--color-surface-400); cursor: pointer; padding: 0.2rem; }
.copy-btn:hover { color: var(--color-brand-400); }

/* OTP */
.verify-form { display: flex; flex-direction: column; gap: 1rem; max-width: 280px; }
.otp-wrap { position: relative; }
.otp-input { width: 100%; background: var(--color-surface-900); border: 1px solid var(--color-surface-700);
             border-radius: 10px; padding: 0.75rem 1rem; color: white; font-size: 1.5rem; font-weight: 700;
             text-align: center; letter-spacing: 0.4em; outline: none; font-family: monospace; }
.otp-input:focus { border-color: var(--color-brand-500); }

/* Recovery */
.recovery-block { border-radius: 14px; padding: 1.5rem; margin-top: 1.5rem; border: 1px solid rgba(251,191,36,0.2); }
.recovery-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.recovery-title  { font-size: 0.9rem; font-weight: 700; color: #fbbf24; margin: 0; }
.recovery-desc   { font-size: 0.78rem; color: var(--color-surface-400); margin-bottom: 1rem; }
.codes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.5rem; }
.recovery-code { background: var(--color-surface-900); border: 1px solid var(--color-surface-700);
                 border-radius: 6px; padding: 0.4rem 0.75rem; font-size: 0.82rem; color: white;
                 font-family: monospace; text-align: center; }
.mt-3 { margin-top: 0.75rem; }

/* Form */
.field-input { width: 100%; background: var(--color-surface-900); border: 1px solid var(--color-surface-700);
               border-radius: 10px; padding: 0.65rem 1rem; color: white; font-size: 0.9rem; outline: none; }
.field-input:focus { border-color: var(--color-brand-500); }
.form-error  { font-size: 0.78rem; color: #f87171; margin: 0; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 50;
                 display: flex; align-items: center; justify-content: center; }
.modal-box   { border-radius: 16px; padding: 2rem; max-width: 420px; width: 90%; display: flex; flex-direction: column; gap: 1rem; }
.modal-title { font-size: 1.1rem; font-weight: 800; color: white; margin: 0; }
.modal-text  { font-size: 0.875rem; color: var(--color-surface-400); margin: 0; line-height: 1.5; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; }

.btn-danger { background: #ef4444; color: white; border: none; }
.btn-danger:hover { background: #dc2626; }
</style>
