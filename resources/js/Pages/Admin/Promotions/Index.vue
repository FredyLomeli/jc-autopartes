<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    promotions: {
        type: Array,
        default: () => [],
    },
    coupons: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('promotions'); // 'promotions' o 'coupons'

// Modales
const isPromoModalOpen = ref(false);
const editingPromo = ref(null);

const isCouponModalOpen = ref(false);

// Formulario de Campaña
const promoForm = useForm({
    name: '',
    badge_label: 'HOT SALE',
    mode: 'direct',
    visible_discount_percentage: 10,
    real_discount_percentage: 10,
    scope_type: 'all', // 'all', 'category', 'brand'
    scope_value: '',
    starts_at: '',
    ends_at: '',
    is_active: true,
});

// Simulador en tiempo real para el cliente (100% legal, precio base vs descuento)
const simulationExample = computed(() => {
    const basePrice = 10000;
    const discount = Number(promoForm.visible_discount_percentage) || 0;
    const finalPrice = Math.max(0, basePrice - (basePrice * (discount / 100)));

    return {
        basePrice,
        strikePrice: basePrice,
        finalPrice: Math.round(finalPrice),
        badge: promoForm.badge_label || `-${discount}% OFF`,
        savingsAmount: Math.round(basePrice - finalPrice),
    };
});

// Formulario de Cupones
const couponForm = useForm({
    code: '',
    type: 'percentage',
    value: 10,
    min_order_amount: null,
    usage_limit: null,
    expires_at: '',
    is_active: true,
    is_public: false,
});

const openCreatePromoModal = () => {
    editingPromo.value = null;
    promoForm.reset();
    promoForm.clearErrors();
    promoForm.is_active = true;
    isPromoModalOpen.value = true;
};

const openEditPromoModal = (promo) => {
    editingPromo.value = promo;
    promoForm.clearErrors();
    promoForm.name = promo.name;
    promoForm.badge_label = promo.badge_label;
    promoForm.mode = promo.mode;
    promoForm.visible_discount_percentage = promo.visible_discount_percentage;
    promoForm.real_discount_percentage = promo.real_discount_percentage;
    promoForm.scope_type = promo.scope_type;
    promoForm.scope_value = promo.scope_value || '';
    promoForm.starts_at = promo.starts_at ? promo.starts_at.substring(0, 16) : '';
    promoForm.ends_at = promo.ends_at ? promo.ends_at.substring(0, 16) : '';
    promoForm.is_active = Boolean(promo.is_active);
    isPromoModalOpen.value = true;
};

const submitPromo = () => {
    if (editingPromo.value) {
        promoForm.put(route('admin.promotions.update', editingPromo.value.id), {
            onSuccess: () => { isPromoModalOpen.value = false; },
        });
    } else {
        promoForm.post(route('admin.promotions.store'), {
            onSuccess: () => { isPromoModalOpen.value = false; },
        });
    }
};

const deletePromo = (promo) => {
    if (confirm(`¿Eliminar la campaña "${promo.name}"?`)) {
        router.delete(route('admin.promotions.destroy', promo.id));
    }
};

const submitCoupon = () => {
    couponForm.post(route('admin.coupons.store'), {
        onSuccess: () => {
            isCouponModalOpen.value = false;
            couponForm.reset();
        },
    });
};

