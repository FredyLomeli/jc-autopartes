<script setup>
import { reactive, ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    items: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ scope: 'suspect', category: '', q: '' }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const scope = ref(props.filters.scope || 'suspect');
const category = ref(props.filters.category || '');
const search = ref(props.filters.q || '');
const savingId = ref(null);
const drafts = reactive({});

const syncDraftsFromProps = () => {
    for (const row of props.items?.data || []) {
        drafts[row.id] = {
            weight_kg: row.weight_kg,
            length_cm: row.length_cm,
            width_cm: row.width_cm,
            height_cm: row.height_cm,
        };
    }
};

syncDraftsFromProps();
watch(() => props.items, syncDraftsFromProps, { deep: true });

const applyFilters = (nextScope = scope.value) => {
    scope.value = nextScope;
    router.get(
        route('admin.products.dimensions-audit'),
        {
            scope: scope.value || 'suspect',
            category: category.value || undefined,
            q: search.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    scope.value = 'suspect';
    category.value = '';
    search.value = '';
    router.get(route('admin.products.dimensions-audit'));
};

const saveRow = (row) => {
    const draft = drafts[row.id];
    if (!draft) return;

    savingId.value = row.id;
    router.patch(
        route('admin.products.dimensions.update', row.id),
        {
            weight_kg: draft.weight_kg,
            length_cm: draft.length_cm,
            width_cm: draft.width_cm,
            height_cm: draft.height_cm,
            dimensions_locked: true,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                savingId.value = null;
            },
        },
    );
};

const exportCsvUrl = () => {
    const params = new URLSearchParams();
    if (scope.value) params.set('scope', scope.value);
    if (category.value) params.set('category', category.value);
    if (search.value) params.set('q', search.value);
    const qs = params.toString();

    return route('admin.products.dimensions-audit.export') + (qs ? `?${qs}` : '');
};

const scopeTabs = [
    { key: 'suspect', label: 'Por revisar (Sospechosos)' },
    { key: 'incomplete', label: 'Sin medidas en ML' },
    { key: 'heavy_low', label: 'Motores / Cajas bajos' },
    { key: 'locked', label: 'Verificados vs ML' },
    { key: 'all', label: 'Todos' },
];
</script>

<template>
    <Head title="Auditoría de Pesos y Medidas — JC Autopartes" />

    <AdminLayout
        title="Auditoría de Pesos y Medidas"
        subtitle="Revisión de dimensiones reales para cotización precisa con Paquetexpress"
    >
        <template #actions>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    :href="exportCsvUrl()"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-2 rounded-base text-xs sm:text-sm transition-colors shadow-xs inline-flex items-center gap-1.5"
                >
                    <span>Descargar CSV (Excel)</span>
                </a>
                <Link
                    :href="route('admin.products.index')"
                    class="bg-white hover:bg-gray-50 text-dark border border-border font-bold px-3.5 py-2 rounded-base text-xs sm:text-sm transition-colors"
                >
                    &larr; Volver a Artículos
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tarjetas KPI -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-card border border-border p-4 shadow-xs">
                    <span class="block text-xs font-bold uppercase text-gray-500">Pendientes por Revisar</span>
                    <strong class="block text-2xl font-black text-primary mt-1">{{ stats.suspect_count }}</strong>
                    <span class="text-xs text-gray-500">Sin verificar con alerta de ML o peso</span>
                </div>
                <div class="bg-white rounded-card border border-border p-4 shadow-xs">
                    <span class="block text-xs font-bold uppercase text-gray-500">Sin Datos en ML</span>
                    <strong class="block text-2xl font-black text-amber-600 mt-1">{{ stats.incomplete_count }}</strong>
                    <span class="text-xs text-gray-500">Vinieron sin SELLER_PACKAGE_* en ML</span>
                </div>
                <div class="bg-white rounded-card border border-border p-4 shadow-xs">
                    <span class="block text-xs font-bold uppercase text-gray-500">Motores &lt;50 kg / Cajas &lt;20 kg</span>
                    <strong class="block text-2xl font-black text-dark mt-1">
                        {{ stats.motors_suspect_count }} / {{ stats.transmissions_suspect_count }}
                    </strong>
                    <span class="text-xs text-gray-500">Motores y transmisiones con peso bajo</span>
                </div>
                <div class="bg-white rounded-card border border-border p-4 shadow-xs">
                    <span class="block text-xs font-bold uppercase text-gray-500">Protegidos vs. ML</span>
                    <strong class="block text-2xl font-black text-emerald-700 mt-1">{{ stats.locked_count }}</strong>
                    <span class="text-xs text-gray-500">De {{ stats.total }} artículos totales</span>
                </div>
            </div>

            <!-- Tabs + Filtros -->
            <div class="bg-white rounded-card border border-border p-4 shadow-xs space-y-4">
                <div class="flex flex-wrap gap-2 border-b border-border pb-3">
                    <button
                        v-for="tab in scopeTabs"
                        :key="tab.key"
                        type="button"
                        @click="applyFilters(tab.key)"
                        :class="scope === tab.key
                            ? 'bg-dark text-white border-dark'
                            : 'bg-surface text-gray-700 border-border hover:border-dark'"
                        class="px-3 py-1.5 rounded-base border text-xs font-bold transition-colors"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <form @submit.prevent="applyFilters(scope)" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-5">
                        <label for="audit-search" class="block text-xs font-bold uppercase text-gray-600 mb-1">
                            Buscar SKU, título o vehículo
                        </label>
                        <input
                            id="audit-search"
                            v-model="search"
                            type="search"
                            placeholder="Ej. Motor, MLM2300322269..."
                            class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        />
                    </div>
                    <div class="sm:col-span-4">
                        <label for="audit-category" class="block text-xs font-bold uppercase text-gray-600 mb-1">
                            Categoría
                        </label>
                        <select
                            id="audit-category"
                            v-model="category"
                            class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Todas las categorías</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3 flex gap-2">
                        <button
                            type="submit"
                            class="flex-1 bg-dark hover:bg-black text-white font-bold px-3 py-2 rounded-base text-xs uppercase tracking-wider"
                        >
                            Filtrar
                        </button>
                        <button
                            type="button"
                            @click="clearFilters"
                            class="px-3 py-2 rounded-base border border-border text-xs font-bold text-gray-600 hover:text-dark"
                        >
                            Limpiar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla editable inline -->
            <div class="bg-white rounded-card border border-border shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-surface text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-extrabold">Artículo / SKU</th>
                                <th class="px-3 py-3 text-left font-extrabold">Hallazgo</th>
                                <th class="px-2 py-3 text-left font-extrabold w-24">Peso (kg)</th>
                                <th class="px-2 py-3 text-left font-extrabold w-24">Largo (cm)</th>
                                <th class="px-2 py-3 text-left font-extrabold w-24">Ancho (cm)</th>
                                <th class="px-2 py-3 text-left font-extrabold w-24">Alto (cm)</th>
                                <th class="px-4 py-3 text-right font-extrabold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-if="!items.data || items.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                    No hay artículos que coincidan con este filtro.
                                </td>
                            </tr>
                            <tr v-for="row in items.data" :key="row.id" class="hover:bg-surface/40">
                                <td class="px-4 py-3 align-top max-w-sm">
                                    <div class="flex flex-wrap items-center gap-1.5 text-[11px] mb-1">
                                        <span class="font-mono font-bold bg-surface px-1.5 py-0.5 rounded border border-border text-dark">
                                            {{ row.sku }}
                                        </span>
                                        <span class="text-primary font-bold uppercase">{{ row.category }}</span>
                                        <a
                                            v-if="row.mercadolibre_permalink"
                                            :href="row.mercadolibre_permalink"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="bg-yellow-100 text-yellow-900 border border-yellow-300 px-1.5 py-0.5 rounded font-bold hover:bg-yellow-200"
                                        >
                                            ML ↗
                                        </a>
                                    </div>
                                    <p class="font-bold text-dark text-xs leading-snug line-clamp-2">{{ row.title }}</p>
                                </td>

                                <td class="px-3 py-3 align-middle">
                                    <span
                                        :class="{
                                            'bg-red-100 text-red-800': row.severity === 'critical',
                                            'bg-amber-100 text-amber-900': row.severity === 'warning',
                                            'bg-emerald-100 text-emerald-800': row.severity === 'locked',
                                            'bg-gray-100 text-gray-700': row.severity === 'ok',
                                        }"
                                        class="inline-block px-2 py-1 rounded text-[11px] font-bold"
                                    >
                                        {{ row.audit_reason }}
                                    </span>
                                </td>

                                <td class="px-2 py-3 align-middle">
                                    <input
                                        v-model="drafts[row.id].weight_kg"
                                        type="number"
                                        step="0.01"
                                        min="0.1"
                                        class="w-24 rounded-base border border-border bg-surface px-2 py-1.5 text-xs font-mono text-dark focus:border-primary focus:bg-white"
                                    />
                                </td>
                                <td class="px-2 py-3 align-middle">
                                    <input
                                        v-model="drafts[row.id].length_cm"
                                        type="number"
                                        step="0.1"
                                        min="1"
                                        class="w-20 rounded-base border border-border bg-surface px-2 py-1.5 text-xs font-mono text-dark focus:border-primary focus:bg-white"
                                    />
                                </td>
                                <td class="px-2 py-3 align-middle">
                                    <input
                                        v-model="drafts[row.id].width_cm"
                                        type="number"
                                        step="0.1"
                                        min="1"
                                        class="w-20 rounded-base border border-border bg-surface px-2 py-1.5 text-xs font-mono text-dark focus:border-primary focus:bg-white"
                                    />
                                </td>
                                <td class="px-2 py-3 align-middle">
                                    <input
                                        v-model="drafts[row.id].height_cm"
                                        type="number"
                                        step="0.1"
                                        min="1"
                                        class="w-20 rounded-base border border-border bg-surface px-2 py-1.5 text-xs font-mono text-dark focus:border-primary focus:bg-white"
                                    />
                                </td>

                                <td class="px-4 py-3 align-middle text-right whitespace-nowrap space-x-2">
                                    <button
                                        type="button"
                                        :disabled="savingId === row.id"
                                        @click="saveRow(row)"
                                        class="bg-primary hover:bg-primary-hover disabled:opacity-50 text-white font-bold px-3 py-1.5 rounded-base text-xs transition-colors"
                                    >
                                        {{ savingId === row.id ? 'Guardando...' : 'Guardar y proteger' }}
                                    </button>
                                    <Link
                                        :href="route('admin.products.edit', row.id)"
                                        class="text-xs font-bold text-gray-600 hover:text-dark underline"
                                    >
                                        Ficha
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="items.links && items.links.length > 3" class="px-4 py-3 border-t border-border flex flex-wrap items-center justify-between gap-2 text-xs">
                    <span class="text-gray-600">
                        Mostrando {{ items.from ?? 0 }}–{{ items.to ?? 0 }} de {{ items.total ?? 0 }}
                    </span>
                    <div class="flex flex-wrap gap-1">
                        <template v-for="(link, idx) in items.links" :key="idx">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="link.active ? 'bg-dark text-white border-dark' : 'bg-white text-dark border-border hover:bg-surface'"
                                class="px-2.5 py-1 rounded border font-bold"
                            />
                            <span v-else v-html="link.label" class="px-2.5 py-1 text-gray-400" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
