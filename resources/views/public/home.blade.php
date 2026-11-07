@extends('layouts.public', [
    'title' => 'Inicio — Autopartes y Refacciones Originales Usadas',
    'metaDescription' => 'Encuentra motores, transmisiones, colisión, suspensión y partes eléctricas originales usadas con garantía escrita de 7 a 30 días en JC Autopartes, Tepatitlán, Jalisco.',
    'canonicalUrl' => route('home'),
])

@php
    $business = config('business');
    $categoryCards = $categories ?? [
        ['slug' => 'Motores', 'name' => 'Motores Completos y 3/4', 'warranty' => '30 días de garantía', 'desc' => 'Motores verificados en compresión y funcionamiento listos para instalar.'],
        ['slug' => 'Transmisiones', 'name' => 'Transmisiones Automáticas y Estándar', 'warranty' => '30 días de garantía', 'desc' => 'Cajas de velocidades automáticas, CVT y manuales revisadas.'],
        ['slug' => 'Partes Internas', 'name' => 'Cabezas y Partes Internas', 'warranty' => '15 días de garantía', 'desc' => 'Cabezas de motor, cigüeñales, árboles de levas y múltiples.'],
        ['slug' => 'Colision', 'name' => 'Carrocería y Colisión', 'warranty' => '7 días de garantía', 'desc' => 'Cofres, puertas, salpicaderas, fascias, faros, calaveras y espejos.'],
        ['slug' => 'Suspension', 'name' => 'Suspensión y Dirección', 'warranty' => '7 días de garantía', 'desc' => 'Horquillas, amortiguadores, cajas de dirección, puentes y mangos.'],
        ['slug' => 'Electrico', 'name' => 'Sistema Eléctrico y Electrónico', 'warranty' => '7 días de garantía', 'desc' => 'Alternadores, marchas, módulos, cuerpos de aceleración y compresores.'],
    ];

    $valuesList = [];
    if (!empty($companySettings['company_values'])) {
        $decoded = json_decode($companySettings['company_values'], true);
        if (is_array($decoded)) {
            $valuesList = $decoded;
        }
    }
@endphp

