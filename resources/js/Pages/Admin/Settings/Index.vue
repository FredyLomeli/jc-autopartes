<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    structured: {
        type: Object,
        default: () => ({}),
    },
    brandLogo: {
        type: Object,
        default: () => ({ is_custom: false, filename: 'logo.jpg', url: '/imagenes/brand/logo.jpg' }),
    },
    warrantyPdf: {
        type: Object,
        default: () => ({ exists: false, filename: null, url: null }),
    },
    privacyPdf: {
        type: Object,
        default: () => ({ exists: false, filename: null, url: null }),
    },
    pricingStats: {
        type: Object,
        default: () => ({ ml_synced_count: 0, discount_percentage: 10 }),
    },
    mercadolibreStatus: {
        type: Object,
        default: () => ({
            has_credentials: false,
            connected: false,
            user_id: null,
        }),
    },
});

const activeTab = ref('general');

const form = useForm({
    settings: { ...props.settings },
});

const submit = () => {
    form.put(route('admin.settings.update'), {
        preserveScroll: true,
    });
};

const legalDocs = [
    {
        key: 'warranty',
        title: 'Política de Garantía en PDF',
        description: 'Documento oficial mostrado como métrica general en /garantia.',
        inputName: 'warranty_pdf',
        uploadRoute: 'admin.settings.warranty-pdf.upload',
        destroyRoute: 'admin.settings.warranty-pdf.destroy',
        confirmDelete: '¿Eliminar el PDF de la Política de Garantía?',
        form: useForm({ warranty_pdf: null }),
    },
    {
        key: 'privacy',
        title: 'Aviso de Privacidad en PDF',
        description: 'Documento oficial mostrado embebido en /aviso-de-privacidad.',
        inputName: 'privacy_pdf',
        uploadRoute: 'admin.settings.privacy-pdf.upload',
        destroyRoute: 'admin.settings.privacy-pdf.destroy',
        confirmDelete: '¿Eliminar el PDF del Aviso de Privacidad? El sitio público volverá a mostrar solo el texto legal.',
        form: useForm({ privacy_pdf: null }),
    },
];

const pdfInputRefs = ref({});
const logoInputRef = ref(null);
const logoForm = useForm({ logo: null });

const onLogoSelected = (event) => {
    logoForm.logo = event.target.files[0] || null;
};

const uploadLogo = () => {
    logoForm.post(route('admin.settings.logo.upload'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            logoForm.reset();
            if (logoInputRef.value) {
                logoInputRef.value.value = '';
            }
        },
    });
};

const deleteLogo = () => {
    if (confirm('¿Restablecer el logotipo predeterminado del sitio público?')) {
        router.delete(route('admin.settings.logo.destroy'), {
            preserveScroll: true,
        });
    }
};

const getPdfMeta = (key) => (key === 'warranty' ? props.warrantyPdf : props.privacyPdf);

const onLegalPdfSelected = (doc, event) => {
    doc.form[doc.inputName] = event.target.files[0] || null;
};

const uploadLegalPdf = (doc) => {
    doc.form.post(route(doc.uploadRoute), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            doc.form.reset();
            const el = pdfInputRefs.value[doc.key];
            if (el) {
                el.value = '';
            }
        },
    });
};

const deleteLegalPdf = (doc) => {
    if (confirm(doc.confirmDelete)) {
        router.delete(route(doc.destroyRoute), {
            preserveScroll: true,
        });
    }
};

const disconnectMercadoLibre = () => {
    if (confirm('¿Desvincular la cuenta de Mercado Libre? Dejará de sincronizar hasta volver a autorizar.')) {
        router.delete(route('admin.mercadolibre.disconnect'), {
            preserveScroll: true,
        });
    }
};

const recalculateForm = useForm({
    discount_percentage: props.settings?.ml_web_discount_percentage ?? props.pricingStats?.discount_percentage ?? 10,
});

const recalculateWebPrices = () => {
    const pct = Number(form.settings.ml_web_discount_percentage ?? recalculateForm.discount_percentage ?? 10);
    if (!confirm(`¿Recalcular los precios web sin IVA de todos los artículos sincronizados usando ${pct}% de descuento vs. Mercado Libre?`)) {
        return;
    }

    recalculateForm.discount_percentage = pct;
    recalculateForm.post(route('admin.settings.recalculate-web-prices'), {
        preserveScroll: true,
    });
};

