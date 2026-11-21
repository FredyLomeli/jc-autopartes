<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const sidebarOpen = ref(false);

const mainNavItems = [
    {
        name: 'Panel',
        routeName: 'admin.dashboard',
        matchPrefix: '/admin',
        exact: true,
        badge: null,
    },
    {
        name: 'Artículos',
        routeName: 'admin.products.index',
        matchPrefix: '/admin/articulos',
        exact: false,
        badge: null,
    },
    {
        name: 'Pedidos',
        routeName: 'admin.orders.index',
        matchPrefix: '/admin/pedidos',
        exact: false,
        badge: null,
    },
    {
        name: 'Clientes',
        routeName: 'admin.customers.index',
        matchPrefix: '/admin/clientes',
        exact: false,
        badge: 'Próximamente',
    },
];

const settingSubItems = [
    {
        name: 'Información de Empresa',
        routeName: 'admin.settings.index',
        matchPrefix: '/admin/ajustes',
    },
    {
        name: 'Banners de Inicio',
        routeName: 'admin.banners.index',
        matchPrefix: '/admin/banners',
    },
    {
        name: 'Preguntas Frecuentes',
        routeName: 'admin.faqs.index',
        matchPrefix: '/admin/faqs',
    },
    {
        name: 'Promociones y Cupones',
        routeName: 'admin.promotions.index',
        matchPrefix: '/admin/promociones',
    },
];

const isCurrent = (item) => {
    const currentUrl = page.url.split('?')[0];
    if (item.exact) {
        return currentUrl === '/admin';
    }
    return currentUrl.startsWith(item.matchPrefix);
};

// Determinar si alguna sub-ruta de ajustes está activa
const isSettingsActive = computed(() => {
    const currentUrl = page.url.split('?')[0];
    return settingSubItems.some(sub => currentUrl.startsWith(sub.matchPrefix));
});

const settingsOpen = ref(true); // Abierto por defecto o interactivo
</script>

