@php
    $business = config('business');
    $pageTitle = isset($title) ? "{$title} | " . ($companySettings['company_name'] ?? $business['name']) : ($companySettings['company_name'] ?? $business['name']) . " — " . ($companySettings['company_slogan'] ?? $business['tagline']);
    $pageDescription = $metaDescription ?? 'Refacciones y autopartes usadas originales en Tepatitlán, Jalisco. Motores, transmisiones, colisión, suspensión y partes eléctricas con garantía oficial por escrito y envíos a todo México.';
    $pageCanonical = $canonicalUrl ?? url()->current();
    $cartCount = collect(session('cart.items', []))->sum('quantity');
    $customLogoPath = (string) ($companySettings['company_logo_path'] ?? '');
    $brandLogoUrl = $customLogoPath !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($customLogoPath)
        ? asset('storage/' . ltrim($customLogoPath, '/'))
        : asset('imagenes/brand/logo.jpg');
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

    <!-- Favicons e Íconos de Sitio -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Open Graph -->
    <meta property="og:locale" content="es_MX">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:site_name" content="{{ $business['name'] }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $pageCanonical }}">
    <meta property="og:image" content="{{ $ogImage ?? $brandLogoUrl }}">

    <!-- Tipografía Inter -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/public.js'])

    @stack('head')
    @stack('jsonld')
