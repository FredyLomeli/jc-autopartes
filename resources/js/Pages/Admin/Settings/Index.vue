<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    business: {
        type: Object,
        required: true,
    },
});
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

                <ul class="space-y-3 text-sm">
                    <li class="p-3.5 rounded-base bg-surface border border-border flex items-center justify-between">
                        <div>
                            <strong class="block text-dark">Mercado Libre</strong>
                            <span class="text-xs text-gray-500">Importación y sincronización de catálogo</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-xs font-bold">Siguiente etapa</span>
                    </li>
                    <li class="p-3.5 rounded-base bg-surface border border-border flex items-center justify-between">
                        <div>
                            <strong class="block text-dark">Mercado Pago</strong>
                            <span class="text-xs text-gray-500">Cobro en línea Checkout Pro</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-xs font-bold">Siguiente etapa</span>
                    </li>
                    <li class="p-3.5 rounded-base bg-surface border border-border flex items-center justify-between">
                        <div>
                            <strong class="block text-dark">Paquetexpress</strong>
                            <span class="text-xs text-gray-500">Cotización de envío por C.P. y dimensiones</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-xs font-bold">Siguiente etapa</span>
                    </li>
                    <li class="p-3.5 rounded-base bg-surface border border-border flex items-center justify-between">
                        <div>
                            <strong class="block text-dark">WhatsApp Cloud API (Meta)</strong>
                            <span class="text-xs text-gray-500">Atención centralizada y notificaciones</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-xs font-bold">Siguiente etapa</span>
                    </li>
                </ul>
            </section>
        </div>
    </AdminLayout>
</template>
