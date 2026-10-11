<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Pedidos — Administración JC Autopartes" />

    <AdminLayout
        title="Pedidos"
        subtitle="Gestión de órdenes web, pagos y guías de envío"
    >
        <div class="bg-white rounded-card border border-border p-10 sm:p-14 text-center max-w-2xl mx-auto shadow-sm my-6">
            <span class="inline-block px-3 py-1 rounded-base bg-primary text-white text-xs font-extrabold uppercase tracking-wider mb-3">
                Próximamente
            </span>
            <h2 class="text-xl font-extrabold text-dark">
                Módulo de Pedidos y Envíos en Construcción
            </h2>
            <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                En las siguientes etapas se habilitará aquí el listado de pedidos pagados con Mercado Pago, la validación de existencias y la generación de cotizaciones/guías con Paquetexpress.
            </p>
        </div>
    </AdminLayout>
</template>
