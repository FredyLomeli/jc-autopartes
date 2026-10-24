<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            q: '',
            payment_status: 'all',
            order_status: 'all',
            delivery_method: 'all',
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_orders: 0,
            paid_orders: 0,
            preparing_orders: 0,
            shipped_orders: 0,
            total_revenue: 0,
            formatted_total_revenue: '$0.00 MXN',
        }),
    },
});

const filterForm = reactive({
    q: props.filters.q || '',
    payment_status: props.filters.payment_status || 'all',
    order_status: props.filters.order_status || 'all',
    delivery_method: props.filters.delivery_method || 'all',
});

const applyFilters = () => {
    router.get(route('admin.orders.index'), filterForm, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.q = '';
    filterForm.payment_status = 'all';
    filterForm.order_status = 'all';
    filterForm.delivery_method = 'all';
    applyFilters();
};

const getPaymentBadgeClass = (status) => {
    switch (status) {
        case 'paid':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'pending_payment':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'failed':
            return 'bg-red-100 text-red-800 border-red-200';
        case 'refunded':
            return 'bg-purple-100 text-purple-800 border-purple-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getOrderStatusBadgeClass = (status) => {
    switch (status) {
        case 'preparing':
            return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'shipped':
            return 'bg-indigo-100 text-indigo-800 border-indigo-200';
        case 'delivered':
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'cancelled':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>

<template>
    <Head title="Pedidos y Ventas Web — Administración JC Autopartes" />

    <AdminLayout
        title="Gestión de Pedidos"
        subtitle="Control de órdenes en línea, cobros de Mercado Pago y logística de empaque"
    >
        <!-- Métricas Rápidas Superiores -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-card border border-border p-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 block">Total Recaudado</span>
                <div class="text-xl sm:text-2xl font-extrabold text-primary font-mono mt-1">
                    {{ stats.formatted_total_revenue }}
                </div>
                <span class="text-xs text-gray-500 mt-1 block">
                    {{ stats.paid_orders }} órdenes pagadas
                </span>
            </div>

            <div class="bg-white rounded-card border border-border p-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 block">Total de Órdenes</span>
                <div class="text-xl sm:text-2xl font-extrabold text-dark mt-1">
                    {{ stats.total_orders }} pedidos
                </div>
                <span class="text-xs text-gray-500 mt-1 block">
                    Registrados en sistema
                </span>
            </div>

            <div class="bg-white rounded-card border border-border p-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 block">En Preparación (Almacén)</span>
                <div class="text-xl sm:text-2xl font-extrabold text-blue-900 mt-1">
                    {{ stats.preparing_orders }} por empacar
                </div>
                <span class="text-xs text-gray-500 mt-1 block">
                    Listos para empaque o retiro
                </span>
            </div>

            <div class="bg-white rounded-card border border-border p-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 block">Enviados con Guía</span>
                <div class="text-xl sm:text-2xl font-extrabold text-indigo-900 mt-1">
                    {{ stats.shipped_orders }} en tránsito
                </div>
                <span class="text-xs text-gray-500 mt-1 block">
                    Por paquetería
                </span>
            </div>
        </div>

        <!-- Barra de Filtros y Búsqueda -->
        <div class="bg-white rounded-card border border-border p-4 shadow-sm mb-6 space-y-4">
            <form @submit.prevent="applyFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                <!-- Buscador de Texto -->
                <div class="lg:col-span-4">
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Buscar pedido</label>
                    <input
                        v-model="filterForm.q"
                        type="text"
                        placeholder="Folio (JC-...), cliente, tel, email o ID MP"
                        class="w-full rounded-base border border-border bg-surface px-3 py-2 text-xs text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                    />
                </div>

                <!-- Filtro de Pago -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Estado de Pago</label>
                    <select
                        v-model="filterForm.payment_status"
                        @change="applyFilters"
                        class="w-full rounded-base border border-border bg-surface px-3 py-2 text-xs text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                    >
                        <option value="all">Todos los cobros</option>
                        <option value="paid">Pagados</option>
                        <option value="pending_payment">Pendiente de pago</option>
                        <option value="failed">Fallidos / Rechazados</option>
                        <option value="refunded">Reembolsados</option>
                    </select>
                </div>

                <!-- Filtro de Logística -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Estado Logístico</label>
                    <select
                        v-model="filterForm.order_status"
                        @change="applyFilters"
                        class="w-full rounded-base border border-border bg-surface px-3 py-2 text-xs text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                    >
                        <option value="all">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="preparing">En preparación</option>
                        <option value="shipped">Enviado con guía</option>
                        <option value="delivered">Entregado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>

                <!-- Filtro de Entrega -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Entrega</label>
                    <select
                        v-model="filterForm.delivery_method"
                        @change="applyFilters"
                        class="w-full rounded-base border border-border bg-surface px-3 py-2 text-xs text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                    >
                        <option value="all">Todas las modalidades</option>
                        <option value="shipping">Envío a Domicilio</option>
                        <option value="pickup">Recolección Mostrador</option>
                    </select>
                </div>

                <!-- Botones de Acción -->
                <div class="lg:col-span-2 flex gap-2">
                    <button
                        type="submit"
                        class="flex-1 bg-dark hover:bg-gray-800 text-white font-bold py-2 px-3 rounded-base text-xs transition-colors text-center"
                    >
                        Filtrar
                    </button>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="bg-surface hover:bg-gray-200 text-gray-700 font-bold py-2 px-3 rounded-base text-xs transition-colors border border-border"
                        title="Limpiar filtros"
                    >
                        Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla de Pedidos -->
        <div class="bg-white rounded-card border border-border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface border-b border-border text-xs font-extrabold uppercase text-gray-600 tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Folio / Fecha</th>
                            <th class="py-3 px-4">Cliente & Contacto</th>
                            <th class="py-3 px-4">Modalidad</th>
                            <th class="py-3 px-4">Piezas</th>
                            <th class="py-3 px-4 text-right">Total</th>
                            <th class="py-3 px-4 text-center">Cobro</th>
                            <th class="py-3 px-4 text-center">Logística</th>
                            <th class="py-3 px-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-if="orders.data.length === 0">
                            <td colspan="8" class="py-12 text-center text-gray-500">
                                <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="font-bold text-dark">No se encontraron pedidos con los filtros aplicados</p>
                                <p class="text-xs text-gray-400 mt-1">Modifica los criterios de búsqueda o limpia los filtros.</p>
                            </td>
                        </tr>

                        <tr
                            v-for="order in orders.data"
                            :key="order.id"
                            class="hover:bg-surface/50 transition-colors"
                        >
                            <!-- Folio y Fecha -->
                            <td class="py-3.5 px-4">
                                <Link
                                    :href="route('admin.orders.show', order.id)"
                                    class="font-mono font-extrabold text-primary hover:underline block leading-tight"
                                >
                                    {{ order.order_number }}
                                </Link>
                                <span class="text-[11px] text-gray-500 block mt-0.5">
                                    {{ order.created_at_formatted || 'Fecha desconocida' }}
                                </span>
                            </td>

                            <!-- Cliente y Teléfono -->
                            <td class="py-3.5 px-4">
                                <strong class="text-dark font-extrabold block leading-tight">{{ order.customer_name }}</strong>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-xs text-gray-500 font-mono">{{ order.customer_phone }}</span>
                                    <a
                                        v-if="order.customer_phone"
                                        :href="`https://wa.me/${order.customer_phone.replace(/[^0-9]/g, '')}`"
                                        target="_blank"
                                        class="text-emerald-600 hover:text-emerald-700 font-bold text-[11px]"
                                        title="Contactar por WhatsApp"
                                    >
                                        [WA]
                                    </a>
                                </div>
                            </td>

                            <!-- Modalidad -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-0.5 rounded text-[11px] font-bold inline-block border"
                                    :class="order.delivery_method === 'pickup' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-blue-50 text-blue-800 border-blue-200'"
                                >
                                    {{ order.delivery_method === 'pickup' ? 'Mostrador' : 'Envío' }}
                                </span>
                            </td>

                            <!-- Piezas / Partidas -->
                            <td class="py-3.5 px-4 text-xs text-gray-700 max-w-xs truncate" :title="order.summary_item">
                                <span class="font-bold text-dark">{{ order.items_count }} pza(s):</span>
                                <span class="text-gray-500 ml-1">{{ order.summary_item }}</span>
                            </td>

                            <!-- Total -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <span class="font-extrabold text-dark font-mono text-sm block">
                                    {{ order.formatted_total }}
                                </span>
                            </td>

                            <!-- Estado de Pago -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 rounded-full text-[11px] font-extrabold inline-block border"
                                    :class="getPaymentBadgeClass(order.payment_status)"
                                >
                                    {{ order.payment_status_label }}
                                </span>
                            </td>

                            <!-- Estado Logístico -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 rounded-full text-[11px] font-extrabold inline-block border"
                                    :class="getOrderStatusBadgeClass(order.order_status)"
                                >
                                    {{ order.order_status_label }}
                                </span>
                            </td>

                            <!-- Botón Ver Detalle -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <Link
                                    :href="route('admin.orders.show', order.id)"
                                    class="bg-surface hover:bg-gray-200 text-dark font-extrabold px-3 py-1.5 rounded-base text-xs transition-colors border border-border inline-flex items-center gap-1 shadow-xs"
                                >
                                    <span>Ver</span>
                                    <span aria-hidden="true">&rarr;</span>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div
                v-if="orders.links && orders.links.length > 3"
                class="p-4 bg-surface border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600"
            >
                <div>
                    Mostrando del <span class="font-bold text-dark">{{ orders.from || 0 }}</span> al
                    <span class="font-bold text-dark">{{ orders.to || 0 }}</span> de
                    <span class="font-bold text-dark">{{ orders.total }}</span> pedidos registrados
                </div>

                <div class="flex flex-wrap gap-1">
                    <Link
                        v-for="(link, index) in orders.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'px-2.5 py-1.5 rounded border text-xs font-bold transition-colors',
                            link.active
                                ? 'bg-primary text-white border-primary'
                                : link.url
                                ? 'bg-white text-dark hover:bg-gray-100 border-border'
                                : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
