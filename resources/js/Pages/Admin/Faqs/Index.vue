<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    faqs: {
        type: Array,
        default: () => [],
    },
    currentCategory: {
        type: String,
        default: null,
    },
});

const isModalOpen = ref(false);
const editingFaq = ref(null);

const categories = [
    { id: 'general', label: 'General / Compatibilidad', color: 'bg-blue-100 text-blue-800' },
    { id: 'garantias', label: 'Garantías y Devoluciones', color: 'bg-amber-100 text-amber-800' },
    { id: 'envios', label: 'Envíos y Entregas', color: 'bg-purple-100 text-purple-800' },
    { id: 'pagos', label: 'Métodos de Pago y Facturación', color: 'bg-emerald-100 text-emerald-800' },
];

const getCategoryBadge = (catId) => {
    const found = categories.find(c => c.id === catId);
    return found || { label: catId, color: 'bg-gray-100 text-gray-800' };
};

const form = useForm({
    question: '',
    answer: '',
    category: 'general',
    is_active: true,
});

const openCreateModal = () => {
    editingFaq.value = null;
    form.reset();
    form.clearErrors();
    form.category = props.currentCategory || 'general';
    form.is_active = true;
    isModalOpen.value = true;
};

const openEditModal = (faq) => {
    editingFaq.value = faq;
    form.clearErrors();
    form.question = faq.question;
    form.answer = faq.answer;
    form.category = faq.category;
    form.is_active = Boolean(faq.is_active);
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingFaq.value = null;
    form.reset();
};

const submit = () => {
    if (editingFaq.value) {
        form.put(route('admin.faqs.update', editingFaq.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.faqs.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const reorder = (faqId, direction) => {
    router.post(route('admin.faqs.reorder', faqId), { direction }, {
        preserveScroll: true,
    });
};

const deleteFaq = (faq) => {
    if (confirm(`¿Eliminar la pregunta "${faq.question}"?`)) {
        router.delete(route('admin.faqs.destroy', faq.id), {
            preserveScroll: true,
        });
    }
};

const filterByCategory = (cat) => {
    router.get(route('admin.faqs.index'), cat ? { category: cat } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Preguntas Frecuentes — JC Autopartes" />

        <div class="space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-dark tracking-tight">
                        Preguntas Frecuentes (FAQ)
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Gestiona las dudas habituales de clientes sobre garantías, compatibilidad, envíos y formas de pago.
                    </p>
                </div>

                <div>
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-dark font-bold text-sm rounded-base shadow-sm transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4 text-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Nueva Pregunta</span>
                    </button>
                </div>
            </div>

            <!-- Filtros por categoría -->
            <div class="flex flex-wrap gap-2 pt-1">
                <button
                    type="button"
                    @click="filterByCategory(null)"
                    :class="!currentCategory ? 'bg-dark text-white font-bold' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                    class="px-3.5 py-1.5 rounded-full text-xs transition"
                >
                    Todas ({{ faqs.length }})
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    type="button"
                    @click="filterByCategory(cat.id)"
                    :class="currentCategory === cat.id ? 'bg-dark text-white font-bold' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                    class="px-3.5 py-1.5 rounded-full text-xs transition"
                >
                    {{ cat.label }}
                </button>
            </div>

            <!-- Tabla de Preguntas -->
            <div class="bg-white rounded-base border border-gray-200 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                            <th class="py-3 px-4 w-24 text-center">Orden</th>
                            <th class="py-3 px-4 w-44">Categoría</th>
                            <th class="py-3 px-4">Pregunta y Respuesta</th>
                            <th class="py-3 px-4 w-24 text-center">Estado</th>
                            <th class="py-3 px-4 w-28 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="faqs.length === 0">
                            <td colspan="5" class="py-12 text-center text-gray-400">
                                No hay preguntas frecuentes registradas en esta categoría.
                            </td>
                        </tr>

                        <tr v-for="(faq, index) in faqs" :key="faq.id" class="hover:bg-gray-50/70 transition">
                            <!-- Flechas de Orden -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button
                                        type="button"
                                        @click="reorder(faq.id, 'up')"
                                        :disabled="index === 0"
                                        class="p-1 rounded hover:bg-gray-200 text-gray-600 disabled:opacity-20 disabled:hover:bg-transparent"
                                        title="Subir posición"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </button>
                                    <span class="w-6 font-black text-dark text-xs">{{ faq.order }}</span>
                                    <button
                                        type="button"
                                        @click="reorder(faq.id, 'down')"
                                        :disabled="index === faqs.length - 1"
                                        class="p-1 rounded hover:bg-gray-200 text-gray-600 disabled:opacity-20 disabled:hover:bg-transparent"
                                        title="Bajar posición"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </td>

                            <!-- Categoría -->
                            <td class="py-3.5 px-4">
                                <span
                                    :class="getCategoryBadge(faq.category).color"
                                    class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold"
                                >
                                    {{ getCategoryBadge(faq.category).label }}
                                </span>
                            </td>

                            <!-- Pregunta y Respuesta -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-dark text-sm">{{ faq.question }}</div>
                                <div class="text-xs text-gray-600 mt-1 line-clamp-2 leading-relaxed">
                                    {{ faq.answer }}
                                </div>
                            </td>

                            <!-- Estado -->
                            <td class="py-3.5 px-4 text-center">
                                <span
                                    :class="faq.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
                                    class="inline-block px-2 py-0.5 rounded-full text-xs font-bold"
                                >
                                    {{ faq.is_active ? 'Visible' : 'Oculta' }}
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="openEditModal(faq)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition"
                                        title="Editar pregunta"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteFaq(faq)"
                                        class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition"
                                        title="Eliminar pregunta"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Crear / Editar FAQ -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60">
            <div class="bg-white rounded-base max-w-lg w-full p-6 space-y-5 shadow-xl">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-dark">
                        {{ editingFaq ? 'Editar Pregunta Frecuente' : 'Nueva Pregunta Frecuente' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-dark">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Categoría <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="form.category"
                            class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                        >
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Pregunta <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.question"
                            type="text"
                            placeholder="Ej: ¿Qué incluye la garantía?"
                            class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                        />
                        <p v-if="form.errors.question" class="text-xs text-rose-600 mt-1">{{ form.errors.question }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Respuesta Detallada <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="form.answer"
                            rows="4"
                            placeholder="Escribe la respuesta clara que verá el cliente..."
                            class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                        ></textarea>
                        <p v-if="form.errors.answer" class="text-xs text-rose-600 mt-1">{{ form.errors.answer }}</p>
                    </div>

                    <div class="flex items-center pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary focus:ring-primary"
                            />
                            <span class="text-sm font-bold text-dark">Pregunta Visible en la Tienda</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-semibold rounded-base hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-primary hover:bg-primary-dark text-dark text-sm font-bold rounded-base transition disabled:opacity-50"
                        >
                            {{ form.processing ? 'Guardando...' : (editingFaq ? 'Actualizar Pregunta' : 'Guardar Pregunta') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
