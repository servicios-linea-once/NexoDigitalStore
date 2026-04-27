<template>
    <AuthLayout subtitle="Únete a miles de compradores en Nexo Digital Store" title="Crear cuenta">
        <form class="flex flex-col gap-[1.125rem]" @submit.prevent="submit">

            <div class="flex flex-col gap-1.5">
                <IftaLabel>
                    <IconField>
                        <InputIcon class="pi pi-user"/>
                        <InputText
                            id="reg-name"
                            v-model="form.name"
                            :disabled="form.processing"
                            :invalid="!!form.errors.name"
                            autocomplete="name"
                            fluid
                        />
                    </IconField>
                    <label for="reg-name">Nombre completo</label>
                </IftaLabel>
                <Message v-if="form.errors.name" severity="error" size="small" variant="simple">
                    {{ form.errors.name }}
                </Message>
            </div>

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

            <div class="flex flex-col gap-1.5">
                <IftaLabel>
                    <Password
                        id="reg-password-confirm"
                        v-model="form.password_confirmation"
                        :disabled="form.processing"
                        :feedback="false"
                        :invalid="!!form.errors.password_confirmation"
                        fluid
                        toggleMask
                    />
                    <label for="reg-password-confirm">Confirmar contraseña</label>
                </IftaLabel>
                <Message v-if="form.errors.password_confirmation" severity="error" size="small" variant="simple">
                    {{ form.errors.password_confirmation }}
                </Message>
            </div>

            <div>
                <div class="flex items-center gap-2 text-[0.82rem] text-[var(--p-text-muted-color)] cursor-pointer">
                    <Checkbox v-model="form.terms" :invalid="!!form.errors.terms" binary input-id="reg-terms"/>
                    <label class="cursor-pointer" for="reg-terms">
                        Acepto los
                        <a
                            :href="route('terms')"
                            class="font-medium text-[var(--p-primary-color)] no-underline transition-opacity duration-200 hover:opacity-80"
                            target="_blank"
                        >
                            Términos y condiciones
                        </a>
                    </label>
                </div>
                <Message v-if="form.errors.terms" class="mt-1" severity="error" size="small" variant="simple">
                    {{ form.errors.terms }}
                </Message>
            </div>

            <Button
                :disabled="form.processing"
                :loading="form.processing"
                fluid
                icon="pi pi-user-plus"
                label="Crear mi cuenta"
                type="submit"
            />

            <p class="m-0 text-center text-[0.82rem] text-[var(--p-text-muted-color)]">
                ¿Ya tienes cuenta?
                <Link
                    :href="route('login')"
                    class="ml-1 font-semibold text-[var(--p-primary-color)] no-underline transition-opacity duration-200 hover:opacity-80"
                >
                    Iniciar sesión
                </Link>
            </p>
        </form>
    </AuthLayout>
</template>

<script setup>
import {Link, useForm} from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

function submit() {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>
