<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    mode: {
        type: String,
        default: 'create',
    },
    product: {
        type: Object,
        default: null,
    },
    options: {
        type: Object,
        required: true,
    },
});

const isEdit = props.mode === 'edit' && props.product !== null;

const initialPhotoUrls =
    props.product?.photo_urls?.length > 0
        ? [...props.product.photo_urls]
        : [''];

const form = useForm({
    _method: isEdit ? 'put' : 'post',
    sku: props.product?.sku ?? '',
    title: props.product?.title ?? '',
    slug: props.product?.slug ?? '',
    category: props.product?.category ?? (props.options.categories?.[0] || 'Motores'),
    vehicle_brand: props.product?.vehicle_brand ?? 'Nissan',
    vehicle_model: props.product?.vehicle_model ?? '',
    year_start: props.product?.year_start ?? 2016,
    year_end: props.product?.year_end ?? 2021,
    condition: props.product?.condition ?? 'A',
    price: props.product?.price ?? 0,
    stock: props.product?.stock ?? 1,
    description: props.product?.description ?? '',
    weight_kg: props.product?.weight_kg ?? '',
    height_cm: props.product?.height_cm ?? '',
    width_cm: props.product?.width_cm ?? '',
    length_cm: props.product?.length_cm ?? '',
    status: props.product?.status ?? 'published',
    is_featured: Boolean(props.product?.is_featured ?? false),
    photo_urls: initialPhotoUrls,
    photos: [],
});

const addPhotoUrlField = () => {
    if (form.photo_urls.length < 8) {
        form.photo_urls.push('');
    }
};

const removePhotoUrlField = (index) => {
    form.photo_urls.splice(index, 1);
    if (form.photo_urls.length === 0) {
        form.photo_urls.push('');
    }
};

const handleFileUpload = (event) => {
    form.photos = Array.from(event.target.files || []);
};

const submit = () => {
    if (isEdit) {
        form.post(route('admin.products.update', props.product.id), {
            forceFormData: true,
        });
    } else {
        form.post(route('admin.products.store'), {
            forceFormData: true,
        });
    }
};
</script>

