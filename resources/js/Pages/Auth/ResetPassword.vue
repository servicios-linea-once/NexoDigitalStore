<template>
  <AuthLayout title="Nueva contraseña" subtitle="Elige una contraseña segura para tu cuenta">
    <Head title="Restablecer contraseña — Nexo Digital Store" />

    <form @submit.prevent="submit" class="auth-form">

      <!-- Email read-only -->
      <div class="p-field">
        <IftaLabel>
          <IconField>
            <InputIcon class="pi pi-envelope" />
            <InputText
              id="reset-email"
              v-model="form.email"
              type="email"
              readonly
              fluid
            />
          </IconField>
          <label for="reset-email">Correo electrónico</label>
        </IftaLabel>
        <Message v-if="form.errors.email" severity="error" size="small" variant="simple">
          {{ form.errors.email }}
        </Message>
      </div>

      <!-- New Password -->
      <div class="p-field">
        <IftaLabel>
          <Password
            id="reset-password"
            v-model="form.password"
            :invalid="!!form.errors.password"
            autocomplete="new-password"
            toggleMask
            fluid
            promptLabel="Escribe tu nueva contraseña"
            weakLabel="Débil"
            mediumLabel="Regular"
            strongLabel="Fuerte"
          />
          <label for="reset-password">Nueva contraseña</label>
        </IftaLabel>
        <Message v-if="form.errors.password" severity="error" size="small" variant="simple">
          {{ form.errors.password }}
        </Message>
      </div>

      <!-- Confirm Password -->
      <div class="p-field">
        <IftaLabel>
          <Password
            id="reset-password-confirm"
            v-model="form.password_confirmation"
            :feedback="false"
            :invalid="!!form.errors.password_confirmation"
            autocomplete="new-password"
            toggleMask
            fluid
          />
          <label for="reset-password-confirm">Confirmar contraseña</label>
        </IftaLabel>
        <!-- Match indicator -->
        <div v-if="form.password_confirmation" class="match-row">
          <i :class="passwordsMatch ? 'pi pi-check-circle' : 'pi pi-times-circle'"
             :style="{ color: passwordsMatch ? 'var(--p-green-500, #10b981)' : 'var(--p-red-500, #ef4444)' }" />
          <span :style="{ color: passwordsMatch ? 'var(--p-green-500, #10b981)' : 'var(--p-red-500, #ef4444)', fontSize: '0.78rem' }">
            {{ passwordsMatch ? 'Contraseñas coinciden' : 'No coinciden' }}
          </span>
        </div>
        <Message v-if="form.errors.password_confirmation" severity="error" size="small" variant="simple">
          {{ form.errors.password_confirmation }}
        </Message>
      </div>

      <!-- Submit -->
      <Button
        type="submit"
        label="Restablecer contraseña"
        icon="pi pi-shield"
        :loading="form.processing"
        :disabled="form.processing"
        fluid
      />
    </form>
  </AuthLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const props = defineProps({ token: String, email: String });

const form = useForm({
  token:                 props.token,
  email:                 props.email,
  password:              '',
  password_confirmation: '',
});

function submit() {
  form.post(route('password.update'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
}

const passwordsMatch = computed(() =>
  form.password && form.password_confirmation && form.password === form.password_confirmation
);
</script>

<style scoped>
.auth-form { display: flex; flex-direction: column; gap: 1.125rem; }
.p-field   { display: flex; flex-direction: column; gap: 0.375rem; }

.match-row {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  margin-top: 0.25rem;
}
</style>
