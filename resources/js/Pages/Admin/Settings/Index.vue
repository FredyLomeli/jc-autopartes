<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    business: {
        type: Object,
        required: true,
    },
    mercadolibre: {
        type: Object,
        default: () => ({
            has_credentials: false,
            app_id: null,
            connected: false,
            user_id: null,
            expires_at: null,
            expires_at_formatted: null,
            is_expired: false,
            auth_url: null,
        }),
    },
});

const disconnectMeli = () => {
    if (confirm('¿Estás seguro de que deseas desconectar la cuenta de Mercado Libre? Se revocarán los tokens de lectura guardados.')) {
        router.delete(route('admin.mercadolibre.disconnect'));
    }
};

const connectMeli = () => {
    window.location.href = route('admin.mercadolibre.connect');
};
</script>

<template>
    <Head title="Configuración — Administración JC Autopartes" />

    <AdminLayout
        title="Configuración General"
        subtitle="Datos comerciales de sucursal, plazos de garantía e integraciones"
    >
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Datos Oficiales del Negocio -->
            <section class="lg:col-span-7 bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-dark border-b border-border pb-3">
                    Datos Oficiales de Sucursal (Tepatitlán)
                </h2>

                <dl class="divide-y divide-border text-sm">
                    <div class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1">
                        <dt class="text-gray-500 font-semibold">Nombre Comercial</dt>
                        <dd class="font-bold text-dark">{{ business.name }}</dd>
                    </div>
                    <div class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1">
                        <dt class="text-gray-500 font-semibold">Dirección</dt>
                        <dd class="font-bold text-dark sm:text-right max-w-md">{{ business.address }}</dd>
                    </div>
                    <div class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1">
                        <dt class="text-gray-500 font-semibold">WhatsApp Ventas</dt>
                        <dd class="font-bold text-dark">{{ business.whatsapp_display }}</dd>
                    </div>
                    <div class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1">
                        <dt class="text-gray-500 font-semibold">Teléfono Fijo</dt>
                        <dd class="font-bold text-dark">{{ business.phone_display }}</dd>
                    </div>
                    <div class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1">
                        <dt class="text-gray-500 font-semibold">Correo Electrónico</dt>
                        <dd class="font-bold text-dark">{{ business.email }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Estado de Integraciones por Etapa -->
            <section class="lg:col-span-5 bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-dark border-b border-border pb-3">
                    Integraciones Externas
                </h2>

                <ul class="space-y-4 text-sm">
                    <!-- Mercado Libre (Fase B Activa) -->
                    <li class="p-4 rounded-base bg-surface border border-border space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <strong class="text-dark font-extrabold">Mercado Libre</strong>
                                    <span
                                        v-if="mercadolibre.connected"
                                        class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold inline-flex items-center gap-1"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Conectado
                                    </span>
                                    <span
                                        v-else-if="mercadolibre.has_credentials"
                                        class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-[11px] font-bold"
                                    >
                                        Listo para vincular
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold"
                                    >
                                        Faltan credenciales .env
                                    </span>
                                </div>
                                <span class="block text-xs text-gray-500 mt-0.5">
                                    Importación de catálogo de solo lectura (Fase B)
                                </span>
                            </div>
                        </div>

                        <!-- Estado cuando está conectado -->
                        <div v-if="mercadolibre.connected" class="bg-white p-3 rounded border border-border/80 text-xs space-y-1.5">
                            <div class="flex justify-between">
                                <span class="text-gray-500">ID Vendedor ML:</span>
                                <span class="font-mono font-bold text-dark">{{ mercadolibre.user_id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Token vigente hasta:</span>
                                <span class="font-medium" :class="mercadolibre.is_expired ? 'text-red-600 font-bold' : 'text-gray-700'">
                                    {{ mercadolibre.expires_at_formatted || 'Desconocido' }}
                                </span>
                            </div>
                            <div class="pt-2 flex justify-end">
                                <button
                                    type="button"
                                    @click="disconnectMeli"
                                    class="text-xs text-red-600 hover:text-red-800 font-bold transition-colors"
                                >
                                    Desconectar cuenta
                                </button>
                            </div>
                        </div>

                        <!-- Estado cuando no está conectado pero tiene credenciales -->
                        <div v-else-if="mercadolibre.has_credentials" class="space-y-2">
                            <p class="text-xs text-gray-600">
                                Las credenciales de la app de Mercado Libre están configuradas. Haz clic para autorizar el acceso:
                            </p>
                            <button
                                type="button"
                                @click="connectMeli"
                                class="w-full bg-[#FFE600] hover:bg-[#F5DC00] text-[#2D3277] font-extrabold py-2 px-3 rounded-base text-xs transition-colors shadow-sm flex items-center justify-center gap-2"
                            >
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                </svg>
                                <span>Conectar con Mercado Libre</span>
                            </button>
                        </div>

                        <!-- Estado cuando faltan credenciales en .env -->
                        <div v-else class="text-xs text-gray-500 bg-amber-50/70 p-2.5 rounded border border-amber-200/60">
                            Para vincular la cuenta oficial, define <code class="bg-amber-100 text-amber-900 px-1 py-0.5 rounded font-mono text-[11px]">MELI_APP_ID</code> y <code class="bg-amber-100 text-amber-900 px-1 py-0.5 rounded font-mono text-[11px]">MELI_CLIENT_SECRET</code> en el archivo <code class="font-mono">.env</code>.
                        </div>
                    </li>

                    <li class="p-3.5 rounded-base bg-surface border border-border flex items-center justify-between opacity-75">
                        <div>
                            <strong class="block text-dark">Mercado Pago</strong>
                            <span class="text-xs text-gray-500">Cobro en línea Checkout Pro</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-xs font-bold">Fase C</span>
                    </li>
                    <li class="p-3.5 rounded-base bg-surface border border-border flex items-center justify-between opacity-75">
                        <div>
                            <strong class="block text-dark">Paquetexpress</strong>
                            <span class="text-xs text-gray-500">Cotización de envío por C.P. y dimensiones</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-xs font-bold">Fase D</span>
                    </li>
                    <li class="p-3.5 rounded-base bg-surface border border-border flex items-center justify-between opacity-75">
                        <div>
                            <strong class="block text-dark">WhatsApp Cloud API (Meta)</strong>
                            <span class="text-xs text-gray-500">Atención centralizada y notificaciones</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-xs font-bold">Fase F</span>
                    </li>
                </ul>
            </section>
        </div>
    </AdminLayout>
</template>