<template>
    <Head
        :title="
            isEdit
                ? `Editar ${product.title} — Administración JC Autopartes`
                : 'Nuevo Artículo — Administración JC Autopartes'
        "
    />

    <AdminLayout
        :title="isEdit ? `Editar Artículo: ${product.sku}` : 'Registrar Nuevo Artículo'"
        :subtitle="
            isEdit
                ? 'Actualiza datos generales, compatibilidad, fotografías, precio y existencia'
                : 'Alta de refacción en el catálogo central'
        "
    >
        <template #actions>
            <Link
                :href="route('admin.products.index')"
                class="px-4 py-2 rounded-base border border-border bg-white hover:bg-surface text-xs sm:text-sm font-bold text-dark transition-colors"
            >
                &larr; Volver al listado
            </Link>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Columna Principal (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 1. Datos Generales -->
                    <section class="bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-extrabold uppercase tracking-wider text-dark border-b border-border pb-2">
                            1. Datos Generales de la Refacción
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label for="title" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Título de la Pieza *
                                </label>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    required
                                    placeholder="Ej. Motor 1.6L HR16DE Completo Nissan Versa 2016-2019"
                                    class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                                <InputError class="mt-1" :message="form.errors.title" />
                            </div>

                            <div>
                                <label for="sku" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    SKU / Código *
                                </label>
                                <input
                                    id="sku"
                                    v-model="form.sku"
                                    type="text"
                                    required
                                    placeholder="JC-MOT-099"
                                    class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-sm font-mono text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                                <InputError class="mt-1" :message="form.errors.sku" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="category" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Categoría *
                                </label>
                                <select
                                    id="category"
                                    v-model="form.category"
                                    required
                                    class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                >
                                    <option v-for="cat in options.categories" :key="cat" :value="cat">
                                        {{ cat }}
                                    </option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.category" />
                            </div>

                            <div>
                                <label for="condition" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Condición Física *
                                </label>
                                <select
                                    id="condition"
                                    v-model="form.condition"
                                    required
                                    class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                >
                                    <option
                                        v-for="cond in options.conditions"
                                        :key="cond.value"
                                        :value="cond.value"
                                    >
                                        {{ cond.label }}
                                    </option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.condition" />
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                Descripción Técnica y Observaciones
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                placeholder="Detalla el estado de la pieza, kilometraje aproximado, números de parte OEM y compatibilidades adicionales..."
                                class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            ></textarea>
                            <InputError class="mt-1" :message="form.errors.description" />
                        </div>
                    </section>

                    <!-- 2. Vehículo Compatible -->
                    <section class="bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-extrabold uppercase tracking-wider text-dark border-b border-border pb-2">
                            2. Vehículo Compatible
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div>
                                <label for="vehicle_brand" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Marca *
                                </label>
                                <input
                                    id="vehicle_brand"
                                    v-model="form.vehicle_brand"
                                    type="text"
                                    list="brand-suggestions"
                                    required
                                    placeholder="Ej. Nissan"
                                    class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                                <datalist id="brand-suggestions">
                                    <option v-for="b in options.brands" :key="b" :value="b" />
                                </datalist>
                                <InputError class="mt-1" :message="form.errors.vehicle_brand" />
                            </div>

                            <div>
                                <label for="vehicle_model" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Modelo *
                                </label>
                                <input
                                    id="vehicle_model"
                                    v-model="form.vehicle_model"
                                    type="text"
                                    required
                                    placeholder="Ej. Versa"
                                    class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                                <InputError class="mt-1" :message="form.errors.vehicle_model" />
                            </div>

                            <div>
                                <label for="year_start" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Año Inicial *
                                </label>
                                <input
                                    id="year_start"
                                    v-model.number="form.year_start"
                                    type="number"
                                    min="1980"
                                    max="2035"
                                    required
                                    class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                                <InputError class="mt-1" :message="form.errors.year_start" />
                            </div>

                            <div>
                                <label for="year_end" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Año Final *
                                </label>
                                <input
                                    id="year_end"
                                    v-model.number="form.year_end"
                                    type="number"
                                    min="1980"
                                    max="2035"
                                    required
                                    class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                                <InputError class="mt-1" :message="form.errors.year_end" />
                            </div>
                        </div>
                    </section>

                    <!-- 3. Fotografías del Artículo -->
                    <section class="bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-border pb-2">
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-dark">
                                3. Fotografías del Artículo
                            </h2>
                            <button
                                type="button"
                                @click="addPhotoUrlField"
                                class="text-xs font-bold text-primary hover:underline"
                            >
                                ＋ Agregar otra URL de imagen
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            <p class="text-xs text-gray-500">
                                Puedes indicar URLs de imágenes (la primera será la foto principal de portada) o subir archivos desde tu equipo:
                            </p>
                            <div
                                v-for="(url, idx) in form.photo_urls"
                                :key="idx"
                                class="flex items-center gap-2"
                            >
                                <input
                                    v-model="form.photo_urls[idx]"
                                    type="text"
                                    placeholder="https://images.unsplash.com/... o ruta local imagenes/..."
                                    class="flex-1 rounded-base border border-border bg-surface px-3.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                                <button
                                    type="button"
                                    @click="removePhotoUrlField(idx)"
                                    class="px-2.5 py-2 rounded-base border border-border text-xs font-bold text-gray-600 hover:text-red-600 hover:bg-surface"
                                    title="Quitar fila"
                                >
                                    ✕
                                </button>
                            </div>
                            <InputError class="mt-1" :message="form.errors.photo_urls" />
                        </div>

                        <div class="pt-3 border-t border-border">
                            <label for="photos_upload" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                Subir Archivos de Imagen (Opcional, JPG/PNG/WebP máx. 4MB)
                            </label>
                            <input
                                id="photos_upload"
                                type="file"
                                multiple
                                accept="image/*"
                                @change="handleFileUpload"
                                class="block w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-3.5 file:rounded-base file:border-0 file:text-xs file:font-bold file:bg-dark file:text-white hover:file:bg-black"
                            />
                            <InputError class="mt-1" :message="form.errors.photos" />
                        </div>
                    </section>
                </div>

                <!-- Columna Derecha (1/3): Precio, Stock, Estado y Dimensiones -->
                <div class="space-y-6">
                    <!-- Precio y Existencias (PriceService & StockService) -->
                    <section class="bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-extrabold uppercase tracking-wider text-dark border-b border-border pb-2">
                            Precio y Existencia
                        </h2>

                        <div>
                            <label for="price" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                Precio Unitario (MXN) *
                            </label>
                            <input
                                id="price"
                                v-model.number="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-base font-extrabold text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            />
                            <InputError class="mt-1" :message="form.errors.price" />
                        </div>

                        <div>
                            <label for="stock" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                Existencia Disponible (Unidades) *
                            </label>
                            <input
                                id="stock"
                                v-model.number="form.stock"
                                type="number"
                                step="1"
                                min="0"
                                required
                                class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-base font-extrabold text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            />
                            <InputError class="mt-1" :message="form.errors.stock" />
                        </div>
                    </section>

                    <!-- Estado de Publicación -->
                    <section class="bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-extrabold uppercase tracking-wider text-dark border-b border-border pb-2">
                            Publicación en Tienda
                        </h2>

                        <div>
                            <label for="status" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                Estado *
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="w-full rounded-base border border-border bg-surface px-3.5 py-2 text-sm font-semibold text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            >
                                <option
                                    v-for="st in options.statuses"
                                    :key="st.value"
                                    :value="st.value"
                                >
                                    {{ st.label }}
                                </option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.status" />
                        </div>

                        <label class="flex items-center gap-2.5 cursor-pointer pt-1">
                            <input
                                v-model="form.is_featured"
                                type="checkbox"
                                class="rounded border-border text-primary focus:ring-primary"
                            />
                            <span class="text-xs font-bold text-dark">
                                Mostrar en «Piezas Destacadas» de la portada
                            </span>
                        </label>
                    </section>

                    <!-- Peso y Dimensiones para Paquetería -->
                    <section class="bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-extrabold uppercase tracking-wider text-dark border-b border-border pb-2">
                            Peso y Dimensiones (Opcional)
                        </h2>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="weight_kg" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Peso (kg)
                                </label>
                                <input
                                    id="weight_kg"
                                    v-model="form.weight_kg"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="Ej. 45.0"
                                    class="w-full rounded-base border border-border bg-surface px-3 py-1.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label for="length_cm" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Largo (cm)
                                </label>
                                <input
                                    id="length_cm"
                                    v-model="form.length_cm"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    placeholder="Ej. 70"
                                    class="w-full rounded-base border border-border bg-surface px-3 py-1.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label for="width_cm" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Ancho (cm)
                                </label>
                                <input
                                    id="width_cm"
                                    v-model="form.width_cm"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    placeholder="Ej. 50"
                                    class="w-full rounded-base border border-border bg-surface px-3 py-1.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label for="height_cm" class="block text-xs font-bold uppercase text-gray-700 mb-1">
                                    Alto (cm)
                                </label>
                                <input
                                    id="height_cm"
                                    v-model="form.height_cm"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    placeholder="Ej. 60"
                                    class="w-full rounded-base border border-border bg-surface px-3 py-1.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Botón de Guardado -->
                    <div class="flex flex-col gap-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-primary hover:bg-primary-hover text-white font-extrabold py-3 px-4 rounded-base text-sm transition-colors shadow-sm disabled:opacity-50"
                        >
                            {{
                                form.processing
                                    ? 'Guardando artículo...'
                                    : isEdit
                                      ? 'Guardar Cambios del Artículo'
                                      : 'Crear y Guardar Artículo'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
