<template>
    <AuthLayout
        subtitle="Te enviaremos un enlace para restablecer tu contraseña"
        title="Recuperar contraseña"
    >
        <form class="auth-form" @submit.prevent="submit">

            <Message v-if="status" :closable="false" severity="success">{{ status }}</Message>

            <!-- Email via FloatLabel (más compatible que IftaLabel + IconField nested) -->
            <div class="flex flex-col gap-1.5">
                <IftaLabel>
                    <IconField>
                        <InputIcon class="pi pi-envelope"/>
                        <InputText
                            id="reg-email"
                            v-model="form.email"
                            :disabled="form.processing"
                            :invalid="!!form.errors.email"
                            autocomplete="email"
                            fluid
                            type="email"
                        />
                    </IconField>
                    <label for="reg-email">Correo electrónico</label>
                </IftaLabel>
                <Message v-if="form.errors.email" severity="error" size="small" variant="simple">
                    {{ form.errors.email }}
                </Message>
            </div>

            <Button
                :loading="form.processing"
                fluid
                icon="pi pi-send"
                label="Enviar enlace de recuperación"
                type="submit"
            />

            <p class="auth-back">
                <Link :href="route('login')" class="auth-back-link">
                    <i class="pi pi-arrow-left"/>
                    Volver al inicio de sesión
                </Link>
            </p>
        </form>
    </AuthLayout>
</template>

<script setup>
import {Link, useForm} from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

defineProps({status: {type: String, default: ''}});

const form = useForm({email: ''});

function submit() {
    form.post(route('password.email'));
}
</script>

<style scoped>
.auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.p-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.auth-back {
    margin: 0;
    text-align: center;
}

.auth-back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.82rem;
    color: var(--p-primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.2s;
}

.auth-back-link:hover {
    opacity: 0.75;
}

.auth-back-link .pi {
    font-size: 0.7rem;
}
</style>