@section('content')
    <!-- Título H1 Semántico Oculto para SEO si el banner tiene imagen pura -->
    <h1 class="sr-only">JC Autopartes — Refacciones y Autopartes Originales con Garantía Escrita en Tepatitlán</h1>

    <!-- 1. CARRUSEL HERO PRINCIPAL (Estilo AFR 16:9 / 1920x1080) -->
    @if(isset($banners) && $banners->count() > 0)
        <section
            x-data="{
                currentSlide: 0,
                totalSlides: {{ $banners->count() }},
                autoplayTimer: null,
                startAutoplay() {
                    this.autoplayTimer = setInterval(() => {
                        this.next();
                    }, 5000);
                },
                stopAutoplay() {
                    if (this.autoplayTimer) clearInterval(this.autoplayTimer);
                },
                next() {
                    this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
                },
                prev() {
                    this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
                },
                goTo(index) {
                    this.currentSlide = index;
                }
            }"
            x-init="startAutoplay()"
            @mouseenter="stopAutoplay()"
            @mouseleave="startAutoplay()"
            class="relative w-full bg-dark overflow-hidden select-none border-b border-gray-100 shadow-sm"
        >
            <div class="relative w-full aspect-[16/9] max-h-[640px] overflow-hidden">
                @foreach($banners as $index => $banner)
                    <div
                        x-show="currentSlide === {{ $index }}"
                        x-transition:enter="transition ease-out duration-700"
                        x-transition:enter-start="opacity-0 scale-98"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-500"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute inset-0 w-full h-full"
                        style="{{ $index === 0 ? '' : 'display: none;' }}"
                    >
                        <img
                            src="{{ $banner->image_url }}"
                            alt="{{ $banner->title ?? 'JC Autopartes' }}"
                            class="w-full h-full object-cover object-center"
                        >

                        @if($banner->title || $banner->button_text)
                            <div class="absolute inset-0 bg-gradient-to-r from-dark/70 via-dark/30 to-transparent flex items-center">
                                <div class="max-w-7xl mx-auto px-6 sm:px-12 w-full">
                                    <div class="max-w-xl text-white space-y-3">
                                        @if($banner->title)
                                            <h2 class="text-2xl sm:text-4xl md:text-5xl font-black tracking-tight leading-tight drop-shadow-md">
                                                {{ $banner->title }}
                                            </h2>
                                        @endif
                                        @if($banner->subtitle)
                                            <p class="text-xs sm:text-base md:text-lg text-gray-200 drop-shadow">
                                                {{ $banner->subtitle }}
                                            </p>
                                        @endif
                                        @if($banner->button_text && $banner->button_url)
                                            <div class="pt-2">
                                                <a
                                                    href="{{ $banner->button_url }}"
                                                    class="inline-flex items-center gap-2 px-6 py-3 bg-primary hover:bg-primary-hover text-white font-black text-xs sm:text-sm uppercase tracking-wider rounded-base transition shadow-lg"
                                                >
                                                    <span>{{ $banner->button_text }}</span>
                                                    <span>→</span>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($banners->count() > 1)
                <button
                    type="button"
                    @click="prev()"
                    class="absolute left-3 sm:left-6 top-1/2 -translate-y-1/2 z-20 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-dark/40 hover:bg-primary text-white flex items-center justify-center transition backdrop-blur-sm shadow-md"
                    aria-label="Banner anterior"
                >
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button
                    type="button"
                    @click="next()"
                    class="absolute right-3 sm:right-6 top-1/2 -translate-y-1/2 z-20 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-dark/40 hover:bg-primary text-white flex items-center justify-center transition backdrop-blur-sm shadow-md"
                    aria-label="Siguiente banner"
                >
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>

                <div class="absolute bottom-4 left-0 right-0 z-20 flex items-center justify-center gap-2">
                    @foreach($banners as $index => $banner)
                        <button
                            type="button"
                            @click="goTo({{ $index }})"
                            :class="currentSlide === {{ $index }} ? 'w-8 bg-primary' : 'w-2.5 bg-white/50 hover:bg-white'"
                            class="h-2.5 rounded-full transition-all duration-300"
                            aria-label="Ir al banner {{ $index + 1 }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <!-- 2. SECCIÓN: ¿QUÉ NOS HACE DIFERENTES? (Diseño Pulido, Proporciones Perfectas y Envolventes) -->
    <section class="bg-white py-16 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-black text-[#0c1a30] tracking-tight mb-14">
                ¿Qué nos hace diferentes?
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10 max-w-5xl mx-auto">
                <!-- Columna 1: Tarjetas y Métodos de Pago -->
                <div class="flex flex-col items-center text-center space-y-4 group">
                    <div class="w-24 h-24 rounded-2xl bg-gray-50 border border-gray-200 flex items-center justify-center shadow-xs group-hover:scale-105 group-hover:border-primary group-hover:bg-primary/5 transition-all duration-300">
                        <!-- SVG Ícono de Tarjeta Bancaria con Chip -->
                        <svg class="w-14 h-14 text-dark group-hover:text-primary transition-colors" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <rect x="2" y="5" width="20" height="14" rx="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M2 10h20" stroke-linecap="round" />
                            <rect x="5.5" y="13.5" width="4" height="2.5" rx="0.5" fill="currentColor" stroke="none" class="opacity-80" />
                            <circle cx="16" cy="14.7" r="1.5" stroke-width="1.3" />
                            <circle cx="18" cy="14.7" r="1.5" stroke-width="1.3" />
                        </svg>
                    </div>
                    <h3 class="text-base sm:text-lg font-black text-gray-800 leading-snug">
                        Diferentes métodos de pago seguros
                    </h3>
                    <p class="text-xs text-gray-500 max-w-xs leading-relaxed">
                        Paga con tarjetas Visa, Mastercard, AMEX, SPEI o efectivo en tiendas mediante Mercado Pago.
                    </p>
                </div>

                <!-- Columna 2: Vehículos y Autopartes de Calidad con Check -->
                <div class="flex flex-col items-center text-center space-y-4 group">
                    <div class="w-24 h-24 rounded-2xl bg-gray-50 border border-gray-200 flex items-center justify-center shadow-xs group-hover:scale-105 group-hover:border-primary group-hover:bg-primary/5 transition-all duration-300">
                        <!-- SVG Silueta de Auto Elegante -->
                        <svg class="w-14 h-14 text-dark group-hover:text-primary transition-colors" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.7 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.5-1.2-.7-1.9-.7H7.5c-.8 0-1.5.4-1.9 1.1L3.3 12.2C2.5 12.5 2 13.2 2 14v2c0 .6.4 1 1 1h2" />
                            <circle cx="7" cy="17" r="2" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="17" cy="17" r="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17h6" />
                            <!-- Ventanas -->
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12l2-3.5c.2-.3.5-.5.9-.5H12v4H6z" />
                        </svg>
                    </div>
                    <h3 class="text-base sm:text-lg font-black text-gray-800 leading-snug">
                        Autopartes originales garantizadas
                    </h3>
                    <p class="text-xs text-gray-500 max-w-xs leading-relaxed">
                        Piezas revisadas, probadas y respaldadas con póliza de garantía escrita de 7 a 30 días.
                    </p>
                </div>

                <!-- Columna 3: Camión de Envíos Express Nacionales -->
                <div class="flex flex-col items-center text-center space-y-4 group">
                    <div class="w-24 h-24 rounded-2xl bg-gray-50 border border-gray-200 flex items-center justify-center shadow-xs group-hover:scale-105 group-hover:border-primary group-hover:bg-primary/5 transition-all duration-300">
                        <!-- SVG Camión de Carga Limpio -->
                        <svg class="w-14 h-14 text-dark group-hover:text-primary transition-colors" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                            <rect x="2" y="6" width="13" height="10" rx="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h4l2.5 3.5v3.5h-6.5V9z" />
                            <circle cx="6.5" cy="18" r="2" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="17.5" cy="18" r="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 18h7" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2 12h13" stroke-dasharray="2 2" />
                        </svg>
                    </div>
                    <h3 class="text-base sm:text-lg font-black text-gray-800 leading-snug">
                        Envíos seguros a todo México
                    </h3>
                    <p class="text-xs text-gray-500 max-w-xs leading-relaxed">
                        Empaque especializado y despachos con paqueterías certificadas desde Tepatitlán, Jalisco.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. SECCIÓN: ¡ESTAS SON LAS MARCAS CON LAS QUE TRABAJAMOS! (Logotipos Vectoriales Reales sobre fondo Carbón/Azul) -->
    <section class="bg-gradient-to-b from-[#0a1424] via-[#070e1b] to-[#040810] text-white py-16 border-y border-white/5 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight mb-12 drop-shadow">
                ¡Estas son las marcas con las que trabajamos!
            </h2>

            <!-- Malla con Emblemas Vectoriales de Marcas Reales -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-6 max-w-5xl mx-auto">
                <!-- 1. BMW -->
                <a href="{{ route('catalog.index', ['brand' => 'BMW']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center">
                        <svg class="w-12 h-12" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="46" fill="none" stroke="#ffffff" stroke-width="4"/>
                            <circle cx="50" cy="50" r="32" fill="#111111" stroke="#ffffff" stroke-width="3"/>
                            <!-- Cuadrantes BMW -->
                            <path d="M50 18 A32 32 0 0 1 82 50 L50 50 Z" fill="#0066b1"/>
                            <path d="M50 50 L82 50 A32 32 0 0 1 50 82 Z" fill="#ffffff"/>
                            <path d="M50 50 L50 82 A32 32 0 0 1 18 50 Z" fill="#0066b1"/>
                            <path d="M50 18 L50 50 L18 50 A32 32 0 0 1 50 18 Z" fill="#ffffff"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">BMW</span>
                </a>

                <!-- 2. MERCEDES-BENZ -->
                <a href="{{ route('catalog.index', ['brand' => 'Mercedes-Benz']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-white group-hover:text-primary transition-colors">
                        <svg class="w-12 h-12" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="3.5">
                            <circle cx="50" cy="50" r="44"/>
                            <line x1="50" y1="6" x2="50" y2="50"/>
                            <line x1="50" y1="50" x2="13" y2="72"/>
                            <line x1="50" y1="50" x2="87" y2="72"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Mercedes</span>
                </a>

                <!-- 3. AUDI -->
                <a href="{{ route('catalog.index', ['brand' => 'Audi']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-white group-hover:text-primary transition-colors">
                        <svg class="w-14 h-8" viewBox="0 0 140 50" fill="none" stroke="currentColor" stroke-width="4">
                            <circle cx="25" cy="25" r="20"/>
                            <circle cx="55" cy="25" r="20"/>
                            <circle cx="85" cy="25" r="20"/>
                            <circle cx="115" cy="25" r="20"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Audi</span>
                </a>

                <!-- 4. VOLKSWAGEN -->
                <a href="{{ route('catalog.index', ['brand' => 'Volkswagen']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-white group-hover:text-primary transition-colors">
                        <svg class="w-12 h-12" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="4">
                            <circle cx="50" cy="50" r="44"/>
                            <!-- V -->
                            <path d="M30 22 L50 65 L70 22" stroke-linecap="round" stroke-linejoin="round"/>
                            <!-- W -->
                            <path d="M22 45 L40 85 L50 65 L60 85 L78 45" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Volkswagen</span>
                </a>

                <!-- 5. TOYOTA -->
                <a href="{{ route('catalog.index', ['brand' => 'Toyota']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-white group-hover:text-primary transition-colors">
                        <svg class="w-13 h-10" viewBox="0 0 100 70" fill="none" stroke="currentColor" stroke-width="4">
                            <ellipse cx="50" cy="35" rx="46" ry="30"/>
                            <ellipse cx="50" cy="30" rx="20" ry="24"/>
                            <ellipse cx="50" cy="20" rx="32" ry="12"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Toyota</span>
                </a>

                <!-- 6. NISSAN -->
                <a href="{{ route('catalog.index', ['brand' => 'Nissan']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-white group-hover:text-primary transition-colors">
                        <svg class="w-13 h-10" viewBox="0 0 100 70" fill="none" stroke="currentColor" stroke-width="4">
                            <circle cx="50" cy="35" r="30"/>
                            <rect x="10" y="27" width="80" height="16" rx="2" fill="#070e1b" stroke="currentColor" stroke-width="3"/>
                            <text x="50" y="39" font-size="10" font-weight="900" fill="currentColor" text-anchor="middle" letter-spacing="1">NISSAN</text>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Nissan</span>
                </a>

                <!-- 7. CHEVROLET -->
                <a href="{{ route('catalog.index', ['brand' => 'Chevrolet']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-yellow-400 group-hover:scale-105 transition-transform">
                        <svg class="w-14 h-9" viewBox="0 0 100 50" fill="currentColor">
                            <polygon points="10,20 38,20 32,8 68,8 62,20 90,20 86,32 58,32 64,44 28,44 34,32 6,32"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Chevrolet</span>
                </a>

                <!-- 8. FORD -->
                <a href="{{ route('catalog.index', ['brand' => 'Ford']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-blue-400 group-hover:scale-105 transition-transform">
                        <svg class="w-14 h-9" viewBox="0 0 100 50">
                            <ellipse cx="50" cy="25" rx="46" ry="22" fill="#003478" stroke="#ffffff" stroke-width="2.5"/>
                            <text x="50" y="32" font-family="cursive, Georgia" font-size="22" font-weight="bold" font-style="italic" fill="#ffffff" text-anchor="middle">Ford</text>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Ford</span>
                </a>

                <!-- 9. HONDA -->
                <a href="{{ route('catalog.index', ['brand' => 'Honda']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-white group-hover:text-primary transition-colors">
                        <svg class="w-12 h-10" viewBox="0 0 100 80" fill="none" stroke="currentColor" stroke-width="4">
                            <rect x="15" y="10" width="70" height="60" rx="8" />
                            <path d="M30 18 L36 62 M70 18 L64 62 M33 40 L67 40" stroke-width="5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Honda</span>
                </a>

                <!-- 10. MAZDA -->
                <a href="{{ route('catalog.index', ['brand' => 'Mazda']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-white group-hover:text-primary transition-colors">
                        <svg class="w-12 h-10" viewBox="0 0 100 70" fill="none" stroke="currentColor" stroke-width="4">
                            <ellipse cx="50" cy="35" rx="44" ry="28"/>
                            <path d="M26 38 C35 24, 45 28, 50 38 C55 28, 65 24, 74 38" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Mazda</span>
                </a>

                <!-- 11. JEEP -->
                <a href="{{ route('catalog.index', ['brand' => 'Jeep']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-white group-hover:text-primary transition-colors">
                        <span class="font-black text-xl tracking-tight uppercase border-2 border-white px-2 py-0.5 rounded">Jeep</span>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Jeep</span>
                </a>

                <!-- 12. PEUGEOT -->
                <a href="{{ route('catalog.index', ['brand' => 'Peugeot']) }}" class="bg-white/5 hover:bg-white/12 border border-white/10 hover:border-primary/60 rounded-2xl p-5 flex flex-col items-center justify-center gap-3 transition-all duration-300 group shadow-lg">
                    <div class="w-14 h-14 flex items-center justify-center text-white group-hover:text-primary transition-colors">
                        <svg class="w-11 h-11" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="3.5">
                            <polygon points="50,10 85,25 78,80 50,92 22,80 15,25"/>
                            <path d="M50 30 L50 65 M40 45 L60 45" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-gray-200 group-hover:text-primary transition">Peugeot</span>
                </a>
            </div>

            <div class="mt-10">
                <a
                    href="{{ route('catalog.index') }}"
                    class="inline-flex items-center gap-2 px-8 py-3.5 bg-primary hover:bg-primary-hover text-white font-black text-xs uppercase tracking-widest rounded-base transition shadow-xl"
                >
                    <span>Explorar Refacciones por Marca</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 4. SECCIÓN: FILOSOFÍA INSTITUCIONAL Y QUIÉNES SOMOS (Ancla #nosotros) -->
    <section id="nosotros" class="bg-white py-16 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-primary/10 text-primary border border-primary/20 mb-3">
                    Identidad y Respaldo
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-dark tracking-tight">
                    Nuestra Filosofía Empresarial
                </h2>
                <p class="text-gray-600 text-sm sm:text-base mt-2">
                    {{ $companySettings['company_slogan'] ?? 'Soluciones exactas que le devuelven la vida a tu auto.' }}
                </p>
            </div>

            <!-- Misión y Visión -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12 max-w-5xl mx-auto">
                <!-- Misión -->
                <div class="bg-surface rounded-2xl p-8 border border-gray-200 shadow-sm relative overflow-hidden group hover:border-primary transition">
                    <div class="w-12 h-12 rounded-xl bg-primary text-white flex items-center justify-center font-black text-xl mb-5 shadow">
                        🎯
                    </div>
                    <h3 class="text-xl font-black text-dark mb-3">Nuestra Misión</h3>
                    <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
                        {{ $companySettings['company_mission'] ?? 'Encontrar la solución exacta para cada vehículo que llega a nuestras manos. Nos apasiona devolverle la movilidad a nuestros clientes brindándoles la refacción precisa, asesoría técnica experta y la tranquilidad de una garantía real que genera absoluta confianza.' }}
                    </p>
                </div>

                <!-- Visión -->
                <div class="bg-surface rounded-2xl p-8 border border-gray-200 shadow-sm relative overflow-hidden group hover:border-primary transition">
                    <div class="w-12 h-12 rounded-xl bg-dark text-white flex items-center justify-center font-black text-xl mb-5 shadow">
                        🚀
                    </div>
                    <h3 class="text-xl font-black text-dark mb-3">Nuestra Visión</h3>
                    <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
                        {{ $companySettings['company_vision'] ?? 'Ser la opción más eficaz y ágil para dar nueva vida a cualquier vehículo en la región, posicionándonos como el proveedor preferido en el mercado local y un referente nacional en e-commerce automotriz, respaldados por tecnología de vanguardia, procesos digitalizados y atención humana de excelencia.' }}
                    </p>
                </div>
            </div>

            <!-- Valores Institucionales (01 al 05) -->
            @if(!empty($valuesList))
                <div class="max-w-5xl mx-auto">
                    <h3 class="text-center text-xs font-black uppercase tracking-widest text-gray-400 mb-6">
                        Valores Institucionales
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        @foreach($valuesList as $val)
                            <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-xs hover:border-primary transition text-center sm:text-left">
                                <span class="text-primary font-black text-xs block mb-1">{{ $val['num'] ?? '•' }}</span>
                                <h4 class="font-extrabold text-dark text-sm leading-snug">{{ $val['title'] ?? '' }}</h4>
                                <p class="text-xs text-gray-500 mt-1 leading-normal">{{ $val['desc'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