<template>
    <div class="h-[100dvh] lg:h-screen w-full bg-surface text-dark flex overflow-hidden">
        <!-- Overlay Móvil -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        ></div>

        <!-- Menú Lateral en Carbón (#111111) -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-40 w-64 h-[100dvh] flex-shrink-0 bg-dark text-white flex flex-col transition-transform duration-200 lg:static lg:w-64 lg:h-screen lg:flex-shrink-0 border-r border-white/10 overflow-hidden"
        >
            <!-- Logo y Cabecera Lateral -->
            <div class="h-16 px-5 flex items-center justify-between border-b border-white/10 flex-shrink-0 overflow-hidden">
                    <Link :href="route('admin.dashboard')" class="flex items-center gap-3 min-w-0">
                        <img
                            src="/imagenes/brand/logo.jpg"
                            alt="JC Autopartes"
                            width="48"
                            height="48"
                            style="max-height: 3rem; width: auto;"
                            class="max-h-12 h-10 w-auto object-contain rounded-base border border-white/15 flex-shrink-0"
                        />
                        <div class="min-w-0">
                            <span class="block text-sm font-extrabold tracking-tight text-white leading-none truncate">
                                JC <span class="text-primary">AUTOPARTES</span>
                            </span>
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-gray-400 mt-1 truncate">
                                Administración
                            </span>
                        </div>
                    </Link>

                    <button
                        type="button"
                        @click="sidebarOpen = false"
                        class="lg:hidden text-gray-400 hover:text-white flex-shrink-0"
                        aria-label="Cerrar menú lateral"
                    >
                        ✕
                    </button>
                </div>

                <!-- Navegación Lateral -->
                <nav class="flex-1 min-h-0 overflow-y-auto p-4 space-y-1.5" aria-label="Menú de administración">
                    <!-- Ítems Principales -->
                    <Link
                        v-for="item in mainNavItems"
                        :key="item.name"
                        :href="route(item.routeName)"
                        :class="[
                            isCurrent(item)
                                ? 'bg-primary text-white font-bold shadow-sm'
                                : 'text-gray-300 hover:bg-white/10 hover:text-white font-medium',
                        ]"
                        class="flex items-center justify-between px-3.5 py-2.5 rounded-base text-sm transition-colors"
                    >
                        <span>{{ item.name }}</span>
                        <span
                            v-if="item.badge"
                            class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded bg-white/15 text-gray-200"
                        >
                            {{ item.badge }}
                        </span>
                    </Link>

                    <!-- Grupo Colapsable / Sección: Ajustes del Sitio -->
                    <div class="pt-3">
                        <button
                            type="button"
                            @click="settingsOpen = !settingsOpen"
                            class="w-full flex items-center justify-between px-3.5 py-2 text-xs font-bold uppercase tracking-wider text-gray-400 hover:text-white transition-colors"
                        >
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Ajustes del Sitio</span>
                            </span>
                            <svg
                                :class="settingsOpen ? 'rotate-180' : ''"
                                class="w-3.5 h-3.5 transition-transform text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-show="settingsOpen" class="pl-3 mt-1 space-y-1 border-l-2 border-white/10 ml-3">
                            <Link
                                v-for="sub in settingSubItems"
                                :key="sub.name"
                                :href="route(sub.routeName)"
                                :class="[
                                    isCurrent(sub)
                                        ? 'bg-white/15 text-white font-bold'
                                        : 'text-gray-400 hover:text-white hover:bg-white/5 font-medium',
                                ]"
                                class="flex items-center px-3 py-2 rounded-base text-xs transition-colors"
                            >
                                <span>{{ sub.name }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>

            <!-- Pie del Menú Lateral: Tienda Pública y Usuario -->
            <div class="p-4 border-t border-white/10 space-y-3 flex-shrink-0 bg-dark">
                <a
                    href="/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-base bg-white/10 hover:bg-white/15 text-xs font-bold text-white transition-colors"
                >
                    <span>Ver Tienda Pública</span>
                    <span aria-hidden="true">↗</span>
                </a>

                <div class="px-1 text-xs">
                    <p class="font-bold text-white truncate">{{ user?.name }}</p>
                    <p class="text-gray-400 truncate">{{ user?.email }}</p>
                </div>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-base border border-rose-500/30 bg-rose-500/10 text-xs text-rose-200 hover:bg-rose-500/20 hover:text-white transition-colors font-bold"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Cerrar Sesión</span>
                </Link>
            </div>
        </aside>

        <!-- Contenedor Principal -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Barra Superior en Fondo Claro (#F7F7F7) -->
            <header class="h-16 bg-surface border-b border-gray-200 px-4 sm:px-6 flex items-center justify-between gap-2 flex-shrink-0">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 text-dark hover:bg-gray-200 rounded-base flex-shrink-0"
                        aria-label="Abrir menú"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="text-[11px] sm:text-xs text-gray-500 font-semibold uppercase tracking-wider truncate">
                        JC Autopartes Admin
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        En Línea
                    </span>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-base border border-rose-200 bg-white hover:bg-rose-50 text-xs font-bold text-rose-700 transition-colors"
                        title="Cerrar sesión"
                    >
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Salir</span>
                    </Link>
                </div>
            </header>

            <!-- Área de Contenido Principal -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <!-- Banner Flash de Éxito Global -->
                <div
                    v-if="page.props.flash?.success"
                    class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-base text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ page.props.flash.success }}</span>
                    </div>
                </div>

                <!-- Banner Flash de Error Global -->
                <div
                    v-if="page.props.flash?.error"
                    class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-base text-rose-800 text-sm font-medium flex items-center justify-between shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>{{ page.props.flash.error }}</span>
                    </div>
                </div>

                <!-- Cabecera de Página y Acciones -->
                <div
                    v-if="title || subtitle || $slots.actions"
                    class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div v-if="title || subtitle" class="min-w-0">
                        <h1 v-if="title" class="text-xl sm:text-2xl font-extrabold text-dark tracking-tight">
                            {{ title }}
                        </h1>
                        <p v-if="subtitle" class="text-xs sm:text-sm text-gray-500 mt-0.5">
                            {{ subtitle }}
                        </p>
                    </div>

                    <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 flex-shrink-0">
                        <slot name="actions" />
                    </div>
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
