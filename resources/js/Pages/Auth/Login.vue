<script setup>
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Acceso Administrativo — JC Autopartes" />

    <div class="min-h-screen flex flex-col justify-center items-center bg-surface px-4 py-10">
        <!-- Encabezado de Marca -->
        <div class="w-full max-w-md mb-6 text-center">
            <a href="/" class="inline-flex flex-col items-center gap-3 group">
                <img
                    src="/imagenes/brand/logo.jpg"
                    alt="Logo JC Autopartes"
                    width="64"
                    height="64"
                    style="max-height: 4rem; width: auto;"
                    class="max-h-16 h-16 w-auto object-contain rounded-base border border-border shadow-sm"
                />
                <div>
                    <span class="block text-xl font-extrabold tracking-tight text-dark">
                        JC <span class="text-primary">AUTOPARTES</span>
                    </span>
                    <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mt-0.5">
                        Panel de Administración
                    </span>
                </div>
            </a>
        </div>

        <!-- Tarjeta de Inicio de Sesión -->
        <div class="w-full max-w-md bg-white rounded-card border border-border shadow-sm overflow-hidden">
            <div class="bg-dark text-white px-6 py-4 border-b-2 border-primary">
                <h1 class="text-base font-extrabold tracking-tight">Iniciar Sesión en Administración</h1>
                <p class="text-xs text-gray-300 mt-0.5">Ingresa con tus credenciales autorizadas</p>
            </div>

            <div class="p-6">
                <div
                    v-if="status"
                    class="mb-4 rounded-base bg-green-50 border border-green-300 px-3.5 py-2.5 text-xs font-semibold text-green-800"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                            Correo Electrónico
                        </label>
                        <input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="admin@jcautopartes.mx"
                            class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        />
                        <InputError class="mt-1.5" :message="form.errors.email" />
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                            Contraseña
                        </label>
                        <input
                            id="password"
                            type="password"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        />
                        <InputError class="mt-1.5" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-gray-700 font-medium">
                            <input
                                type="checkbox"
                                name="remember"
                                v-model="form.remember"
                                class="rounded border-border text-primary focus:ring-primary"
                            />
                            <span>Mantener sesión activa</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="font-semibold text-gray-600 hover:text-primary transition-colors"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-primary hover:bg-primary-hover text-white font-extrabold py-2.5 px-4 rounded-base text-sm transition-colors disabled:opacity-50 shadow-sm"
                    >
                        {{ form.processing ? 'Verificando acceso...' : 'Entrar al Panel Administrativo' }}
                    </button>
                </form>

                <!-- Credenciales de prueba visibles para ambiente de demostración Fase A0 -->
                <div class="mt-5 pt-4 border-t border-border text-xs text-gray-600 bg-surface rounded-base p-3">
                    <span class="font-bold text-dark block mb-1">Acceso de prueba (Fase A0):</span>
                    <span class="block font-mono">Usuario: admin@jcautopartes.mx</span>
                    <span class="block font-mono">Contraseña: password</span>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <a href="/" class="text-xs font-bold text-gray-600 hover:text-primary transition-colors">
                &larr; Volver a la tienda pública de JC Autopartes
            </a>
        </div>
    </div>
</template>
