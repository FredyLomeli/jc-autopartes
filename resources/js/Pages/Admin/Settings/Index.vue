<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import MercadoLibreBatchSyncModal from '@/Components/Admin/MercadoLibreBatchSyncModal.vue';
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

const showSyncModal = ref(false);

const disconnectMeli = () => {
    if (confirm('¿Estás seguro de que deseas desconectar la cuenta de Mercado Libre? Se revocarán los tokens de lectura guardados.')) {
        router.delete(route('admin.mercadolibre.disconnect'));
    }
};

const connectMeli = () => {
    window.location.href = route('admin.mercadolibre.connect');
};

const downloadSyncReport = () => {
    window.location.href = route('admin.mercadolibre.sync-report') + '?download=1';
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
                    <!-- Mercado Libre (Fase B + Sincronización Masiva 12k) -->
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
                                    Sincronización de catálogo masivo (12,000+ refacciones)
                                </span>
                            </div>
                        </div>

                        <!-- Estado cuando está conectado -->
                        <div v-if="mercadolibre.connected" class="bg-white p-3 rounded border border-border/80 text-xs space-y-2.5">
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

                            <!-- Botones de Sincronización Masiva -->
                            <div class="pt-2 border-t border-border flex flex-col gap-2">
                                <button
                                    type="button"
                                    @click="showSyncModal = true"
                                    class="w-full bg-primary hover:bg-primary-dark text-white font-extrabold py-2 px-3 rounded-button text-xs transition-colors shadow-sm flex items-center justify-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Sincronizar Catálogo (Lotes 12k)</span>
                                </button>

                                <div class="flex items-center justify-between pt-1">
                                    <button
                                        type="button"
                                        @click="downloadSyncReport"
                                        class="text-xs text-gray-600 hover:text-dark font-medium underline inline-flex items-center gap-1"
                                    >
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Descargar Diagnóstico JSON
                                    </button>

                                    <button
                                        type="button"
                                        @click="disconnectMeli"
                                        class="text-xs text-red-600 hover:text-red-800 font-bold transition-colors"
                                    >
                                        Desconectar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Estado cuando no está conectado pero tiene credenciales -->
                        <div v-else-if="mercadolibre.has_credentials" class="space-y-3">
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

                            <div class="pt-2 border-t border-border/80 flex items-center justify-between text-xs">
                                <span class="text-gray-500">¿Pruebas en local?</span>
                                <button
                                    type="button"
                                    @click="showSyncModal = true"
                                    class="text-primary font-bold hover:underline"
                                >
                                    Probar con Fixture
                                </button>
                            </div>
                        </div>

                        <!-- Estado cuando faltan credenciales en .env -->
                        <div v-else class="text-xs text-gray-500 bg-amber-50/70 p-2.5 rounded border border-amber-200/60 space-y-2">
                            <p>
                                Para vincular la cuenta oficial, define <code class="bg-amber-100 text-amber-900 px-1 py-0.5 rounded font-mono text-[11px]">MELI_APP_ID</code> y <code class="bg-amber-100 text-amber-900 px-1 py-0.5 rounded font-mono text-[11px]">MELI_CLIENT_SECRET</code> en el archivo <code class="font-mono">.env</code>.
                            </p>
                            <div class="pt-1 flex items-center justify-between border-t border-amber-200/60">
                                <span class="text-gray-600">Probar simulador de 12k:</span>
                                <button
                                    type="button"
                                    @click="showSyncModal = true"
                                    class="text-primary font-bold hover:underline"
                                >
                                    Abrir Modal de Lotes
                                </button>
                            </div>
                        </div>
                    </li>

                    <li class="p-3.5 rounded-base bg-surface border border-border flex items-center justify-between">
                        <div>
                            <strong class="block text-dark">Mercado Pago</strong>
                            <span class="text-xs text-gray-500">Cobro en línea Checkout Pro con blindaje de stock</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-xs font-bold">Activo</span>
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

        <!-- Modal Interactivo de Sincronización Masiva -->
        <MercadoLibreBatchSyncModal
            v-model="showSyncModal"
            :connected="mercadolibre.connected"
        />
    </AdminLayout>
</template>