const deleteCoupon = (coupon) => {
    if (confirm(`¿Eliminar el cupón "${coupon.code}"?`)) {
        router.delete(route('admin.coupons.destroy', coupon.id));
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Campañas de Promoción y Cupones — JC Autopartes" />

        <div class="space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-dark tracking-tight">
                        Promociones y Cupones de Descuento
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Crea campañas comerciales automáticas (directas o con anclaje simulado) y gestiona códigos de cupón para el carrito.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        v-if="activeTab === 'promotions'"
                        type="button"
                        @click="openCreatePromoModal"
                        class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-bold text-sm rounded-base shadow-sm transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Nueva Campaña</span>
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="isCouponModalOpen = true"
                        class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-bold text-sm rounded-base shadow-sm transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Nuevo Cupón</span>
                    </button>
                </div>
            </div>

            <!-- Pestañas Superiores -->
            <div class="border-b border-gray-200 flex gap-4">
                <button
                    type="button"
                    @click="activeTab = 'promotions'"
                    :class="activeTab === 'promotions' ? 'border-primary text-dark font-black' : 'border-transparent text-gray-500 hover:text-dark font-medium'"
                    class="py-3 px-2 text-sm border-b-2 transition flex items-center gap-2"
                >
                    <span>Campañas de Promoción</span>
                    <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 font-bold">{{ promotions.length }}</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'coupons'"
                    :class="activeTab === 'coupons' ? 'border-primary text-dark font-black' : 'border-transparent text-gray-500 hover:text-dark font-medium'"
                    class="py-3 px-2 text-sm border-b-2 transition flex items-center gap-2"
                >
                    <span>Códigos de Cupón</span>
                    <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 font-bold">{{ coupons.length }}</span>
                </button>
            </div>

            <!-- TAB 1: CAMPAÑAS DE PROMOCIÓN -->
            <div v-show="activeTab === 'promotions'" class="space-y-4">
                <div class="bg-white rounded-base border border-gray-200 overflow-hidden shadow-sm">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                                <th class="py-3 px-4">Campaña / Distintivo</th>
                                <th class="py-3 px-4">% Descuento</th>
                                <th class="py-3 px-4">Alcance</th>
                                <th class="py-3 px-4 text-center">Estado</th>
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="promotions.length === 0">
                                <td colspan="5" class="py-12 text-center text-gray-400">
                                    No hay campañas registradas. Haz clic en <strong>"Nueva Campaña"</strong> para configurar tu primer descuento por categoría, marca o general.
                                </td>
                            </tr>
                            <tr v-for="promo in promotions" :key="promo.id" class="hover:bg-gray-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-dark">{{ promo.name }}</div>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 mt-1">
                                        {{ promo.badge_label }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 font-bold text-primary text-sm">
                                    {{ promo.visible_discount_percentage }}% OFF
                                </td>

                                <td class="py-3.5 px-4 text-xs font-medium text-gray-600">
                                    <span v-if="promo.scope_type === 'all'">Todo el Catálogo</span>
                                    <span v-else-if="promo.scope_type === 'category'">Categoría: {{ promo.scope_value }}</span>
                                    <span v-else-if="promo.scope_type === 'brand'">Marca: {{ promo.scope_value }}</span>
                                    <span v-else>Personalizado</span>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        :class="promo.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
                                        class="inline-block px-2 py-0.5 rounded-full text-xs font-bold"
                                    >
                                        {{ promo.is_active ? 'Activa' : 'Pausada' }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="openEditPromoModal(promo)"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded"
                                            title="Editar"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deletePromo(promo)"
                                            class="p-1.5 text-rose-600 hover:bg-rose-50 rounded"
                                            title="Eliminar"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: CUPONES DE DESCUENTO -->
            <div v-show="activeTab === 'coupons'" class="space-y-4">
                <div class="bg-white rounded-base border border-gray-200 overflow-hidden shadow-sm">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                                <th class="py-3 px-4">Código</th>
                                <th class="py-3 px-4">Descuento</th>
                                <th class="py-3 px-4">Compra Mínima</th>
                                <th class="py-3 px-4">Usos</th>
                                <th class="py-3 px-4 text-center">Público en Web</th>
                                <th class="py-3 px-4 text-center">Estado</th>
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="coupons.length === 0">
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    No hay códigos de cupón registrados. Crea uno con <strong>"Nuevo Cupón"</strong> para otorgar beneficios en el carrito.
                                </td>
                            </tr>
                            <tr v-for="coupon in coupons" :key="coupon.id" class="hover:bg-gray-50/70 transition">
                                <td class="py-3.5 px-4 font-mono font-black text-dark text-sm tracking-wider">
                                    {{ coupon.code }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-primary">
                                    {{ coupon.type === 'percentage' ? `${coupon.value}% OFF` : `$${coupon.value} MXN` }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-gray-600">
                                    {{ coupon.min_order_amount ? `$${coupon.min_order_amount} MXN` : 'Sin mínimo' }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-gray-600">
                                    {{ coupon.used_count }} {{ coupon.usage_limit ? `/ ${coupon.usage_limit}` : 'usos' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span :class="coupon.is_public ? 'text-emerald-700 font-bold' : 'text-gray-400'" class="text-xs">
                                        {{ coupon.is_public ? 'Visible' : 'Privado' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        :class="coupon.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
                                        class="inline-block px-2 py-0.5 rounded-full text-xs font-bold"
                                    >
                                        {{ coupon.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button
                                        type="button"
                                        @click="deleteCoupon(coupon)"
                                        class="p-1.5 text-rose-600 hover:bg-rose-50 rounded"
                                        title="Eliminar cupón"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL: CREAR / EDITAR CAMPAÑA -->
        <div v-if="isPromoModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60">
            <div class="bg-white rounded-base max-w-xl w-full p-6 space-y-5 shadow-2xl">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-black text-dark">
                        {{ editingPromo ? 'Editar Campaña de Promoción' : 'Nueva Campaña de Promoción' }}
                    </h3>
                    <button @click="isPromoModalOpen = false" class="text-gray-400 hover:text-dark">✕</button>
                </div>

                <form @submit.prevent="submitPromo" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nombre Interno de Campaña</label>
                            <input
                                v-model="promoForm.name"
                                type="text"
                                placeholder="Ej: Especial Buen Fin Motores"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Texto del Badge en Foto</label>
                            <input
                                v-model="promoForm.badge_label"
                                type="text"
                                placeholder="Ej: HOT SALE, -15% OFF"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">% de Descuento</label>
                            <div class="relative">
                                <input
                                    v-model.number="promoForm.visible_discount_percentage"
                                    type="number"
                                    min="1"
                                    max="99"
                                    step="1"
                                    class="w-full rounded-base border-gray-300 text-sm pr-8 focus:border-primary focus:ring-primary"
                                    required
                                />
                                <span class="absolute right-3 top-2 text-gray-400 font-bold">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Previsualización transparente de precio habitual vs oferta -->
                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-2">
                        <span class="font-bold text-xs text-dark block">Cálculo en tienda (Ejemplo con pieza habitual de $10,000 MXN):</span>
                        <div class="flex items-center gap-3 bg-white p-3 rounded-lg border border-gray-200 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Precio Habitual (Tachado)</span>
                                <span class="line-through text-gray-500 font-bold text-sm">${{ simulationExample.strikePrice.toLocaleString() }} MXN</span>
                            </div>
                            <div class="border-l border-gray-200 pl-3">
                                <span class="text-primary block text-[10px] uppercase font-bold">Precio de Oferta</span>
                                <span class="text-base font-black text-dark">${{ simulationExample.finalPrice.toLocaleString() }} MXN</span>
                            </div>
                            <div class="ml-auto text-right">
                                <span class="bg-rose-100 text-rose-800 text-[10px] font-black px-2 py-0.5 rounded block mb-0.5">{{ simulationExample.badge }}</span>
                                <span class="text-emerald-700 font-semibold text-xs">Ahorro: -${{ simulationExample.savingsAmount.toLocaleString() }} MXN</span>
                            </div>
                        </div>
                    </div>

                    <!-- Alcance -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">A qué artículos aplica</label>
                            <select
                                v-model="promoForm.scope_type"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            >
                                <option value="all">A todo el catálogo</option>
                                <option value="category">Por Categoría</option>
                                <option value="brand">Por Marca de Vehículo</option>
                            </select>
                        </div>

                        <div v-if="promoForm.scope_type === 'category'">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Categoría</label>
                            <select
                                v-model="promoForm.scope_value"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            >
                                <option value="" disabled>Selecciona una categoría...</option>
                                <option v-for="cat in categories" :key="cat" :value="cat">
                                    {{ cat }}
                                </option>
                            </select>
                        </div>

                        <div v-if="promoForm.scope_type === 'brand'">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Marca</label>
                            <input
                                v-model="promoForm.scope_value"
                                type="text"
                                placeholder="Ej: BMW, Nissan, Chevrolet"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>
                    </div>

                    <div class="flex items-center pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="promoForm.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <span class="text-sm font-bold text-dark">Campaña Activa en la Tienda</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t">
                        <button
                            type="button"
                            @click="isPromoModalOpen = false"
                            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-semibold rounded-base hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="promoForm.processing"
                            class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-bold rounded-base transition"
                        >
                            {{ promoForm.processing ? 'Guardando...' : 'Guardar Campaña' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: CREAR CUPÓN -->
        <div v-if="isCouponModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60">
            <div class="bg-white rounded-base max-w-md w-full p-6 space-y-5 shadow-2xl">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-black text-dark">Crear Código de Cupón</h3>
                    <button @click="isCouponModalOpen = false" class="text-gray-400 hover:text-dark">✕</button>
                </div>

                <form @submit.prevent="submitCoupon" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Código del Cupón</label>
                        <input
                            v-model="couponForm.code"
                            type="text"
                            placeholder="Ej: BUENFIN10"
                            class="w-full font-mono uppercase rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tipo</label>
                            <select
                                v-model="couponForm.type"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            >
                                <option value="percentage">Porcentaje (%)</option>
                                <option value="fixed">Monto Fijo ($)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Valor de Descuento</label>
                            <input
                                v-model.number="couponForm.value"
                                type="number"
                                min="1"
                                step="any"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Compra Mínima (Opcional)</label>
                            <input
                                v-model.number="couponForm.min_order_amount"
                                type="number"
                                min="0"
                                placeholder="$0.00"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Límite de Usos (Opcional)</label>
                            <input
                                v-model.number="couponForm.usage_limit"
                                type="number"
                                min="1"
                                placeholder="Ilimitado"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="couponForm.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <span class="text-sm font-bold text-dark">Cupón Activo</span>
                        </label>

                        <label class="block">
                            <span class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    v-model="couponForm.is_public"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary focus:ring-primary"
                                />
                                <span class="text-sm font-bold text-dark">Mostrar públicamente en página de Promociones</span>
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t">
                        <button
                            type="button"
                            @click="isCouponModalOpen = false"
                            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-semibold rounded-base hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="couponForm.processing"
                            class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-bold rounded-base transition"
                        >
                            {{ couponForm.processing ? 'Guardando...' : 'Crear Cupón' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
