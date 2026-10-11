<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Clientes — Administración JC Autopartes" />

    <AdminLayout
        title="Clientes"
        subtitle="Directorio de compradores e historial de atención por WhatsApp"
    >
        <div class="bg-white rounded-card border border-border p-10 sm:p-14 text-center max-w-2xl mx-auto shadow-sm my-6">
            <span class="inline-block px-3 py-1 rounded-base bg-primary text-white text-xs font-extrabold uppercase tracking-wider mb-3">
                Próximamente
            </span>
            <h2 class="text-xl font-extrabold text-dark">
                Directorio de Clientes y Atención WhatsApp en Construcción
            </h2>
            <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                En las siguientes etapas se integrará aquí la cartera de clientes recurrentes, sus pedidos asociados y el historial de conversaciones con la API oficial de WhatsApp.
            </p>
        </div>
    </AdminLayout>
</template>