</head>
<body class="min-h-screen flex flex-col bg-surface text-dark antialiased selection:bg-primary selection:text-white">

    <!-- Encabezado Principal Oscuro con Logo Protagónico y Buscador -->
    <header x-data="{ mobileMenuOpen: false }" class="bg-dark text-white border-b border-white/10 sticky top-0 z-40 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-3 sm:py-4 gap-6">
                <!-- Logotipo Grande con Fondo Oscuro Integrado y Slogan Oficial -->
                <a href="{{ url('/') }}" class="flex items-center gap-4 shrink-0 group" aria-label="JC Autopartes Inicio">
                    <img
                        src="{{ $brandLogoUrl }}"
                        alt="Logo JC Autopartes"
                        class="h-24 sm:h-28 md:h-32 w-auto object-contain rounded-base transition group-hover:scale-102"
                    >
                    <div class="hidden xl:flex flex-col items-center text-center">
                        <span class="block text-2xl font-black tracking-tight text-white leading-none">
                            JC <span class="text-primary">AUTOPARTES</span>
                        </span>
                        <span class="block text-[11px] font-bold text-gray-300 uppercase tracking-widest mt-1 text-center w-full">
                            {{ $companySettings['company_badge'] ?? "Todo pa' tu máquina!" }}
                        </span>
                    </div>
                </a>

                <!-- Buscador Multifiltro Central Estilo AFR Autopartes (Escritura libre + Categoría + Marca + Año + Botón) -->
                <form action="{{ url('/refacciones') }}" method="GET" class="hidden md:flex flex-1 max-w-3xl items-center gap-1.5" role="search">
                    <div class="relative flex-1 min-w-[200px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar productos por escritura libre..."
                            class="w-full rounded-base border border-white/20 bg-white pl-9 pr-3 py-2 text-xs sm:text-sm text-dark placeholder-gray-500 focus:border-primary focus:ring-1 focus:ring-primary shadow-sm"
                        >
                    </div>

                    <!-- Categoría -->
                    <select
                        name="category"
                        class="rounded-base border border-white/20 bg-white py-2 px-2.5 text-xs text-dark focus:border-primary focus:ring-1 focus:ring-primary max-w-[165px]"
                    >
                        <option value="">Categoría</option>
                        @foreach($headerSearchCategories ?? [] as $cat)
                            <option value="{{ $cat['slug'] }}" {{ request('category') === $cat['slug'] ? 'selected' : '' }}>
                                {{ $cat['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Marca -->
                    <select
                        name="brand"
                        class="rounded-base border border-white/20 bg-white py-2 px-2.5 text-xs text-dark focus:border-primary focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Marca</option>
                        @foreach($headerSearchBrands as $b)
                            <option value="{{ $b }}" {{ request('brand') === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>

                    <!-- Año -->
                    <select
                        name="year"
                        class="rounded-base border border-white/20 bg-white py-2 px-2.5 text-xs text-dark focus:border-primary focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Año</option>
                        @foreach($headerSearchYears as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>

                    <!-- Botón BUSCAR -->
                    <button
                        type="submit"
                        class="bg-primary hover:bg-primary-hover text-white font-black px-4 py-2 rounded-base text-xs transition shadow-sm uppercase tracking-wider shrink-0"
                    >
                        Buscar
                    </button>
                </form>

                <!-- Carrito de Compras Estilo AFR con texto de Cotización Centrado y Más Grande -->
                <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                    <a href="{{ url('/carrito') }}" class="group flex flex-col items-center text-center" aria-label="Ver carrito">
                        <div class="relative inline-flex items-center justify-center p-1 text-white group-hover:text-primary transition">
                            <!-- Ícono de Carrito Rojo Más Grande -->
                            <svg class="w-10 h-10 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[1.4rem] h-5 px-1 rounded-full bg-primary text-white text-[11px] font-black border-2 border-dark shadow">
                                {{ $cartCount }}
                            </span>
                        </div>
                        <span class="hidden sm:block text-xs font-black uppercase text-white group-hover:text-primary tracking-tight mt-1 text-center">
                            ¡Solicita tu Cotización!
                        </span>
                    </a>

                    <!-- Botón Menú Móvil -->
                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        :aria-expanded="mobileMenuOpen.toString()"
                        aria-label="Abrir menú"
                        class="md:hidden inline-flex items-center justify-center p-2 rounded-base border border-white/20 text-white hover:bg-white/10"
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
                <form action="{{ url('/refacciones') }}" method="GET" role="search" class="flex gap-1">
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Buscar pieza o vehículo..."
                        class="w-full rounded-base border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-dark focus:border-primary focus:ring-primary"
                    >
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-base text-xs font-bold uppercase shrink-0">
                        Buscar
                    </button>
                </form>
            </div>
        </div>

        <!-- Barra Roja/Color Primario de Navegación Exacta como en AFR Autopartes -->
        <nav aria-label="Navegación principal" class="hidden md:block bg-primary text-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-center h-11 text-xs font-black uppercase tracking-wider space-x-8">
                <a href="{{ url('/') }}" class="hover:text-yellow-200 transition-colors {{ request()->is('/') ? 'text-yellow-300 border-b-2 border-yellow-300 pb-0.5' : '' }}">
                    HOME
                </a>
                <a href="{{ url('/#nosotros') }}" class="hover:text-yellow-200 transition-colors">
                    NOSOTROS
                </a>
                <a href="{{ url('/refacciones') }}" class="hover:text-yellow-200 transition-colors {{ request()->is('refacciones*') && !request('ofertas') ? 'text-yellow-300 border-b-2 border-yellow-300 pb-0.5' : '' }}">
                    PRODUCTOS
                </a>
                <a href="{{ url('/refacciones?ofertas=1') }}" class="hover:text-yellow-200 transition-colors inline-flex items-center gap-1.5 {{ request('ofertas') ? 'text-yellow-300 border-b-2 border-yellow-300 pb-0.5' : '' }}">
                    <span>OFERTAS</span>
                    <span class="bg-yellow-400 text-dark text-[10px] font-black px-1.5 py-0.5 rounded shadow-sm">HOT</span>
                </a>
                <a href="{{ url('/preguntas-frecuentes') }}" class="hover:text-yellow-200 transition-colors {{ request()->is('preguntas-frecuentes*') ? 'text-yellow-300 border-b-2 border-yellow-300 pb-0.5' : '' }}">
                    PREGUNTAS FRECUENTES
                </a>
                <a href="{{ url('/contacto') }}" class="hover:text-yellow-200 transition-colors {{ request()->is('contacto*') ? 'text-yellow-300 border-b-2 border-yellow-300 pb-0.5' : '' }}">
                    ATENCIÓN A CLIENTES
                </a>
                <a href="{{ url('/garantia') }}" class="hover:text-yellow-200 transition-colors {{ request()->is('garantia*') ? 'text-yellow-300 border-b-2 border-yellow-300 pb-0.5' : '' }}">
                    GARANTÍA ESCRITA
                </a>
            </div>
        </nav>

        <!-- Menú Desplegable Móvil -->
        <nav x-cloak x-show="mobileMenuOpen" aria-label="Navegación móvil" class="md:hidden bg-dark text-white border-t border-white/10 px-4 pt-3 pb-5 space-y-2">
            <a href="{{ url('/') }}" class="block py-2 px-3 rounded-base text-xs font-bold hover:bg-white/10">HOME</a>
            <a href="{{ url('/#nosotros') }}" class="block py-2 px-3 rounded-base text-xs font-bold hover:bg-white/10">NOSOTROS</a>
            <a href="{{ url('/refacciones') }}" class="block py-2 px-3 rounded-base text-xs font-bold hover:bg-white/10">PRODUCTOS (CATÁLOGO)</a>
            <a href="{{ url('/refacciones?ofertas=1') }}" class="block py-2 px-3 rounded-base text-xs font-black text-yellow-300 hover:bg-white/10">🔥 OFERTAS ESPECIALES</a>
            <a href="{{ url('/preguntas-frecuentes') }}" class="block py-2 px-3 rounded-base text-xs font-bold hover:bg-white/10">PREGUNTAS FRECUENTES</a>
            <a href="{{ url('/contacto') }}" class="block py-2 px-3 rounded-base text-xs font-bold hover:bg-white/10">ATENCIÓN A CLIENTES</a>
            <a href="{{ url('/garantia') }}" class="block py-2 px-3 rounded-base text-xs font-bold hover:bg-white/10">GARANTÍA ESCRITA</a>
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

    <!-- Cintillo Principal de Garantía Escrita JC Autopartes (Antes de Contacto) -->
    <section class="bg-primary text-white py-4 border-b border-primary-hover shadow-inner">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-black uppercase tracking-tight leading-tight">
                        Garantía Escrita JC Autopartes
                    </h3>
                    <p class="text-xs sm:text-sm text-yellow-100 font-medium">
                        {{ $companySettings['guarantee_banner_text'] ?? 'Respaldamos cada compra con procesos formales, compatibilidad verificada y garantía clara que protege tu inversión.' }}
                    </p>
                </div>
            </div>
            <a href="{{ url('/garantia') }}" class="px-5 py-2 rounded-base bg-white text-dark hover:bg-yellow-100 font-extrabold text-xs uppercase tracking-wider transition shadow shrink-0">
                Conoce Nuestra Garantía
            </a>
        </div>
    </section>

    <!-- Sección de Contacto Oficial Estilo AFR (Fondo Carbón Oscuro + Mapa de Tepatitlán + Datos Directos) -->
    <section id="contacto-seccion" class="bg-[#0b1320] text-white py-14 border-t border-white/5 relative overflow-hidden">
        <!-- Textura de fondo sutil automotriz -->
        <div class="absolute inset-0 bg-gradient-to-b from-dark/95 to-[#0b1320] pointer-events-none opacity-90"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <!-- Título Principal de Contacto estilo AFR -->
            <div class="text-center mb-10">
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                    ¡Contáctanos!
                </h2>
                <p class="text-gray-400 text-sm sm:text-base mt-2">
                    Escríbenos vía WhatsApp o visítanos en nuestra Matriz
                </p>

                <!-- Teléfono Centralizado Estilo AFR -->
                <div class="mt-4 inline-flex items-center justify-center gap-2 text-xl sm:text-2xl font-black text-white hover:text-primary transition">
                    <svg class="w-6 h-6 text-whatsapp" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"/>
                    </svg>
                    <a href="{{ $companySettings['contact_whatsapp_1_url'] ?? 'https://wa.me/3781455737' }}" target="_blank" rel="noopener noreferrer">
                        {{ $companySettings['contact_whatsapp_1'] ?? '378 145 5737' }}
                    </a>
                </div>
            </div>

            <!-- Bloque Dividido Estilo AFR: Mapa Izquierda + Datos a la Derecha -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center bg-white/5 p-6 sm:p-8 rounded-2xl border border-white/10 backdrop-blur-sm shadow-2xl">
                <!-- Columna Izquierda: Mapa de Google Maps de Tepatitlán -->
                <div class="lg:col-span-7 rounded-xl overflow-hidden shadow-lg border border-white/10 h-72 sm:h-80 w-full relative">
                    <iframe
                        src="{{ $companySettings['contact_maps_embed'] ?? 'https://maps.google.com/maps?q=Bertha%20Padilla%20Gutierrez%20%232020,%20Los%20Adobes,%20Tepatitlan,%20Jalisco&t=&z=15&ie=UTF8&iwloc=&output=embed' }}"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Ubicación JC Autopartes Matriz Tepatitlán"
                        class="w-full h-full"
                    ></iframe>
                </div>

                <!-- Columna Derecha: Datos Oficiales y Redes Sociales -->
                <div class="lg:col-span-5 space-y-5 text-sm sm:text-base">
                    <!-- Correo -->
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center shrink-0 text-primary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="block text-xs uppercase font-bold text-gray-400">Correo Oficial</span>
                            <a href="mailto:{{ $companySettings['contact_email'] ?? 'jcautopartes.mx@gmail.com' }}" class="text-white hover:text-primary transition font-medium">
                                {{ $companySettings['contact_email'] ?? 'jcautopartes.mx@gmail.com' }}
                            </a>
                        </div>
                    </div>

                    <!-- Domicilio Matriz -->
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center shrink-0 text-primary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="block text-xs uppercase font-bold text-gray-400">Dirección Matriz Tepatitlán</span>
                            <a href="{{ $companySettings['contact_maps_url'] ?? 'https://maps.app.goo.gl/HpyvbhFdg5vkYB167' }}" target="_blank" rel="noopener noreferrer" class="text-white hover:text-primary transition font-medium leading-snug">
                                {{ $companySettings['contact_address'] ?? 'Bertha Padilla Gutierrez #2020' }}, {{ $companySettings['contact_colony'] ?? 'Col. Los Adobes' }}, C.P. {{ $companySettings['contact_zip'] ?? '47656' }}, {{ $companySettings['contact_city'] ?? 'Tepatitlán de Morelos, Jalisco' }}.
                            </a>
                        </div>
                    </div>

                    <!-- Horarios -->
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center shrink-0 text-primary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="block text-xs uppercase font-bold text-gray-400">Horarios de Atención</span>
                            <p class="text-white font-medium">
                                {{ $companySettings['schedule_weekday'] ?? 'Lun-Vie: 9:00 am - 6:00 pm' }}<br>
                                {{ $companySettings['schedule_saturday'] ?? 'Sábados: 9:00 am - 2:00 pm' }}
                            </p>
                        </div>
                    </div>

                    <!-- Redes Sociales y Mercado Libre (Íconos Circulares estilo AFR) -->
                    <div class="pt-3 border-t border-white/10">
                        <span class="block text-xs uppercase font-bold text-gray-400 mb-3">Nuestras Redes y Tienda Oficial</span>
                        <div class="flex items-center gap-3">
                            <!-- Tienda Mercado Libre Oficial -->
                            <a
                                href="{{ $companySettings['social_mercadolibre'] ?? 'https://listado.mercadolibre.com.mx/_CustId_546980216' }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="Tienda Oficial Mercado Libre"
                                class="w-10 h-10 rounded-full bg-yellow-400 hover:bg-yellow-300 text-dark flex items-center justify-center transition shadow font-black text-xs"
                            >
                                ML
                            </a>

                            <!-- Facebook -->
                            <a
                                href="{{ $companySettings['social_facebook'] ?? 'https://facebook.com/jcautopartestepa' }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="Facebook Oficial"
                                class="w-10 h-10 rounded-full bg-blue-600 hover:bg-blue-500 text-white flex items-center justify-center transition shadow"
                            >
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                    <path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/>
                                </svg>
                            </a>

                            <!-- Instagram -->
                            <a
                                href="{{ $companySettings['social_instagram'] ?? 'https://instagram.com/jcautopartesmx' }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="Instagram Oficial"
                                class="w-10 h-10 rounded-full bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 hover:opacity-90 text-white flex items-center justify-center transition shadow"
                            >
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sub-Footer Legal y Derechos -->
    <footer class="bg-black text-gray-400 py-6 border-t border-white/10 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="font-extrabold text-white">JC AUTOPARTES</span>
                <span>&copy; {{ date('Y') }}. Todos los derechos reservados.</span>
            </div>
            <div class="flex items-center gap-4 text-gray-400">
                <a href="{{ url('/garantia') }}" class="hover:text-white transition">Garantía Escrita</a>
                <span>•</span>
                <a href="{{ url('/terminos-y-condiciones') }}" class="hover:text-white transition">Términos y Condiciones</a>
                <span>•</span>
                <a href="{{ url('/aviso-de-privacidad') }}" class="hover:text-white transition">Aviso de Privacidad</a>
            </div>
        </div>
    </footer>

    <!-- Botón Flotante de WhatsApp Compacto (Estilo AFR, tamaño discreto y selector de líneas) -->
    <div x-data="{ openWhatsappMenu: false }" class="fixed bottom-5 right-5 z-50">
        <!-- Menú emergente de Líneas de WhatsApp -->
        <div
            x-show="openWhatsappMenu"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            @click.away="openWhatsappMenu = false"
            class="mb-3 bg-white rounded-2xl shadow-2xl border border-gray-200 p-4 w-72 text-dark space-y-3"
            style="display: none;"
        >
            <div class="border-b border-gray-100 pb-2">
                <h4 class="font-black text-xs uppercase tracking-wider text-dark">Ventas JC Autopartes</h4>
                <p class="text-[11px] text-gray-500">¿En qué podemos ayudarte hoy?</p>
            </div>

            <!-- Línea 1 -->
            <a
                href="{{ $companySettings['contact_whatsapp_1_url'] ?? 'https://wa.me/3781455737' }}"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-emerald-50 transition border border-gray-100 hover:border-emerald-300 group"
            >
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-whatsapp group-hover:text-white transition">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                </div>
                <div>
                    <span class="block text-xs font-bold text-dark group-hover:text-emerald-800">Línea de Ventas 1</span>
                    <span class="block text-[11px] text-gray-500 font-medium">{{ $companySettings['contact_whatsapp_1'] ?? '378 145 5737' }}</span>
                </div>
            </a>

            <!-- Línea 2 -->
            <a
                href="{{ $companySettings['contact_whatsapp_2_url'] ?? 'https://wa.me/3782724234' }}"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-emerald-50 transition border border-gray-100 hover:border-emerald-300 group"
            >
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 group-hover:bg-whatsapp group-hover:text-white transition">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                </div>
                <div>
                    <span class="block text-xs font-bold text-dark group-hover:text-emerald-800">Línea de Ventas 2</span>
                    <span class="block text-[11px] text-gray-500 font-medium">{{ $companySettings['contact_whatsapp_2'] ?? '378 272 4234' }}</span>
                </div>
            </a>
        </div>

        <!-- Botón Circular Compacto (Estilo AFR ~48px) -->
        <button
            type="button"
            @click="openWhatsappMenu = !openWhatsappMenu"
            aria-label="Abrir WhatsApp JC Autopartes"
            class="w-12 h-12 rounded-full bg-whatsapp hover:bg-emerald-600 text-white flex items-center justify-center shadow-2xl transition-transform hover:scale-110 focus:outline-none"
        >
            <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
            </svg>
        </button>
    </div>

</body>
</html>
