<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ q: '', category: '', status: '' }),
    },
    options: {
        type: Object,
        required: true,
    },
});

const search = ref(props.filters.q || '');
const category = ref(props.filters.category || '');
const status = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(
        route('admin.products.index'),
        {
            q: search.value || undefined,
            category: category.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    search.value = '';
    category.value = '';
    status.value = '';
    router.get(route('admin.products.index'));
};

const confirmDelete = (item) => {
    if (window.confirm(`¿Deseas eliminar la refacción "${item.title}" (SKU: ${item.sku})?`)) {
        router.delete(route('admin.products.destroy', item.id), {
            preserveScroll: true,
        });
    }
};

const isSyncing = ref(false);
const syncMercadoLibre = () => {
    if (isSyncing.value) return;
    if (window.confirm('¿Deseas sincronizar el catálogo con las publicaciones de Mercado Libre ahora?')) {
        isSyncing.value = true;
        router.post(
            route('admin.products.sync-meli'),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    isSyncing.value = false;
                },
            }
        );
    }
};
</script>

<template>
    <Head title="Artículos de Catálogo — Administración JC Autopartes" />

    <AdminLayout
        title="Catálogo de Artículos"
        subtitle="Gestión de refacciones, existencias, precios y estado de publicación"
    >
        <template #actions>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="syncMercadoLibre"
                    :disabled="isSyncing"
                    class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-extrabold px-3.5 py-2 rounded-base text-xs sm:text-sm transition-colors shadow-sm inline-flex items-center gap-1.5 disabled:opacity-50"
                    title="Importar y actualizar publicaciones de Mercado Libre"
                >
                    <svg class="w-4 h-4" :class="{ 'animate-spin': isSyncing }" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46A7.93 7.93 0 0020 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74A7.93 7.93 0 004 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
                    </svg>
                    <span>{{ isSyncing ? 'Sincronizando...' : 'Sincronizar ML' }}</span>
                </button>
                <Link
                    :href="route('admin.products.create')"
                    class="bg-primary hover:bg-primary-hover text-white font-extrabold px-4 py-2 rounded-base text-xs sm:text-sm transition-colors shadow-sm inline-flex items-center gap-1.5"
                >
                    <span>＋</span>
                    <span>Nuevo Artículo</span>
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Barra de Búsqueda y Filtros -->
            <form
                @submit.prevent="applyFilters"
                class="bg-white rounded-card border border-border p-4 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end"
            >
                <div class="lg:col-span-5">
                    <label for="admin-search" class="block text-xs font-bold uppercase text-gray-600 mb-1">
                        Buscar por SKU, nombre o vehículo
                    </label>
                    <input
                        id="admin-search"
                        v-model="search"
                        type="search"
                        placeholder="Ej. Motor Versa, JC-MOT-001, Nissan..."
                        class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                    />
                </div>

                <div class="lg:col-span-3">
                    <label for="admin-category" class="block text-xs font-bold uppercase text-gray-600 mb-1">
                        Categoría
                    </label>
                    <select
                        id="admin-category"
                        v-model="category"
                        @change="applyFilters"
                        class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Todas las categorías</option>
                        <option v-for="cat in options.categories" :key="cat" :value="cat">
                            {{ cat }}
                        </option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="admin-status" class="block text-xs font-bold uppercase text-gray-600 mb-1">
                        Estado
                    </label>
                    <select
                        id="admin-status"
                        v-model="status"
                        @change="applyFilters"
                        class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Todos</option>
                        <option value="published">Publicados</option>
                        <option value="draft">Borradores</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 bg-dark hover:bg-black text-white font-bold py-2 px-3 rounded-base text-xs uppercase tracking-wide transition-colors"
                    >
                        Filtrar
                    </button>
                    <button
                        v-if="search || category || status"
                        type="button"
                        @click="clearFilters"
                        class="px-3 py-2 rounded-base border border-border text-xs font-bold text-gray-600 hover:text-primary hover:bg-surface transition-colors"
                    >
                        Limpiar
                    </button>
                </div>
            </form>

            <!-- Tabla de Artículos -->
            <div class="bg-white rounded-card border border-border shadow-sm overflow-hidden">
                <div class="px-6 py-3.5 border-b border-border flex items-center justify-between bg-surface">
                    <span class="text-xs font-bold text-gray-600">
                        Mostrando {{ products.from || 0 }}–{{ products.to || 0 }} de {{ products.total }} artículo(s)
                    </span>
                </div>

                <div v-if="products.data.length === 0" class="p-10 text-center">
                    <p class="text-base font-extrabold text-dark">No se encontraron refacciones con esos filtros</p>
                    <p class="text-xs text-gray-500 mt-1">Prueba ajustando la búsqueda o registra un nuevo artículo.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-surface text-xs font-bold uppercase text-gray-600">
                            <tr>
                                <th class="px-5 py-3 text-left">Refacción / SKU</th>
                                <th class="px-5 py-3 text-left">Compatibilidad</th>
                                <th class="px-5 py-3 text-left">Condición</th>
                                <th class="px-5 py-3 text-left">Estado</th>
                                <th class="px-5 py-3 text-right">Existencia</th>
                                <th class="px-5 py-3 text-right">Precio</th>
                                <th class="px-5 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-white">
                            <tr v-for="item in products.data" :key="item.id" class="hover:bg-surface">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="item.primary_photo_url"
                                            :alt="item.title"
                                            width="48"
                                            height="40"
                                            loading="lazy"
                                            decoding="async"
                                            style="max-height: 2.5rem; width: 3rem;"
                                            class="w-12 h-10 rounded object-cover border border-border flex-shrink-0"
                                        />
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <Link
                                                    :href="route('admin.products.edit', item.id)"
                                                    class="font-bold text-dark hover:text-primary transition-colors truncate"
                                                >
                                                    {{ item.title }}
                                                </Link>
                                                <span
                                                    v-if="item.is_featured"
                                                    class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 text-[10px] font-extrabold uppercase shrink-0"
                                                >
                                                    Destacado
                                                </span>
                                            </div>
                                            <p class="text-xs font-mono text-gray-500 mt-0.5">
                                                SKU: {{ item.sku }} · {{ item.category }}
                                            </p>
                                            <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px]">
                                                <span
                                                    v-if="item.part_number"
                                                    class="text-gray-600 bg-gray-100 px-1.5 py-0.5 rounded font-mono"
                                                    title="Número de parte del fabricante"
                                                >
                                                    P/N: {{ item.part_number }}
                                                </span>
                                                <span
                                                    v-if="item.side_position_label"
                                                    class="text-primary-700 bg-red-50 border border-red-100 px-1.5 py-0.5 rounded font-medium"
                                                >
                                                    {{ item.side_position_label }}
                                                </span>
                                                <a
                                                    v-if="item.mercadolibre_permalink"
                                                    :href="item.mercadolibre_permalink"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-yellow-900 bg-yellow-100 hover:bg-yellow-200 border border-yellow-300 px-1.5 py-0.5 rounded transition-colors"
                                                    title="Ver publicación original en Mercado Libre"
                                                >
                                                    <span>ML</span>
                                                    <span class="text-[9px]">↗</span>
                                                </a>
                                                <span
                                                    v-else-if="item.mercadolibre_id"
                                                    class="inline-flex items-center gap-1 text-[11px] font-bold text-yellow-900 bg-yellow-50 border border-yellow-200 px-1.5 py-0.5 rounded"
                                                    title="ID de publicación Mercado Libre"
                                                >
                                                    ML: {{ item.mercadolibre_id }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-3.5 text-xs font-semibold text-gray-700 whitespace-nowrap">
                                    {{ item.vehicle_brand }} {{ item.vehicle_model }}
                                    <span class="block text-gray-500 font-normal">
                                        Años {{ item.year_start }}–{{ item.year_end }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded bg-dark text-white text-xs font-bold">
                                        Grado {{ item.condition }}
                                    </span>
                                    <span class="block text-[11px] text-gray-500 mt-0.5">
                                        Garantía {{ item.warranty_days }} días
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span
                                        :class="
                                            item.status === 'published'
                                                ? 'bg-green-100 text-green-800 border-green-300'
                                                : 'bg-gray-100 text-gray-700 border-gray-300'
                                        "
                                        class="px-2.5 py-0.5 rounded border text-xs font-bold"
                                    >
                                        {{ item.status === 'published' ? 'Publicado' : 'Borrador' }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <span
                                        :class="item.has_stock ? 'text-green-700 font-extrabold' : 'text-red-700 font-extrabold'"
                                    >
                                        {{ item.stock }} pza(s)
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 text-right font-extrabold text-dark whitespace-nowrap">
                                    {{ item.formatted_price }}
                                </td>

                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <a
                                            v-if="item.status === 'published'"
                                            :href="`/refacciones/${item.slug}`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="px-2.5 py-1 rounded border border-border text-xs font-bold text-gray-700 hover:bg-surface transition-colors"
                                            title="Ver ficha pública"
                                        >
                                            Ver ↗
                                        </a>
                                        <Link
                                            :href="route('admin.products.edit', item.id)"
                                            class="px-2.5 py-1 rounded bg-dark hover:bg-black text-white text-xs font-bold transition-colors"
                                        >
                                            Editar
                                        </Link>
                                        <button
                                            type="button"
                                            @click="confirmDelete(item)"
                                            class="px-2.5 py-1 rounded border border-red-200 text-xs font-bold text-red-700 hover:bg-red-50 transition-colors"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div
                    v-if="products.links && products.links.length > 3"
                    class="px-6 py-4 border-t border-border bg-surface flex flex-wrap items-center justify-between gap-2"
                >
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="(link, idx) in products.links" :key="idx">
                            <span
                                v-if="!link.url"
                                v-html="link.label"
                                class="px-3 py-1.5 rounded text-xs font-semibold text-gray-400 border border-transparent"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                v-html="link.label"
                                :class="
                                    link.active
                                        ? 'bg-primary text-white font-extrabold border-primary'
                                        : 'bg-white text-dark hover:bg-surface border-border font-semibold'
                                "
                                class="px-3 py-1.5 rounded text-xs border transition-colors"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
