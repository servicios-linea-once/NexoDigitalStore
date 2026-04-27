<template>
    <AuthLayout subtitle="Bienvenido de vuelta a Nexo Digital Store" title="Iniciar sesión">
        <form class="flex flex-col gap-5" @submit.prevent="submit">

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

            <div class="flex flex-col gap-1.5">
                <IftaLabel>
                    <Password
                        id="reg-password"
                        v-model="form.password"
                        :disabled="form.processing"
                        :invalid="!!form.errors.password"
                        fluid
                        mediumLabel="Media"
                        promptLabel="Escribe tu contraseña"
                        strongLabel="Fuerte"
                        toggleMask
                        weakLabel="Débil"
                    />
                    <label for="reg-password">Contraseña</label>
                </IftaLabel>
                <Message v-if="form.errors.password" severity="error" size="small" variant="simple">
                    {{ form.errors.password }}
                </Message>
            </div>

            <div class="flex items-center justify-between mt-1 mb-2">
                <div class="flex items-center gap-2 group">
                    <Checkbox v-model="form.remember" binary inputId="login-remember"/>
                    <label
                        class="cursor-pointer text-sm text-[var(--p-text-muted-color)] transition-colors duration-200 group-hover:text-[var(--p-text-color)]"
                        for="login-remember"
                    >
                        Recordarme
                    </label>
                </div>
                <Link
                    :href="route('password.request')"
                    class="text-sm font-medium text-[var(--p-primary-color)] no-underline transition-colors duration-200 hover:opacity-80"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <Message v-if="status" :closable="false" class="w-full" severity="success">
                {{ status }}
            </Message>

            <Button
                :disabled="form.processing"
                :loading="form.processing"
                fluid
                icon="pi pi-sign-in"
                label="Iniciar sesión"
                type="submit"
            />

            <p class="mt-2 text-center text-sm text-[var(--c-text-muted)]">
                ¿No tienes cuenta?
                <Link
                    :href="route('register')"
                    class="ml-1 font-semibold text-[var(--p-primary-color)] no-underline transition-colors duration-200 hover:opacity-80"
                >
                    Regístrate gratis
                </Link>
            </p>
        </form>
    </AuthLayout>
</template>

<script setup>
import {Link, useForm} from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

defineProps({
    status: {
        type: String,
        default: '',
    }
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>
