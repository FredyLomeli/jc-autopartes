<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    business: {
        type: Object,
        required: true,
    },
});

const statusForm = useForm({
    order_status: props.order.order_status,
    notes: '',
});

const showStatusModal = ref(false);

const updateStatus = () => {
    if (statusForm.order_status === 'cancelled') {
        if (!confirm('¿Estás seguro de cancelar este pedido? Si el pedido ya estaba pagado, el stock de las refacciones se restituirá automáticamente al inventario.')) {
            return;
        }
    }

    statusForm.patch(route('admin.orders.update-status', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            showStatusModal.value = false;
            statusForm.notes = '';
        },
    });
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
    <Head :title="`Pedido ${order.order_number} — Administración JC Autopartes`" />

    <AdminLayout
        :title="`Pedido ${order.order_number}`"
        subtitle="Detalle de venta, refacciones empaquetadas, comprobante y logística"
    >
        <!-- Barra Superior: Navegación y Acciones -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.orders.index')"
                    class="bg-white hover:bg-surface text-gray-700 font-bold px-3 py-1.5 rounded-base text-xs border border-border transition-colors flex items-center gap-1 shadow-xs"
                >
                    &larr; Volver a pedidos
                </Link>
                <span class="text-xs text-gray-400">|</span>
                <span class="text-xs text-gray-500">
                    Registrado el <strong>{{ order.created_at_formatted }}</strong>
                </span>
            </div>

            <!-- Botón Cambiar Estado -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="showStatusModal = !showStatusModal"
                    class="bg-primary hover:bg-primary-dark text-white font-extrabold px-4 py-2 rounded-base text-xs transition-colors shadow-sm flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Actualizar Estado Logístico</span>
                </button>
            </div>
        </div>

        <!-- Formulario Desplegable de Cambio de Estado -->
        <div v-if="showStatusModal" class="bg-white rounded-card border-2 border-primary/30 p-5 shadow-sm mb-6 space-y-4">
            <div class="flex items-center justify-between border-b border-border pb-2">
                <h3 class="text-sm font-extrabold text-dark flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                    Actualizar Estado del Pedido {{ order.order_number }}
                </h3>
                <button
                    type="button"
                    @click="showStatusModal = false"
                    class="text-xs text-gray-400 hover:text-gray-600 font-bold"
                >
                    ✕ Cerrar
                </button>
            </div>

            <form @submit.prevent="updateStatus" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Nuevo Estado</label>
                        <select
                            v-model="statusForm.order_status"
                            class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-xs text-dark focus:border-primary focus:bg-white"
                        >
                            <option value="pending">Pendiente (Sin procesar)</option>
                            <option value="preparing">En preparación (Almacén)</option>
                            <option value="shipped">Enviado con guía (Paquetería)</option>
                            <option value="delivered">Entregado al cliente</option>
                            <option value="cancelled">Cancelado (Restituir inventario)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Notas / Guía de Rastreo (Opcional)</label>
                        <input
                            v-model="statusForm.notes"
                            type="text"
                            placeholder="Ej. Guía Paquetexpress 1234567890 o notas de empaque"
                            class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-xs text-dark focus:border-primary focus:bg-white"
                        />
                    </div>
                </div>

                <div v-if="statusForm.order_status === 'cancelled'" class="bg-red-50 border border-red-200 p-3 rounded-base text-xs text-red-800">
                    ⚠️ <strong>Atención:</strong> Cancelar una orden pagada marcará el pedido como cancelado y devolverá automáticamente las piezas al stock disponible en el almacén.
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showStatusModal = false"
                        class="bg-surface hover:bg-gray-200 text-gray-700 font-bold px-3 py-1.5 rounded-base text-xs transition-colors border border-border"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="statusForm.processing"
                        class="bg-dark hover:bg-gray-800 text-white font-extrabold px-4 py-1.5 rounded-base text-xs transition-colors disabled:opacity-50"
                    >
                        {{ statusForm.processing ? 'Guardando...' : 'Confirmar Cambio de Estado' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Cuadrícula Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Columna Izquierda: Partidas y Cobro (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Resumen de Estados del Pedido -->
                <div class="bg-white rounded-card border border-border p-5 shadow-sm flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <span class="text-xs uppercase font-extrabold text-gray-500 block">Folio Comercial</span>
                        <strong class="font-mono text-xl sm:text-2xl font-extrabold text-primary">
                            {{ order.order_number }}
                        </strong>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="text-right">
                            <span class="text-[11px] uppercase font-bold text-gray-400 block">Cobro</span>
                            <span
                                class="px-2.5 py-1 rounded-full text-xs font-extrabold inline-block border mt-0.5"
                                :class="getPaymentBadgeClass(order.payment_status)"
                            >
                                {{ order.payment_status_label }}
                            </span>
                        </div>

                        <div class="text-right">
                            <span class="text-[11px] uppercase font-bold text-gray-400 block">Logística</span>
                            <span
                                class="px-2.5 py-1 rounded-full text-xs font-extrabold inline-block border mt-0.5"
                                :class="getOrderStatusBadgeClass(order.order_status)"
                            >
                                {{ order.order_status_label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Lista de Refacciones Vendidas -->
                <div class="bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                    <h2 class="text-base font-extrabold text-dark border-b border-border pb-3">
                        Refacciones Compradas ({{ order.items.length }})
                    </h2>

                    <div class="divide-y divide-border">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <img
                                    v-if="item.photo_url"
                                    :src="item.photo_url"
                                    :alt="item.product_title"
                                    class="w-14 h-14 rounded object-cover border border-border shrink-0 bg-surface"
                                />
                                <div
                                    v-else
                                    class="w-14 h-14 rounded bg-surface border border-border flex items-center justify-center text-gray-400 shrink-0 text-xs font-bold"
                                >
                                    Sin foto
                                </div>

                                <div class="min-w-0">
                                    <h3 class="font-extrabold text-dark text-sm leading-snug truncate">
                                        {{ item.product_title }}
                                    </h3>
                                    <div class="text-xs text-gray-500 mt-1 space-x-2">
                                        <span>SKU: <strong class="font-mono text-dark">{{ item.product_sku }}</strong></span>
                                        <span v-if="item.part_number">· No. Parte: <strong class="font-mono text-dark">{{ item.part_number }}</strong></span>
                                        <span class="inline-block px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 text-[10px] font-bold">
                                            Garantía: {{ item.warranty_days }} días
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right shrink-0 sm:pl-4">
                                <span class="font-extrabold text-dark font-mono text-sm block">
                                    {{ item.formatted_subtotal }}
                                </span>
                                <span class="text-xs text-gray-400 block">
                                    {{ item.quantity }} x {{ item.formatted_unit_price }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Desglose de Totales -->
                    <div class="border-t border-border pt-4 max-w-xs ml-auto space-y-1.5 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal piezas:</span>
                            <span class="font-bold text-dark font-mono">{{ order.formatted_subtotal }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Costo de envío:</span>
                            <span class="font-bold text-dark font-mono">
                                {{ order.shipping_cost > 0 ? order.formatted_shipping_cost : 'Sin costo' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-baseline pt-2 border-t border-border">
                            <span class="font-extrabold text-dark">Total del Pedido:</span>
                            <span class="font-extrabold text-primary font-mono text-lg">{{ order.formatted_total }}</span>
                        </div>
                    </div>
                </div>

                <!-- Datos de Pago en Mercado Pago -->
                <div class="bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                    <h2 class="text-base font-extrabold text-dark border-b border-border pb-3 flex items-center justify-between">
                        <span>Detalles de Transacción (Mercado Pago)</span>
                        <span class="text-xs text-gray-400 uppercase font-mono">{{ order.payment_gateway || 'mercadopago' }}</span>
                    </h2>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <dt class="font-bold text-gray-500 uppercase">ID de Cobro en Mercado Pago</dt>
                            <dd class="font-mono text-sm font-bold text-dark mt-0.5">
                                {{ order.mercadopago_payment_id || 'Pendiente de acreditación' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-bold text-gray-500 uppercase">Tipo de Medio de Pago</dt>
                            <dd class="text-sm font-bold text-dark mt-0.5">
                                {{ order.mercadopago_payment_type || 'Checkout Pro' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-bold text-gray-500 uppercase">Fecha de Acreditación</dt>
                            <dd class="font-medium text-dark mt-0.5">
                                {{ order.paid_at_formatted || 'No acreditado aún' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-bold text-gray-500 uppercase">ID de Preferencia Checkout</dt>
                            <dd class="font-mono text-xs text-gray-600 mt-0.5 truncate" :title="order.mercadopago_preference_id">
                                {{ order.mercadopago_preference_id || 'N/A' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Columna Derecha: Cliente y Entrega (4 Cols) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Tarjeta del Comprador -->
                <div class="bg-white rounded-card border border-border p-5 shadow-sm space-y-3">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-dark border-b border-border pb-2">
                        Datos del Comprador
                    </h3>

                    <div class="space-y-2 text-xs">
                        <div>
                            <span class="text-gray-500 block">Nombre completo:</span>
                            <strong class="text-dark text-sm block">{{ order.customer_name }}</strong>
                        </div>

                        <div>
                            <span class="text-gray-500 block">Teléfono / WhatsApp:</span>
                            <div class="flex items-center justify-between mt-0.5">
                                <span class="font-mono font-bold text-dark text-sm">{{ order.customer_phone }}</span>
                                <a
                                    v-if="order.customer_phone"
                                    :href="`https://wa.me/${order.customer_phone.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(`Hola ${order.customer_name}, te escribimos de JC Autopartes sobre tu pedido web ${order.order_number}.`)}`"
                                    target="_blank"
                                    class="bg-emerald-50 text-emerald-700 hover:bg-emerald-100 px-2 py-1 rounded text-xs font-bold transition-colors inline-flex items-center gap-1 border border-emerald-200"
                                >
                                    <span>Escribir WA</span>
                                </a>
                            </div>
                        </div>

                        <div>
                            <span class="text-gray-500 block">Correo electrónico:</span>
                            <a :href="`mailto:${order.customer_email}`" class="text-primary hover:underline font-medium break-all">
                                {{ order.customer_email }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Logística y Entrega -->
                <div class="bg-white rounded-card border border-border p-5 shadow-sm space-y-3">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-dark border-b border-border pb-2 flex items-center justify-between">
                        <span>Modalidad de Entrega</span>
                        <span
                            class="px-2 py-0.5 rounded text-[10px] font-bold border"
                            :class="order.delivery_method === 'pickup' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-blue-50 text-blue-800 border-blue-200'"
                        >
                            {{ order.delivery_method === 'pickup' ? 'Mostrador' : 'Paquetería' }}
                        </span>
                    </h3>

                    <div v-if="order.delivery_method === 'shipping'" class="text-xs space-y-2 text-gray-700">
                        <div>
                            <span class="text-gray-500 block">Dirección de Envío:</span>
                            <strong class="text-dark block mt-0.5 leading-relaxed">
                                {{ order.shipping_address }}<br />
                                Col. {{ order.shipping_neighborhood }}<br />
                                {{ order.shipping_city }}, {{ order.shipping_state }}<br />
                                C.P. {{ order.shipping_postal_code }}
                            </strong>
                        </div>

                        <div v-if="order.shipping_notes" class="bg-surface p-2.5 rounded border border-border mt-2">
                            <span class="text-[11px] font-bold text-gray-500 block">Referencias:</span>
                            <p class="text-gray-700 italic mt-0.5">{{ order.shipping_notes }}</p>
                        </div>
                    </div>

                    <div v-else class="text-xs space-y-2 text-gray-700">
                        <div>
                            <span class="text-gray-500 block">Sucursal de Entrega:</span>
                            <strong class="text-dark block mt-0.5 leading-relaxed">
                                {{ business.address }}
                            </strong>
                        </div>
                        <p class="text-gray-500 text-[11px]">
                            Horario: {{ business.hours.weekdays }} · {{ business.hours.saturday }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