const previewWebPrice = () => {
    const pct = Math.max(0, Math.min(90, Number(form.settings.ml_web_discount_percentage ?? 10) || 0));
    const web = (1000 / 1.16) * (1 - pct / 100);
    return web.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <AdminLayout>
        <Head title="Ajustes de Empresa — JC Autopartes" />

        <div class="space-y-6">
            <!-- Encabezado -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-dark tracking-tight">
                        Ajustes e Información de Empresa
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Controla los datos oficiales, teléfonos, WhatsApps, horarios y textos corporativos visibles en el sitio público.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-dark font-bold text-sm rounded-base shadow-sm transition disabled:opacity-50 flex items-center gap-2"
                    >
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-dark" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}</span>
                    </button>
                </div>
            </div>

            <!-- Notificación de éxito -->
            <div
                v-if="form.recentlySuccessful"
                class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-base text-sm font-medium flex items-center gap-2"
            >
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>¡Los cambios se guardaron con éxito en la base de datos!</span>
            </div>

            <!-- Pestañas de navegación -->
            <div class="border-b border-gray-200 flex gap-2">
                <button
                    type="button"
                    @click="activeTab = 'general'"
                    :class="activeTab === 'general' ? 'border-primary text-dark font-extrabold' : 'border-transparent text-gray-500 hover:text-dark hover:border-gray-300 font-medium'"
                    class="py-3 px-4 text-sm border-b-2 transition"
                >
                    Identidad y Garantía
                </button>
                <button
                    type="button"
                    @click="activeTab = 'contact'"
                    :class="activeTab === 'contact' ? 'border-primary text-dark font-extrabold' : 'border-transparent text-gray-500 hover:text-dark hover:border-gray-300 font-medium'"
                    class="py-3 px-4 text-sm border-b-2 transition"
                >
                    Contacto, Ubicación y Horarios
                </button>
                <button
                    type="button"
                    @click="activeTab = 'social'"
                    :class="activeTab === 'social' ? 'border-primary text-dark font-extrabold' : 'border-transparent text-gray-500 hover:text-dark hover:border-gray-300 font-medium'"
                    class="py-3 px-4 text-sm border-b-2 transition"
                >
                    Redes y Mercado Libre
                </button>
                <button
                    type="button"
                    @click="activeTab = 'philosophy'"
                    :class="activeTab === 'philosophy' ? 'border-primary text-dark font-extrabold' : 'border-transparent text-gray-500 hover:text-dark hover:border-gray-300 font-medium'"
                    class="py-3 px-4 text-sm border-b-2 transition"
                >
                    Filosofía Corporativa 2026
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- TAB 1: IDENTIDAD -->
                <div v-show="activeTab === 'general'" class="bg-white rounded-base border border-gray-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-dark border-b pb-2">Información de Marca</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nombre Comercial</label>
                            <input
                                v-model="form.settings.company_name"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Subtítulo / Distintivo</label>
                            <input
                                v-model="form.settings.company_badge"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Slogan Institucional</label>
                            <input
                                v-model="form.settings.company_slogan"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Cintillo Global de Garantía (Pie de página)</label>
                            <textarea
                                v-model="form.settings.guarantee_banner_text"
                                rows="2"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            ></textarea>
                            <p class="text-xs text-gray-500 mt-1">Este texto aparece destacado al final de cada página antes del footer.</p>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: CONTACTO Y HORARIOS -->
                <div v-show="activeTab === 'contact'" class="bg-white rounded-base border border-gray-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-dark border-b pb-2">Matriz, Teléfonos y Ubicación</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Sucursal / Nombre del local</label>
                            <input
                                v-model="form.settings.contact_branch"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Teléfono Fijo de Oficina</label>
                            <input
                                v-model="form.settings.contact_phone_office"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Calle y Número</label>
                            <input
                                v-model="form.settings.contact_address"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Colonia y C.P.</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input
                                    v-model="form.settings.contact_colony"
                                    type="text"
                                    placeholder="Colonia"
                                    class="rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                                />
                                <input
                                    v-model="form.settings.contact_zip"
                                    type="text"
                                    placeholder="C.P."
                                    class="rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ciudad y Estado</label>
                            <input
                                v-model="form.settings.contact_city"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Correo Electrónico Oficial</label>
                            <input
                                v-model="form.settings.contact_email"
                                type="email"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">WhatsApp Ventas Línea 1</label>
                            <input
                                v-model="form.settings.contact_whatsapp_1"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Enlace Directo WhatsApp Línea 1</label>
                            <input
                                v-model="form.settings.contact_whatsapp_1_url"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">WhatsApp Ventas Línea 2</label>
                            <input
                                v-model="form.settings.contact_whatsapp_2"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Enlace Directo WhatsApp Línea 2</label>
                            <input
                                v-model="form.settings.contact_whatsapp_2_url"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Horario Lun-Vie</label>
                            <input
                                v-model="form.settings.schedule_weekday"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Horario Sábados</label>
                            <input
                                v-model="form.settings.schedule_saturday"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Enlace Compartir Google Maps</label>
                            <input
                                v-model="form.settings.contact_maps_url"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">URL Embebida (iframe) de Google Maps</label>
                            <input
                                v-model="form.settings.contact_maps_embed"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>
                    </div>
                </div>

                <!-- TAB 3: REDES Y MERCADO LIBRE -->
                <div v-show="activeTab === 'social'" class="bg-white rounded-base border border-gray-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-dark border-b pb-2">Canales Externos y Tiendas</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tienda Oficial Mercado Libre</label>
                            <input
                                v-model="form.settings.social_mercadolibre"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Enlace a Facebook</label>
                            <input
                                v-model="form.settings.social_facebook"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Enlace a Instagram</label>
                            <input
                                v-model="form.settings.social_instagram"
                                type="text"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            />
                        </div>
                    </div>
                </div>

                <!-- TAB 4: FILOSOFÍA 2026 -->
                <div v-show="activeTab === 'philosophy'" class="bg-white rounded-base border border-gray-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-dark border-b pb-2">Filosofía Corporativa</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nuestra Misión</label>
                            <textarea
                                v-model="form.settings.company_mission"
                                rows="3"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nuestra Visión</label>
                            <textarea
                                v-model="form.settings.company_vision"
                                rows="3"
                                class="w-full rounded-base border-gray-300 text-sm focus:border-primary focus:ring-primary"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Valores Institucionales (JSON)</label>
                            <textarea
                                v-model="form.settings.company_values"
                                rows="6"
                                class="font-mono text-xs w-full rounded-base border-gray-300 focus:border-primary focus:ring-primary"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Botón inferior para guardar -->
                <div class="flex justify-end pt-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-primary hover:bg-primary-dark text-dark font-black text-sm rounded-base shadow-sm transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Guardando...' : 'Guardar Todos los Cambios' }}
                    </button>
                </div>
            </form>

            <!-- LOGOTIPO OFICIAL DE LA BARRA SUPERIOR -->
            <div v-show="activeTab === 'general'" class="bg-white rounded-base border border-gray-200 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-3">
                    <div>
                        <h2 class="text-base font-bold text-dark">Logotipo Oficial (Barra Superior)</h2>
                        <p class="text-xs text-gray-500">
                            Se muestra en el encabezado oscuro del sitio público. Se recomienda imagen con fondo oscuro o transparente (JPG, PNG, WEBP o SVG).
                        </p>
                    </div>
                    <span
                        :class="brandLogo.is_custom ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700'"
                        class="px-2.5 py-1 rounded-full text-xs font-bold self-start sm:self-auto"
                    >
                        {{ brandLogo.is_custom ? 'Logotipo Personalizado' : 'Predeterminado' }}
                    </span>
                </div>

                <div
                    v-if="logoForm.recentlySuccessful"
                    class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-base text-xs font-semibold"
                >
                    El logotipo oficial se actualizó correctamente.
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-dark rounded-base border border-gray-800">
                    <div class="flex items-center gap-4">
                        <img
                            :src="brandLogo.url"
                            alt="Vista previa logotipo JC Autopartes"
                            class="h-20 w-auto object-contain rounded-base bg-dark"
                        />
                        <div class="text-xs text-gray-300 space-y-0.5">
                            <span class="block font-bold text-white">Vista previa sobre barra superior oscura</span>
                            <span class="font-mono text-gray-400">{{ brandLogo.filename }}</span>
                        </div>
                    </div>

                    <button
                        v-if="brandLogo.is_custom"
                        type="button"
                        @click="deleteLogo"
                        class="px-3.5 py-2 rounded-base border border-rose-300/30 bg-rose-500/20 hover:bg-rose-500/30 text-xs font-bold text-rose-100 transition self-start sm:self-center"
                    >
                        Restablecer Predeterminado
                    </button>
                </div>

                <form @submit.prevent="uploadLogo" class="flex flex-col sm:flex-row sm:items-end gap-3 pt-1">
                    <div class="flex-1">
                        <label for="company-logo-input" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Subir nuevo logotipo (máx. 4 MB)
                        </label>
                        <input
                            id="company-logo-input"
                            ref="logoInputRef"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/svg+xml,.jpg,.jpeg,.png,.webp,.svg"
                            @change="onLogoSelected"
                            class="block w-full text-xs text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-base file:border-0 file:text-xs file:font-bold file:bg-gray-100 file:text-dark hover:file:bg-gray-200 border border-gray-300 rounded-base bg-white"
                            required
                        />
                        <p v-if="logoForm.errors.logo" class="text-xs text-red-600 font-semibold mt-1">
                            {{ logoForm.errors.logo }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="logoForm.processing || !logoForm.logo"
                        class="px-5 py-2.5 bg-dark hover:bg-gray-800 text-white font-bold text-xs uppercase tracking-wider rounded-base transition disabled:opacity-50 shrink-0"
                    >
                        {{ logoForm.processing ? 'Subiendo...' : 'Subir Logotipo' }}
                    </button>
                </form>
            </div>

            <!-- DOCUMENTOS LEGALES EN PDF (GARANTÍA Y PRIVACIDAD) -->
            <div v-show="activeTab === 'general'" class="space-y-4">
                <div
                    v-for="doc in legalDocs"
                    :key="doc.key"
                    class="bg-white rounded-base border border-gray-200 p-6 shadow-sm space-y-4"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-3">
                        <div>
                            <h2 class="text-base font-bold text-dark">{{ doc.title }}</h2>
                            <p class="text-xs text-gray-500">
                                {{ doc.description }} Formato <code class="font-mono">.pdf</code> (máximo 10 MB).
                            </p>
                        </div>
                        <span
                            :class="getPdfMeta(doc.key).exists ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
                            class="px-2.5 py-1 rounded-full text-xs font-bold self-start sm:self-auto"
                        >
                            {{ getPdfMeta(doc.key).exists ? 'PDF Publicado' : 'Sin PDF cargado' }}
                        </span>
                    </div>

                    <div
                        v-if="doc.form.recentlySuccessful"
                        class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-base text-xs font-semibold"
                    >
                        El archivo PDF se actualizó y publicó correctamente.
                    </div>

                    <div
                        v-if="getPdfMeta(doc.key).exists"
                        class="p-4 bg-gray-50 border border-gray-200 rounded-base flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div class="text-xs space-y-0.5">
                            <span class="block font-bold text-dark">Archivo activo en el sitio público:</span>
                            <span class="font-mono text-gray-600">{{ getPdfMeta(doc.key).filename }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <a
                                :href="getPdfMeta(doc.key).url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="px-3.5 py-2 rounded-base border border-gray-300 bg-white hover:bg-gray-100 text-xs font-bold text-dark transition"
                            >
                                Ver PDF &rarr;
                            </a>
                            <button
                                type="button"
                                @click="deleteLegalPdf(doc)"
                                class="px-3.5 py-2 rounded-base border border-rose-200 bg-rose-50 hover:bg-rose-100 text-xs font-bold text-rose-700 transition"
                            >
                                Eliminar PDF
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="uploadLegalPdf(doc)" class="flex flex-col sm:flex-row sm:items-end gap-3 pt-1">
                        <div class="flex-1">
                            <label :for="`legal-pdf-${doc.key}`" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                {{ getPdfMeta(doc.key).exists ? 'Reemplazar archivo PDF' : 'Seleccionar archivo PDF' }}
                            </label>
                            <input
                                :id="`legal-pdf-${doc.key}`"
                                :ref="(el) => { if (el) pdfInputRefs[doc.key] = el }"
                                type="file"
                                accept="application/pdf,.pdf"
                                @change="onLegalPdfSelected(doc, $event)"
                                class="block w-full text-xs text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-base file:border-0 file:text-xs file:font-bold file:bg-gray-100 file:text-dark hover:file:bg-gray-200 border border-gray-300 rounded-base bg-white"
                                required
                            />
                            <p v-if="doc.form.errors[doc.inputName]" class="text-xs text-red-600 font-semibold mt-1">
                                {{ doc.form.errors[doc.inputName] }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="doc.form.processing || !doc.form[doc.inputName]"
                            class="px-5 py-2.5 bg-dark hover:bg-gray-800 text-white font-bold text-xs uppercase tracking-wider rounded-base transition disabled:opacity-50 shrink-0"
                        >
                            {{ doc.form.processing ? 'Subiendo...' : 'Subir PDF' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- REGLA DE PRECIOS WEB VS MERCADO LIBRE (SIN IVA) -->
            <div v-show="activeTab === 'social'" class="bg-white rounded-base border border-gray-200 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-3">
                    <div>
                        <h2 class="text-base font-bold text-dark">Regla de Precios Web vs. Mercado Libre (Sin IVA)</h2>
                        <p class="text-xs text-gray-500">
                            Fórmula aplicada a artículos importados de ML: <code class="font-mono bg-surface px-1 py-0.5 rounded">(Precio ML ÷ 1.16) × (1 - % descuento)</code>. Los precios manuales sin ID de ML no se alteran.
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-surface border border-border text-xs font-bold text-dark self-start sm:self-auto">
                        {{ pricingStats.ml_synced_count }} artículos con precio ML
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-4">
                        <label for="ml-discount-pct" class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Descuento Web vs. ML (%)
                        </label>
                        <input
                            id="ml-discount-pct"
                            v-model="form.settings.ml_web_discount_percentage"
                            type="number"
                            step="0.5"
                            min="0"
                            max="90"
                            class="w-full rounded-base border-gray-300 text-sm font-bold focus:border-primary focus:ring-primary"
                        />
                    </div>

                    <div class="md:col-span-5 bg-surface border border-border rounded-base px-3.5 py-2.5 text-xs text-gray-700">
                        <span class="block text-[10px] font-bold uppercase text-gray-500">Ejemplo en vivo ($1,000.00 MXN en ML)</span>
                        <span class="font-extrabold text-dark text-sm">$1,000.00 ML &rarr; ${{ previewWebPrice() }} MXN (+ IVA)</span>
                    </div>

                    <div class="md:col-span-3 flex justify-end">
                        <button
                            type="button"
                            @click="recalculateWebPrices"
                            :disabled="recalculateForm.processing"
                            class="w-full px-4 py-2.5 bg-dark hover:bg-black text-white font-bold text-xs uppercase tracking-wider rounded-base shadow-xs transition disabled:opacity-50"
                        >
                            {{ recalculateForm.processing ? 'Recalculando...' : 'Recalcular Precios Web' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- INTEGRACIÓN API MERCADO LIBRE (OAUTH 2.0) -->
            <div v-show="activeTab === 'social'" class="bg-white rounded-base border border-gray-200 p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-3">
                    <div>
                        <h2 class="text-base font-bold text-dark">Conexión con Mercado Libre</h2>
                        <p class="text-xs text-gray-500">
                            Vincula tu cuenta vendedora de Mercado Libre para importar y mantener sincronizado tu catálogo en la tienda web.
                        </p>
                    </div>
                    <span
                        :class="mercadolibreStatus.connected ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                        class="px-2.5 py-1 rounded-full text-xs font-bold self-start sm:self-auto"
                    >
                        {{ mercadolibreStatus.connected ? 'Cuenta Conectada' : 'Sin Vincular' }}
                    </span>
                </div>

                <div
                    v-if="!mercadolibreStatus.has_credentials"
                    class="rounded-base bg-amber-50 border border-amber-200 p-4 text-xs text-amber-900"
                >
                    La conexión con Mercado Libre aún no ha sido habilitada en el servidor. Contacta al administrador técnico para activarla.
                </div>

                <div
                    v-else-if="mercadolibreStatus.connected"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-emerald-50/50 border border-emerald-200 rounded-base p-4"
                >
                    <div>
                        <span class="block text-emerald-700 font-bold uppercase text-[10px]">Cuenta Vendedora Vinculada</span>
                        <span class="font-mono font-bold text-dark">
                            ID {{ mercadolibreStatus.user_id }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-emerald-700 font-bold uppercase text-[10px]">Estado de Autorización</span>
                        <span class="font-bold text-emerald-800">
                            Activa (renovación automática)
                        </span>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-base bg-gray-50 border border-gray-200 p-4 text-xs text-gray-600"
                >
                    Haz clic en <strong>Conectar con Mercado Libre</strong> e inicia sesión con la cuenta vendedora donde publicas tus refacciones.
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <div class="flex flex-wrap items-center gap-3">
                        <a
                            :href="route('admin.mercadolibre.connect')"
                            class="px-5 py-2.5 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-black text-xs uppercase tracking-wider rounded-base shadow-sm transition inline-flex items-center gap-2"
                        >
                            <span>{{ mercadolibreStatus.connected ? 'Reconectar / Cambiar Cuenta ML' : 'Conectar con Mercado Libre' }}</span>
                            <span>&rarr;</span>
                        </a>

                        <button
                            v-if="mercadolibreStatus.connected"
                            type="button"
                            @click="disconnectMercadoLibre"
                            class="px-4 py-2.5 border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-base transition"
                        >
                            Desvincular Cuenta
                        </button>
                    </div>

                    <Link
                        :href="route('admin.products.index')"
                        class="text-xs font-bold text-primary hover:underline"
                    >
                        Ir al Sincronizador de Artículos &rarr;
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
