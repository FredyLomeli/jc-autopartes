<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    catalogStats: {
        type: Object,
        required: true,
    },
    demoCards: {
        type: Array,
        required: true,
    },
    recentProducts: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Panel de Administración — JC Autopartes" />

    <AdminLayout
        title="Panel de Control"
        subtitle="Resumen general de catálogo e indicadores operativos"
    >
        <template #actions>
            <Link
                :href="route('admin.products.index')"
                class="bg-primary hover:bg-primary-hover text-white font-bold px-4 py-2 rounded-base text-xs sm:text-sm transition-colors"
            >
                Administrar Artículos
            </Link>
        </template>

        <div class="space-y-8">
            <!-- Estado Real del Catálogo en Base de Datos -->
            <section>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-dark">
                        Resumen de Inventario y Catálogo
                    </h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-card border border-border p-5 shadow-sm">
                        <span class="text-xs font-bold uppercase text-gray-500">Artículos Registrados</span>
                        <p class="text-2xl font-extrabold text-dark mt-1">{{ catalogStats.total_products }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ catalogStats.published_products }} publicados · {{ catalogStats.draft_products }} en borrador
                        </p>
                    </div>

                    <div class="bg-white rounded-card border border-border p-5 shadow-sm">
                        <span class="text-xs font-bold uppercase text-gray-500">Piezas Publicadas en Tienda</span>
                        <p class="text-2xl font-extrabold text-green-700 mt-1">{{ catalogStats.published_products }}</p>
                        <p class="text-xs text-gray-500 mt-1">Visibles en el catálogo público</p>
                    </div>

                    <div class="bg-white rounded-card border border-border p-5 shadow-sm">
                        <span class="text-xs font-bold uppercase text-gray-500">Existencias Totales</span>
                        <p class="text-2xl font-extrabold text-dark mt-1">{{ catalogStats.total_stock_units }} pzas</p>
                        <p class="text-xs text-gray-500 mt-1">Suma de unidades disponibles</p>
                    </div>

                    <div class="bg-white rounded-card border border-border p-5 shadow-sm">
                        <span class="text-xs font-bold uppercase text-gray-500">Valor de Inventario</span>
                        <p class="text-xl font-extrabold text-primary mt-1 whitespace-nowrap">{{ catalogStats.formatted_inventory_value }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total estimado en almacén</p>
                    </div>
                </div>
            </section>

            <!-- Indicadores Operativos -->
            <section>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-dark">
                        Indicadores Operativos
                    </h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        v-for="card in demoCards"
                        :key="card.label"
                        class="bg-white rounded-card border border-border p-5 shadow-sm flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="text-xs font-bold uppercase text-gray-500">{{ card.label }}</span>
                                <span
                                    v-if="card.badge"
                                    class="px-2 py-0.5 rounded bg-surface border border-border text-[10px] font-bold uppercase text-gray-600"
                                >
                                    {{ card.badge }}
                                </span>
                            </div>
                            <p class="text-xl font-extrabold text-dark">{{ card.value }}</p>
                        </div>
                        <p class="text-xs text-gray-500 mt-3 pt-2 border-t border-border/60">
                            {{ card.subtext }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Últimos Artículos en Catálogo -->
            <section class="bg-white rounded-card border border-border shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-extrabold text-dark">Últimos Artículos Registrados</h2>
                        <p class="text-xs text-gray-500">Vista rápida de las refacciones más recientes en el catálogo</p>
                    </div>
                    <Link
                        :href="route('admin.products.index')"
                        class="text-xs font-bold text-primary hover:underline"
                    >
                        Ver todos los artículos &rarr;
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-surface text-xs font-bold uppercase text-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left">Pieza / SKU</th>
                                <th class="px-6 py-3 text-left">Vehículo Compatible</th>
                                <th class="px-6 py-3 text-left">Estado</th>
                                <th class="px-6 py-3 text-right">Existencia</th>
                                <th class="px-6 py-3 text-right">Precio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-white">
                            <tr v-for="item in recentProducts" :key="item.id" class="hover:bg-surface/60">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="item.photo_url"
                                            :alt="item.title"
                                            loading="lazy"
                                            decoding="async"
                                            class="w-11 h-9 rounded object-cover border border-border shrink-0"
                                        />
                                        <div>
                                            <p class="font-bold text-dark">{{ item.title }}</p>
                                            <p class="text-xs font-mono text-gray-500">SKU: {{ item.sku }} · {{ item.category }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 text-xs font-semibold text-gray-700">
                                    {{ item.vehicle }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <span
                                        :class="item.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'"
                                        class="px-2.5 py-0.5 rounded text-xs font-bold"
                                    >
                                        {{ item.status === 'published' ? 'Publicado' : 'Borrador' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right font-bold text-dark">
                                    {{ item.stock }} pza(s)
                                </td>
                                <td class="px-6 py-3.5 text-right font-extrabold text-dark whitespace-nowrap">
                                    {{ item.formatted_price }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
