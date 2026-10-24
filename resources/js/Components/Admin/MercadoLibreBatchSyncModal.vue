<script setup>
import { ref, computed, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    connected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'completed']);

// Estados del proceso
const isRunning = ref(false);
const isPaused = ref(false);
const isFinished = ref(false);
const errorMessage = ref(null);

// Parámetros de ejecución
const limit = ref(50);
const useFixture = ref(false);
const statusFilter = ref('active');

// Paginación continua
const scrollId = ref(null);
const offset = ref(0);
const retryCount = ref(0);

// Métricas en tiempo real
const totalRemote = ref(0);
const totalProcessed = ref(0);
const totalCreated = ref(0);
const totalUpdated = ref(0);
const totalErrors = ref(0);

// Feed en vivo de refacciones procesadas
const recentItems = ref([]);
const errorLog = ref([]);

// Cronómetro
const startTime = ref(null);
const elapsedSeconds = ref(0);
let timerInterval = null;

const progressPercentage = computed(() => {
    if (!totalRemote.value || totalRemote.value === 0) return 0;
    const pct = Math.min(100, Math.round((totalProcessed.value / totalRemote.value) * 100));
    return isNaN(pct) ? 0 : pct;
});

const formattedElapsedTime = computed(() => {
    const mins = Math.floor(elapsedSeconds.value / 60);
    const secs = elapsedSeconds.value % 60;
    return `${mins}m ${secs < 10 ? '0' : ''}${secs}s`;
});

const estimatedRemainingTime = computed(() => {
    if (totalProcessed.value === 0 || totalRemote.value === 0) return 'Calculando...';
    const remainingItems = Math.max(0, totalRemote.value - totalProcessed.value);
    if (remainingItems === 0) return '0s';
    const speedPerItem = elapsedSeconds.value / totalProcessed.value;
    const remainingSeconds = Math.round(remainingItems * speedPerItem);
    const mins = Math.floor(remainingSeconds / 60);
    const secs = remainingSeconds % 60;
    return `~${mins}m ${secs < 10 ? '0' : ''}${secs}s`;
});

const startTimer = () => {
    if (timerInterval) clearInterval(timerInterval);
    startTime.value = Date.now() - (elapsedSeconds.value * 1000);
    timerInterval = setInterval(() => {
        elapsedSeconds.value = Math.floor((Date.now() - startTime.value) / 1000);
    }, 1000);
};

const stopTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
};

const startSync = async () => {
    errorMessage.value = null;
    isFinished.value = false;
    isPaused.value = false;
    isRunning.value = true;
    retryCount.value = 0;
    
    // Si inicia desde cero, limpiar contadores
    if (totalProcessed.value === 0) {
        scrollId.value = null;
        offset.value = 0;
        totalCreated.value = 0;
        totalUpdated.value = 0;
        totalErrors.value = 0;
        recentItems.value = [];
        errorLog.value = [];
        elapsedSeconds.value = 0;
        
        try {
            await axios.post(route('admin.mercadolibre.sync-reset'));
        } catch {
            // Silencioso
        }
    }

    startTimer();
    processBatch();
};

const pauseSync = () => {
    isPaused.value = true;
    isRunning.value = false;
    stopTimer();
};

const resumeSync = () => {
    isPaused.value = false;
    isRunning.value = true;
    startTimer();
    processBatch();
};

const processBatch = async () => {
    if (isPaused.value || !isRunning.value) return;

    try {
        const payload = {
            scroll_id: scrollId.value,
            offset: offset.value,
            limit: limit.value,
            status: statusFilter.value,
            fixture: useFixture.value,
        };

        const response = await axios.post(route('admin.mercadolibre.sync-chunk'), payload);
        const data = response.data;

        if (!data.success) {
            throw new Error(data.message || 'Error en el lote');
        }

        // Actualizar contadores acumulados
        totalRemote.value = data.total_remote || totalRemote.value;
        totalProcessed.value += data.processed_count;
        totalCreated.value += data.created_count;
        totalUpdated.value += data.updated_count;
        totalErrors.value += data.failed_count;
        
        // Paginación continua
        scrollId.value = data.next_scroll_id;
        offset.value = data.next_offset;
        retryCount.value = 0;

        // Agregar al feed de refacciones recientes
        if (Array.isArray(data.items) && data.items.length > 0) {
            recentItems.value = [...data.items.slice(0, 5), ...recentItems.value].slice(0, 8);
        }

        // Registrar errores específicos si los hubo
        if (Array.isArray(data.errors) && data.errors.length > 0) {
            errorLog.value = [...data.errors, ...errorLog.value].slice(0, 20);
        }

        // ¿Quedan más piezas o ya terminamos?
        if (data.has_more && totalProcessed.value < totalRemote.value) {
            // Siguiente lote sin congelar el navegador
            setTimeout(() => {
                processBatch();
            }, 100);
        } else {
            // Fin de la sincronización masiva
            isRunning.value = false;
            isFinished.value = true;
            stopTimer();
            emit('completed', {
                total: totalProcessed.value,
                created: totalCreated.value,
                updated: totalUpdated.value,
            });
        }
    } catch (err) {
        if (retryCount.value < 3) {
            retryCount.value++;
            // Reintento automático tras 2 segundos si falló la red
            setTimeout(() => {
                processBatch();
            }, 2000);
        } else {
            isRunning.value = false;
            stopTimer();
            errorMessage.value = err.response?.data?.message || err.message || 'Fallo de conexión al sincronizar.';
        }
    }
};

