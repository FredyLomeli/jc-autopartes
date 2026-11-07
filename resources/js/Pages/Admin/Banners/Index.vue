<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    banners: {
        type: Array,
        default: () => [],
    },
});

const isModalOpen = ref(false);
const editingBanner = ref(null);
const fileInput = ref(null);
const previewUrl = ref(null);

const form = useForm({
    title: '',
    subtitle: '',
    image: null,
    button_text: '',
    button_url: '',
    is_active: true,
});

const openCreateModal = () => {
    editingBanner.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    previewUrl.value = null;
    isModalOpen.value = true;
};

const openEditModal = (banner) => {
    editingBanner.value = banner;
    form.clearErrors();
    form.title = banner.title || '';
    form.subtitle = banner.subtitle || '';
    form.image = null;
    form.button_text = banner.button_text || '';
    form.button_url = banner.button_url || '';
    form.is_active = Boolean(banner.is_active);
    previewUrl.value = banner.image_url;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingBanner.value = null;
    form.reset();
    previewUrl.value = null;
};

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    if (editingBanner.value) {
        // En Inertia para enviar archivos en update se usa post con _method: 'PUT'
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('admin.banners.update', editingBanner.value.id), {
            onSuccess: () => closeModal(),
            forceFormData: true,
        });
    } else {
        form.transform((data) => {
            const copy = { ...data };
            delete copy._method;
            return copy;
        }).post(route('admin.banners.store'), {
            onSuccess: () => closeModal(),
            forceFormData: true,
        });
    }
};

const reorder = (bannerId, direction) => {
    router.post(route('admin.banners.reorder', bannerId), { direction }, {
        preserveScroll: true,
    });
};

const deleteBanner = (banner) => {
    if (confirm(`¿Estás seguro de que deseas eliminar este banner?`)) {
        router.delete(route('admin.banners.destroy', banner.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Banners de Inicio — JC Autopartes" />

        <div class="space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-dark tracking-tight">
                        Banners de Inicio (Carrusel Principal)
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Sube imágenes y controla el orden en que se presentarán en la página de inicio.
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
                        <span>Nuevo Banner</span>
                    </button>
                </div>
            </div>

            <!-- Tabla de Banners -->
            <div class="bg-white rounded-base border border-gray-200 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                            <th class="py-3 px-4 w-28 text-center">Orden</th>
                            <th class="py-3 px-4 w-32">Imagen</th>
                            <th class="py-3 px-4">Título y Subtítulo</th>
                            <th class="py-3 px-4 w-40">Botón / Enlace</th>
                            <th class="py-3 px-4 w-24 text-center">Estado</th>
                            <th class="py-3 px-4 w-32 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="banners.length === 0">
                            <td colspan="6" class="py-12 text-center text-gray-400">
                                No hay banners registrados todavía. Haz clic en <strong>"Nuevo Banner"</strong> para subir el primero.
                            </td>
                        </tr>

                        <tr v-for="(banner, index) in banners" :key="banner.id" class="hover:bg-gray-50/70 transition">
                            <!-- Control de Reordenamiento Directo con Flechas -->
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button
                                        type="button"
                                        @click="reorder(banner.id, 'up')"
                                        :disabled="index === 0"
                                        class="p-1 rounded hover:bg-gray-200 text-gray-600 disabled:opacity-20 disabled:hover:bg-transparent"
                                        title="Subir posición (mostrar antes)"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </button>
                                    <span class="w-6 font-black text-dark text-sm">{{ banner.order }}</span>
                                    <button
                                        type="button"
                                        @click="reorder(banner.id, 'down')"
                                        :disabled="index === banners.length - 1"
                                        class="p-1 rounded hover:bg-gray-200 text-gray-600 disabled:opacity-20 disabled:hover:bg-transparent"
                                        title="Bajar posición (mostrar después)"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </td>

                            <!-- Miniatura -->
                            <td class="py-3 px-4">
                                <img
                                    :src="banner.image_url"
                                    :alt="banner.title || 'Banner'"
                                    class="w-24 h-14 object-cover rounded-base border border-gray-200 bg-gray-100"
                                />
                            </td>

                            <!-- Información -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-dark">{{ banner.title || '(Sin título)' }}</div>
                                <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ banner.subtitle || '—' }}</div>
                            </td>

                            <!-- Botón y URL -->
                            <td class="py-3 px-4">
                                <div v-if="banner.button_text" class="text-xs">
                                    <span class="font-semibold text-dark">{{ banner.button_text }}</span>
                                    <span v-if="banner.button_url" class="block text-gray-400 truncate max-w-[140px]">{{ banner.button_url }}</span>
                                </div>
                                <span v-else class="text-xs text-gray-400">Sin botón</span>
                            </td>

                            <!-- Estado -->
                            <td class="py-3 px-4 text-center">
                                <span
                                    :class="banner.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
                                    class="inline-block px-2 py-0.5 rounded-full text-xs font-bold"
                                >
                                    {{ banner.is_active ? 'Activo' : 'Oculto' }}
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="openEditModal(banner)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition"
                                        title="Editar banner"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteBanner(banner)"
                                        class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition"
                                        title="Eliminar banner"
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

        <!-- Modal Crear / Editar -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60">
            <div class="bg-white rounded-base max-w-lg w-full p-6 space-y-5 shadow-xl">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-dark">
                        {{ editingBanner ? 'Editar Banner' : 'Subir Nuevo Banner' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-dark">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Selector de Imagen -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Imagen del Banner <span class="text-rose-500">*</span>
                        </label>
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            @change="handleImageChange"
                            class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-base file:border-0 file:text-xs file:font-bold file:bg-gray-100 hover:file:bg-gray-200"
                        />
                        <p v-if="form.errors.image" class="text-xs text-rose-600 mt-1">{{ form.errors.image }}</p>

                        <!-- Previsualización -->
                        <div v-if="previewUrl" class="mt-3">
                            <img :src="previewUrl" alt="Preview" class="w-full h-36 object-cover rounded-base border border-gray-200" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Título del Banner (Opcional)</label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Ej: Especialistas en Autopartes de Alta Gama"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Subtítulo / Bajada (Opcional)</label>
                            <input
                                v-model="form.subtitle"
                                type="text"
                                placeholder="Ej: Envíos garantizados a toda la República"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Texto del Botón</label>
                            <input
                                v-model="form.button_text"
                                type="text"
                                placeholder="Ej: Ver Catálogo"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Enlace del Botón (URL)</label>
                            <input
                                v-model="form.button_url"
                                type="text"
                                placeholder="Ej: /refacciones"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div class="flex items-center pt-2 md:col-span-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary focus:ring-primary"
                                />
                                <span class="text-sm font-bold text-dark">Banner Visible</span>
                            </label>
                        </div>
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
                            {{ form.processing ? 'Guardando...' : (editingBanner ? 'Actualizar Banner' : 'Crear Banner') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
