<script setup>
import { Head, useForm } from '@inertiajs/vue3';
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
        </div>
    </AdminLayout>
</template>