const downloadReport = () => {
    window.location.href = route('admin.mercadolibre.sync-report') + '?download=1';
};

const close = () => {
    if (isRunning.value) {
        if (!confirm('La sincronización está en curso. ¿Deseas pausar y salir? Podrás reanudarla después.')) {
            return;
        }
        pauseSync();
    }
    emit('update:modelValue', false);
};

onUnmounted(() => {
    stopTimer();
});
</script>

<template>
    <div
        v-if="modelValue"
        class="fixed inset-0 z-50 overflow-y-auto bg-dark/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6"
    >
        <div class="bg-white rounded-card border border-border shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Encabezado del Modal -->
            <div class="px-6 py-4 border-b border-border bg-surface flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-base font-extrabold text-dark leading-tight">
                            Sincronizador Masivo de Catálogo
                        </h3>
                        <p class="text-xs text-gray-500">
                            Descarga por lotes progresivos optimizada para hosting DirectAdmin
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="close"
                    class="text-gray-400 hover:text-dark transition-colors p-1"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <!-- Barra de Progreso y Porcentaje -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-dark">Progreso de la Sincronización</span>
                        <span class="text-primary font-mono text-sm">{{ progressPercentage }}%</span>
                    </div>

                    <div class="w-full h-3.5 bg-gray-100 rounded-full overflow-hidden border border-border p-0.5">
                        <div
                            class="h-full bg-gradient-to-r from-primary to-rose-500 rounded-full transition-all duration-300"
                            :class="{ 'animate-pulse': isRunning }"
                            :style="{ width: `${progressPercentage}%` }"
                        ></div>
                    </div>

                    <div class="flex justify-between items-center text-[11px] text-gray-500 font-medium">
                        <span>
                            Procesadas: <strong class="text-dark font-mono">{{ totalProcessed.toLocaleString() }}</strong> / {{ totalRemote.toLocaleString() || '12,000+' }} refacciones
                        </span>
                        <span v-if="isRunning || isPaused">
                            Restante: <strong class="text-dark">{{ estimatedRemainingTime }}</strong> ({{ formattedElapsedTime }})
                        </span>
                    </div>
                </div>

                <!-- Tarjetas Métricas de la Sesión -->
                <div class="grid grid-cols-4 gap-3">
                    <div class="bg-surface rounded-base border border-border p-3 text-center">
                        <span class="text-[10px] uppercase font-bold text-gray-500 block">Procesadas</span>
                        <span class="text-base font-extrabold text-dark font-mono">{{ totalProcessed.toLocaleString() }}</span>
                    </div>
                    <div class="bg-emerald-50/60 rounded-base border border-emerald-200 p-3 text-center">
                        <span class="text-[10px] uppercase font-bold text-emerald-800 block">Nuevas</span>
                        <span class="text-base font-extrabold text-emerald-700 font-mono">+{{ totalCreated.toLocaleString() }}</span>
                    </div>
                    <div class="bg-blue-50/60 rounded-base border border-blue-200 p-3 text-center">
                        <span class="text-[10px] uppercase font-bold text-blue-800 block">Actualizadas</span>
                        <span class="text-base font-extrabold text-blue-700 font-mono">{{ totalUpdated.toLocaleString() }}</span>
                    </div>
                    <div class="bg-rose-50/60 rounded-base border border-rose-200 p-3 text-center">
                        <span class="text-[10px] uppercase font-bold text-rose-800 block">Incidencias</span>
                        <span class="text-base font-extrabold text-rose-700 font-mono">{{ totalErrors }}</span>
                    </div>
                </div>

                <!-- Opciones previas al inicio -->
                <div v-if="!isRunning && !isPaused && !isFinished" class="bg-surface/60 rounded-base border border-border p-4 space-y-3">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-500">Configuración del Lote</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-bold text-dark mb-1">Tamaño por paquete</label>
                            <select
                                v-model.number="limit"
                                class="w-full text-xs rounded border-border bg-white py-1.5 focus:border-primary focus:ring-primary"
                            >
                                <option :value="25">25 refacciones (Hosting muy ligero)</option>
                                <option :value="50">50 refacciones (Recomendado para DirectAdmin)</option>
                                <option :value="100">100 refacciones (Más rápido)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-dark mb-1">Fuente de datos</label>
                            <select
                                v-model="useFixture"
                                class="w-full text-xs rounded border-border bg-white py-1.5 focus:border-primary focus:ring-primary"
                            >
                                <option :value="false">API oficial en vivo de Mercado Libre</option>
                                <option :value="true">Modo Simulación / Fixture local</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Feed en Vivo de Refacciones -->
                <div class="space-y-1.5">
                    <span class="text-xs font-bold text-dark flex items-center justify-between">
                        <span>Registro en vivo de importación</span>
                        <span v-if="isRunning" class="text-emerald-600 flex items-center gap-1 font-normal text-[11px]">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                            Conectado con Mercado Libre
                        </span>
                    </span>

                    <div class="h-32 bg-dark rounded-base p-3 overflow-y-auto text-xs font-mono space-y-1 border border-border">
                        <div v-if="recentItems.length === 0" class="text-gray-500 italic">
                            El registro de refacciones aparecerá aquí al iniciar la sincronización...
                        </div>
                        <div
                            v-for="(item, idx) in recentItems"
                            :key="idx"
                            class="text-gray-300 flex items-center justify-between text-[11px]"
                        >
                            <span class="truncate max-w-sm">
                                <span class="text-emerald-400 font-bold">[{{ item.mercadolibre_id }}]</span> {{ item.title }}
                            </span>
                            <span class="text-amber-300 shrink-0 font-sans text-[10px]">
                                ${{ Number(item.price).toLocaleString() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de Error / Diagnóstico si ocurre -->
                <div v-if="errorMessage" class="rounded-base bg-rose-50 border border-rose-300 p-3 text-xs text-rose-900 space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>Atención durante la sincronización</span>
                    </div>
                    <p>{{ errorMessage }}</p>
                    <p class="text-[11px] text-rose-700">Puedes reanudar el proceso o descargar el reporte JSON para ver los detalles.</p>
                </div>

                <!-- Mensaje de Éxito al Finalizar -->
                <div v-if="isFinished" class="rounded-base bg-emerald-50 border border-emerald-300 p-3.5 text-xs text-emerald-900 flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <strong class="block font-extrabold">¡Sincronización masiva finalizada con éxito!</strong>
                        <p class="mt-0.5 text-emerald-800">
                            Se procesaron {{ totalProcessed.toLocaleString() }} publicaciones en {{ formattedElapsedTime }}. El catálogo de la tienda web está actualizado.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pie del Modal con Botones de Acción -->
            <div class="px-6 py-3.5 bg-surface border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3">
                <button
                    type="button"
                    @click="downloadReport"
                    class="inline-flex items-center gap-1.5 text-xs text-gray-700 hover:text-dark font-bold px-3 py-1.5 rounded border border-border bg-white hover:bg-gray-50 transition-colors"
                >
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Descargar Reporte JSON
                </button>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <!-- Botón Iniciar -->
                    <button
                        v-if="!isRunning && !isPaused && !isFinished"
                        type="button"
                        @click="startSync"
                        class="w-full sm:w-auto px-5 py-2 bg-primary hover:bg-primary-dark text-white text-xs font-extrabold rounded-button shadow-sm transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Iniciar Carga Masiva</span>
                    </button>

                    <!-- Botón Pausar -->
                    <button
                        v-if="isRunning"
                        type="button"
                        @click="pauseSync"
                        class="w-full sm:w-auto px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-button shadow-sm transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Pausar</span>
                    </button>

                    <!-- Botón Reanudar -->
                    <button
                        v-if="isPaused"
                        type="button"
                        @click="resumeSync"
                        class="w-full sm:w-auto px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-button shadow-sm transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        </svg>
                        <span>Reanudar</span>
                    </button>

                    <!-- Botón Cerrar -->
                    <button
                        type="button"
                        @click="close"
                        class="px-4 py-2 text-xs font-bold text-gray-700 hover:text-dark border border-border rounded-button bg-white hover:bg-gray-100 transition-colors"
                    >
                        {{ isFinished ? 'Cerrar y Ver Catálogo' : 'Salir' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
