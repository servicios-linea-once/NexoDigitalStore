<template>
  <AuthLayout title="Verifica tu correo" subtitle="Revisa tu bandeja de entrada">
    <Head title="Verificar email — Nexo Digital Store" />

    <div class="verify-wrap">
      <!-- Icon -->
      <div class="verify-icon">
        <i class="pi pi-envelope" />
      </div>

      <!-- Success status -->
      <Message
        v-if="status === 'verification-link-sent'"
        severity="success"
        :closable="false"
        class="verify-msg"
      >
        ¡Nuevo enlace enviado! Revisa tu bandeja de entrada.
      </Message>

      <p class="verify-text">
        Gracias por registrarte. Antes de continuar, necesitas verificar tu dirección de correo.
        <br />
        Hemos enviado un enlace de verificación a tu email.
        Si no lo recibiste, podemos enviarte otro.
      </p>

      <!-- Resend -->
      <Button
        label="Reenviar correo de verificación"
        icon="pi pi-refresh"
        :loading="form.processing"
        :disabled="form.processing"
        fluid
        @click="resend"
      />

      <div class="verify-logout">
        <Button
          label="Cerrar sesión"
          severity="secondary"
          text
          size="small"
          @click="logout"
        />
      </div>
    </div>
  </AuthLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

defineProps({ status: String });

const form = useForm({});
function resend() { form.post(route('verification.send')); }
function logout()  { router.post(route('logout')); }
</script>

<style scoped>
.verify-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.25rem;
  text-align: center;
}

.verify-icon {
  width: 64px;
  height: 64px;
  background: var(--p-primary-100, rgba(99,102,241,0.12));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--p-primary-200, rgba(99,102,241,0.2));
}
.verify-icon .pi {
  font-size: 1.75rem;
  color: var(--p-primary-color);
}

.verify-msg  { width: 100%; }

.verify-text {
  font-size: 0.875rem;
  line-height: 1.7;
  color: var(--p-text-muted-color);
  margin: 0;
}

.verify-logout {
  margin-top: 0.25rem;
}
</style>
