<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: 'Panel Administrativo',
    },
    subtitle: {
        type: String,
        default: '',
    },
});

const sidebarOpen = ref(false);
const page = usePage();

const user = computed(() => page.props.auth?.user);
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const navItems = [
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
    {
        name: 'Configuración',
        routeName: 'admin.settings.index',
        matchPrefix: '/admin/configuracion',
        exact: false,
        badge: null,
    },
];

const isCurrent = (item) => {
    const currentUrl = page.url.split('?')[0];
    if (item.exact) {
        return currentUrl === '/admin';
    }
    return currentUrl.startsWith(item.matchPrefix);
};
</script>

<template>
    <div class="h-screen w-full bg-surface text-dark flex overflow-hidden">
        <!-- Overlay Móvil -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        ></div>

        <!-- Menú Lateral en Carbón (#111111) -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-40 w-64 h-screen flex-shrink-0 bg-dark text-white flex flex-col justify-between transition-transform duration-200 lg:static lg:w-64 lg:h-screen lg:flex-shrink-0 border-r border-white/10 overflow-y-auto overflow-x-hidden"
        >
            <div>
                <!-- Logo y Cabecera Lateral -->
                <div class="h-16 px-5 flex items-center justify-between border-b border-white/10 overflow-hidden">
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
                <nav class="p-4 space-y-1.5" aria-label="Menú de administración">
                    <Link
                        v-for="item in navItems"
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
                </nav>
            </div>

            <!-- Pie del Menú Lateral: Tienda Pública y Usuario -->
            <div class="p-4 border-t border-white/10 space-y-3">
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
                    class="w-full text-left px-3 py-2 rounded-base text-xs font-bold text-red-400 hover:bg-white/5 hover:text-red-300 transition-colors"
                >
                    Cerrar sesión
                </Link>
            </div>
        </aside>

        <!-- Contenedor Derecho Principal con Scrollbar Independiente -->
        <div class="flex-1 h-screen max-h-screen min-w-0 overflow-y-auto overflow-x-hidden flex flex-col">
            <!-- Barra Superior -->
            <header class="bg-white border-b border-border h-16 shrink-0 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4 sticky top-0 z-20">
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 rounded-base border border-border text-dark hover:bg-surface"
                        aria-label="Abrir menú lateral"
                    >
                        ☰
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-extrabold text-dark truncate">{{ title }}</h1>
                        <p v-if="subtitle" class="text-xs text-gray-500 truncate hidden sm:block">{{ subtitle }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <slot name="actions" />
                </div>
            </header>

            <!-- Mensajes Flash -->
            <div v-if="flashSuccess || flashError" class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-4 shrink-0">
                <div
                    v-if="flashSuccess"
                    class="rounded-base bg-green-50 border border-green-300 px-4 py-3 text-sm font-semibold text-green-900"
                    role="status"
                >
                    {{ flashSuccess }}
                </div>
                <div
                    v-if="flashError"
                    class="rounded-base bg-red-50 border border-red-300 px-4 py-3 text-sm font-semibold text-red-900"
                    role="alert"
                >
                    {{ flashError }}
                </div>
            </div>

            <!-- Contenido de la Página -->
            <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <slot />
            </main>
        </div>
    </div>
</template>
