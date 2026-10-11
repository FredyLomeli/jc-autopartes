@php
    $business = config('business');
    $pageTitle = isset($title) ? "{$title} | {$business['name']}" : "{$business['name']} — {$business['tagline']}";
    $pageDescription = $metaDescription ?? 'Refacciones y autopartes usadas originales en Tepatitlán, Jalisco. Motores, transmisiones, colisión, suspensión y partes eléctricas con garantía escrita de 7 a 30 días y envíos a todo México.';
    $pageCanonical = $canonicalUrl ?? url()->current();
    $cartCount = collect(session('cart.items', []))->sum('quantity');
@endphp
<!DOCTYPE html>
<html lang="es-MX" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $pageCanonical }}">

    <!-- Open Graph -->
    <meta property="og:locale" content="es_MX">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:site_name" content="{{ $business['name'] }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $pageCanonical }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('imagenes/brand/logo.jpg') }}">

    <!-- Tipografía Inter -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/public.js'])

    @stack('head')
    @stack('jsonld')
</head>
<body class="min-h-screen flex flex-col bg-surface text-dark antialiased selection:bg-primary selection:text-white">

    <!-- Barra Superior de Confianza -->
    <div class="bg-dark text-white text-xs border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-1.5 font-medium">
                    <span class="w-2 h-2 rounded-full bg-primary inline-block"></span>
                    Garantía escrita de 7, 15 y 30 días en todas nuestras piezas
                </span>
                <span class="hidden md:inline-block text-gray-400">|</span>
                <span class="hidden md:inline-block text-gray-300">
                    Envíos a todo México desde {{ $business['city'] }}
                </span>
            </div>
            <div class="flex items-center gap-4 ml-auto">
                <a href="tel:{{ $business['phone_link'] }}" class="hover:text-gray-300 transition-colors inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span>Tel: {{ $business['phone_display'] }}</span>
                </a>
                <span class="text-gray-500">|</span>
                <a href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, busco cotizar una refacción.') }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="hover:text-whatsapp transition-colors font-semibold inline-flex items-center gap-1">
                    <span>WhatsApp: {{ $business['whatsapp_display'] }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Encabezado Principal (Estructura inspirada en UGS / AFR Autopartes) -->
    <header x-data="{ mobileMenuOpen: false }" class="bg-white border-b border-border sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-4">
                <!-- Logo JC Autopartes (Header claro) -->
                <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0 group" aria-label="{{ $business['name'] }} Inicio">
                    <img
                        src="{{ asset('imagenes/brand/logo_white.jpeg') }}"
                        alt="Logo {{ $business['name'] }}"
                        class="h-14 w-auto object-contain rounded-base"
                    >
                    <div class="hidden sm:block">
                        <span class="block text-lg font-extrabold tracking-tight text-dark leading-none">
                            JC <span class="text-primary">AUTOPARTES</span>
                        </span>
                        <span class="block text-[11px] font-medium text-gray-500 uppercase tracking-wider mt-0.5">
                            Yonke &amp; Refacciones Originales
                        </span>
                    </div>
                </a>

                <!-- Buscador Central Rápido (Escritorio) -->
                <form action="{{ url('/refacciones') }}" method="GET" class="hidden md:flex flex-1 max-w-xl mx-4" role="search">
                    <div class="relative flex w-full">
                        <label for="header-search" class="sr-only">Buscar por pieza, SKU, marca o modelo</label>
                        <input
                            id="header-search"
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar por pieza, SKU o vehículo (ej. Alternador Versa, Motor Hilux)..."
                            class="w-full rounded-l-base border border-border bg-surface px-4 py-2.5 text-sm text-dark placeholder-gray-500 focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        >
                        <button
                            type="submit"
                            class="bg-primary hover:bg-primary-hover text-white font-semibold px-5 py-2.5 rounded-r-base text-sm transition-colors inline-flex items-center gap-2 shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Buscar</span>
                        </button>
                    </div>
                </form>

                <!-- Acciones Rápidas: WhatsApp, Carrito y Botón Móvil -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, necesito ayuda para encontrar una pieza.') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="hidden lg:inline-flex items-center gap-2 px-3.5 py-2 rounded-base border border-border hover:border-dark text-xs font-semibold text-dark transition-colors">
                        <span class="w-2.5 h-2.5 rounded-full bg-whatsapp"></span>
                        <span>Atención Inmediata</span>
                    </a>

                    <a href="{{ url('/carrito') }}"
                       class="relative inline-flex items-center gap-2 px-3.5 py-2.5 rounded-base bg-dark hover:bg-gray-800 text-white text-sm font-semibold transition-colors"
                       aria-label="Ver carrito de compras">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="hidden sm:inline">Carrito</span>
                        <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-primary text-white text-xs font-bold">
                            {{ $cartCount }}
                        </span>
                    </a>

                    <!-- Botón Menú Móvil -->
                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        :aria-expanded="mobileMenuOpen.toString()"
                        aria-label="Abrir menú de navegación"
                        class="md:hidden inline-flex items-center justify-center p-2 rounded-base border border-border text-dark hover:bg-surface"
                    >
                        <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-cloak x-show="mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Buscador Móvil -->
            <div class="md:hidden pb-3">
                <form action="{{ url('/refacciones') }}" method="GET" role="search" class="flex">
                    <label for="mobile-search" class="sr-only">Buscar refacción</label>
                    <input
                        id="mobile-search"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Buscar pieza, SKU o modelo..."
                        class="w-full rounded-l-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:ring-1 focus:ring-primary"
                    >
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-r-base text-sm font-semibold">
                        Buscar
                    </button>
                </form>
            </div>
        </div>

        <!-- Barra de Navegación por Categorías (Escritorio) -->
        <nav aria-label="Navegación principal" class="hidden md:block bg-surface border-t border-border">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-11 text-sm">
                <div class="flex items-center gap-6 font-medium text-dark">
                    <a href="{{ url('/') }}" class="hover:text-primary transition-colors {{ request()->is('/') ? 'text-primary font-semibold' : '' }}">
                        Inicio
                    </a>
                    <a href="{{ url('/refacciones') }}" class="hover:text-primary transition-colors {{ request()->is('refacciones*') ? 'text-primary font-semibold' : '' }}">
                        Catálogo Completo
                    </a>
                    <a href="{{ url('/refacciones?category=Motores') }}" class="hover:text-primary transition-colors text-gray-700">
                        Motores
                    </a>
                    <a href="{{ url('/refacciones?category=Transmisiones') }}" class="hover:text-primary transition-colors text-gray-700">
                        Transmisiones
                    </a>
                    <a href="{{ url('/refacciones?category=Colision') }}" class="hover:text-primary transition-colors text-gray-700">
                        Carrocería y Colisión
                    </a>
                    <a href="{{ url('/refacciones?category=Suspension') }}" class="hover:text-primary transition-colors text-gray-700">
                        Suspensión
                    </a>
                    <a href="{{ url('/refacciones?category=Electrico') }}" class="hover:text-primary transition-colors text-gray-700">
                        Eléctrico
                    </a>
                </div>
                <div class="flex items-center gap-5 text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <a href="{{ url('/garantia') }}" class="hover:text-primary transition-colors {{ request()->is('garantia') ? 'text-primary' : '' }}">
                        Política de Garantía
                    </a>
                    <a href="{{ url('/contacto') }}" class="hover:text-primary transition-colors {{ request()->is('contacto') ? 'text-primary' : '' }}">
                        Ubicación y Contacto
                    </a>
                </div>
            </div>
        </nav>

        <!-- Menú Desplegable Móvil -->
        <nav x-cloak x-show="mobileMenuOpen" aria-label="Navegación móvil" class="md:hidden bg-white border-t border-border px-4 pt-3 pb-5 space-y-2">
            <a href="{{ url('/') }}" class="block py-2 px-3 rounded-base text-sm font-semibold hover:bg-surface">Inicio</a>
            <a href="{{ url('/refacciones') }}" class="block py-2 px-3 rounded-base text-sm font-semibold hover:bg-surface">Catálogo de Refacciones</a>
            <a href="{{ url('/refacciones?category=Motores') }}" class="block py-2 px-3 rounded-base text-sm text-gray-700 hover:bg-surface">Motores (30 días de garantía)</a>
            <a href="{{ url('/refacciones?category=Transmisiones') }}" class="block py-2 px-3 rounded-base text-sm text-gray-700 hover:bg-surface">Transmisiones (30 días de garantía)</a>
            <a href="{{ url('/refacciones?category=Colision') }}" class="block py-2 px-3 rounded-base text-sm text-gray-700 hover:bg-surface">Carrocería y Colisión</a>
            <a href="{{ url('/garantia') }}" class="block py-2 px-3 rounded-base text-sm font-semibold hover:bg-surface">Garantía Escrita</a>
            <a href="{{ url('/contacto') }}" class="block py-2 px-3 rounded-base text-sm font-semibold hover:bg-surface">Contacto y Sucursal</a>
        </nav>
    </header>

    <!-- Mensajes Flash de Sesión -->
    @if(session('success') || session('error'))
        <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
            @if(session('success'))
                <div class="rounded-base bg-green-50 border border-green-300 px-4 py-3 text-sm text-green-900 font-medium flex items-center justify-between" role="status">
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="rounded-base bg-red-50 border border-red-300 px-4 py-3 text-sm text-red-900 font-medium flex items-center justify-between" role="alert">
                    <span>{{ session('error') }}</span>
                </div>
            @endif
        </div>
    @endif

    <!-- Contenido Principal -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Pie de Página Oficial en Carbón (#111111) -->
    <footer class="bg-dark text-white mt-16 border-t-4 border-primary">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Columna 1: Identidad -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <img
                            src="{{ asset('imagenes/brand/logo.jpg') }}"
                            alt="Logo {{ $business['name'] }}"
                            class="h-12 w-auto object-contain rounded-base border border-white/10"
                        >
                        <span class="text-lg font-extrabold tracking-tight text-white">
                            JC <span class="text-primary">AUTOPARTES</span>
                        </span>
                    </div>
                    <p class="text-sm text-gray-300 leading-relaxed mb-4">
                        Centro especializado en autopartes y refacciones originales usadas verificadas. Inventario real sincronizado y envíos seguros a toda la República Mexicana.
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $business['hours']['weekdays'] }}<br>
                        {{ $business['hours']['saturday'] }}
                    </p>
                </div>

                <!-- Columna 2: Cobertura de Garantía Oficial -->
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-white mb-4 border-l-2 border-primary pl-2.5">
                        Garantía Oficial por Escrito
                    </h2>
                    <ul class="space-y-2.5 text-sm text-gray-300">
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-primary shrink-0">30 días:</span>
                            <span>Motores y cajas de transmisión.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-primary shrink-0">15 días:</span>
                            <span>Partes internas de motor (cabezas, cigüeñales).</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-bold text-primary shrink-0">7 días:</span>
                            <span>Eléctrico, mecánica general y carrocería.</span>
                        </li>
                    </ul>
                </div>

                <!-- Columna 3: Enlaces Rápidos y Legales -->
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-white mb-4 border-l-2 border-primary pl-2.5">
                        Atención e Información
                    </h2>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="{{ url('/refacciones') }}" class="hover:text-white transition-colors">Catálogo de Refacciones</a></li>
                        <li><a href="{{ url('/carrito') }}" class="hover:text-white transition-colors">Mi Carrito de Compras</a></li>
                        <li><a href="{{ url('/garantia') }}" class="hover:text-white transition-colors">Condiciones de Garantía</a></li>
                        <li><a href="{{ url('/contacto') }}" class="hover:text-white transition-colors">Sucursal y Contacto</a></li>
                        <li><a href="{{ url('/aviso-de-privacidad') }}" class="hover:text-white transition-colors">Aviso de Privacidad</a></li>
                        <li><a href="{{ url('/terminos-y-condiciones') }}" class="hover:text-white transition-colors">Términos y Condiciones</a></li>
                    </ul>
                </div>

                <!-- Columna 4: Contacto Directo Tepatitlán -->
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-white mb-4 border-l-2 border-primary pl-2.5">
                        Contacto y Ubicación
                    </h2>
                    <address class="not-italic text-sm text-gray-300 space-y-2.5">
                        <p class="leading-relaxed">
                            <strong class="text-white block">Mostrador y Bodega:</strong>
                            {{ $business['address'] }}
                        </p>
                        <p>
                            <strong class="text-white">WhatsApp Ventas:</strong>
                            <a href="https://wa.me/{{ $business['whatsapp_number'] }}" target="_blank" rel="noopener noreferrer" class="text-whatsapp hover:underline font-semibold">
                                {{ $business['whatsapp_display'] }}
                            </a>
                        </p>
                        <p>
                            <strong class="text-white">Teléfono Fijo:</strong>
                            <a href="tel:{{ $business['phone_link'] }}" class="hover:underline">
                                {{ $business['phone_display'] }}
                            </a>
                        </p>
                        <p>
                            <strong class="text-white">Correo:</strong>
                            <a href="mailto:{{ $business['email'] }}" class="hover:underline">
                                {{ $business['email'] }}
                            </a>
                        </p>
                    </address>
                </div>
            </div>

            <div class="border-t border-white/10 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-400 gap-4">
                <p>&copy; {{ date('Y') }} {{ $business['name'] }}. Todos los derechos reservados.</p>
                <p>Tepatitlán de Morelos, Jalisco, México.</p>
            </div>
        </div>
    </footer>

    <!-- Botón Flotante de WhatsApp -->
    <a href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, busco cotizar una refacción.') }}"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Contactar por WhatsApp a JC Autopartes"
       class="fixed bottom-5 right-5 z-50 inline-flex items-center gap-2 bg-whatsapp hover:brightness-95 text-white font-bold px-4 py-3 rounded-full shadow-lg transition-transform hover:scale-105">
        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"/>
        </svg>
        <span class="text-xs sm:text-sm">WhatsApp {{ $business['whatsapp_display'] }}</span>
    </a>

</body>
</html>
