@extends('layouts.public', [
    'title' => 'Inicio — Autopartes y Refacciones Originales Usadas',
    'metaDescription' => 'Encuentra motores, transmisiones, colisión, suspensión y partes eléctricas originales usadas con garantía escrita de 7 a 30 días en JC Autopartes, Tepatitlán, Jalisco.',
    'canonicalUrl' => route('home'),
])

@php
    $business = config('business');
    $brandOptions = $brands ?? ['Nissan', 'Chevrolet', 'Toyota', 'Ford', 'Honda', 'Volkswagen'];
    $modelOptions = $models ?? [];
    $modelsByBrandMap = $modelsByBrand ?? [];
    $yearOptions = $years ?? range((int) date('Y'), 2008);
    $categoryCards = $categories ?? [
        ['slug' => 'Motores', 'name' => 'Motores Completos y 3/4', 'warranty' => '30 días de garantía', 'desc' => 'Motores verificados en compresión y funcionamiento listos para instalar.', 'count' => 6],
        ['slug' => 'Transmisiones', 'name' => 'Transmisiones Automáticas y Estándar', 'warranty' => '30 días de garantía', 'desc' => 'Cajas de velocidades automáticas, CVT y manuales revisadas.', 'count' => 6],
        ['slug' => 'Partes Internas', 'name' => 'Cabezas y Partes Internas', 'warranty' => '15 días de garantía', 'desc' => 'Cabezas de motor, cigüeñales, árboles de levas y múltiples.', 'count' => 6],
        ['slug' => 'Colision', 'name' => 'Carrocería y Colisión', 'warranty' => '7 días de garantía', 'desc' => 'Cofres, puertas, salpicaderas, fascias, faros, calaveras y espejos.', 'count' => 6],
        ['slug' => 'Suspension', 'name' => 'Suspensión y Dirección', 'warranty' => '7 días de garantía', 'desc' => 'Horquillas, amortiguadores, cajas de dirección, puentes y mangos.', 'count' => 6],
        ['slug' => 'Electrico', 'name' => 'Sistema Eléctrico y Electrónico', 'warranty' => '7 días de garantía', 'desc' => 'Alternadores, marchas, módulos, cuerpos de aceleración y compresores.', 'count' => 6],
    ];
    $facilityPhotos = [
        [
            'src' => asset('imagenes/local/fachada.jpg'),
            'alt' => 'Fachada principal de JC Autopartes en Tepatitlán de Morelos, Jalisco',
            'title' => 'Sucursal y Mostrador Principal',
            'caption' => 'Blvd. Lic. Anacleto González Flores Sur #2090, Tepatitlán',
        ],
        [
            'src' => asset('imagenes/local/fachada2.jpg'),
            'alt' => 'Vista exterior e ingreso a JC Autopartes en Tepatitlán',
            'title' => 'Acceso y Atención a Clientes',
            'caption' => 'Atención directa para entregas en mostrador y envíos',
        ],
        [
            'src' => asset('imagenes/local/almacen-1.jpg'),
            'alt' => 'Almacén organizado de refacciones y autopartes usadas en JC Autopartes',
            'title' => 'Almacén de Refacciones Clasificadas',
            'caption' => 'Piezas etiquetadas y resguardadas bajo techo',
        ],
        [
            'src' => asset('imagenes/local/almacen-2.jpg'),
            'alt' => 'Bodega de motores, colisión y autopartes en JC Autopartes',
            'title' => 'Área de Resguardo e Inventario',
            'caption' => 'Control de existencias reales listas para embarque',
        ],
    ];
@endphp

@section('content')
    <!-- Hero Principal con imagen de fondo hero-bg.jpg y overlay bg-black/60 -->
    <section
        class="relative bg-dark text-white border-b border-border bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ asset('imagenes/banner/hero-bg.jpg') }}');"
    >
        <!-- Overlay oscuro bg-black/60 -->
        <div class="absolute inset-0 bg-black/60"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <!-- Texto Principal -->
                <div class="lg:col-span-7 space-y-5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-base bg-black/60 border border-white/20 text-xs font-semibold uppercase tracking-wider text-gray-100">
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                        <span>Inventario Verificado · Tepatitlán, Jalisco</span>
                    </div>

                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight text-white">
                        Refacciones y Autopartes Originales Usadas con <span class="text-primary">Garantía Escrita</span>
                    </h1>

                    <p class="text-base sm:text-lg text-gray-200 max-w-2xl leading-relaxed">
                        Piezas originales de colisión, mecánica, motores y transmisiones para las marcas más comerciales en México. Existencias reales validadas y envíos a toda la República.
                    </p>

                    <div class="grid grid-cols-3 gap-3 pt-2 max-w-lg">
                        <div class="bg-black/60 border border-white/15 rounded-base p-3">
                            <span class="block text-xl sm:text-2xl font-extrabold text-primary">30 Días</span>
                            <span class="block text-xs text-gray-200 mt-0.5">Motores y Transmisiones</span>
                        </div>
                        <div class="bg-black/60 border border-white/15 rounded-base p-3">
                            <span class="block text-xl sm:text-2xl font-extrabold text-primary">15 Días</span>
                            <span class="block text-xs text-gray-200 mt-0.5">Partes Internas</span>
                        </div>
                        <div class="bg-black/60 border border-white/15 rounded-base p-3">
                            <span class="block text-xl sm:text-2xl font-extrabold text-primary">7 Días</span>
                            <span class="block text-xs text-gray-200 mt-0.5">Eléctrico y Colisión</span>
                        </div>
                    </div>
                </div>

                <!-- Buscador por Vehículo y Pieza (Marca / Modelo / Año / Categoría) -->
                <div class="lg:col-span-5">
                    <div
                        x-data="{
                            selectedBrand: '',
                            modelsByBrand: @js($modelsByBrandMap),
                            allModels: @js($modelOptions),
                            get availableModels() {
                                if (this.selectedBrand && this.modelsByBrand[this.selectedBrand]) {
                                    return this.modelsByBrand[this.selectedBrand];
                                }
                                return this.allModels;
                            }
                        }"
                        class="bg-white text-dark rounded-card p-6 shadow-lg border border-border"
                    >
                        <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
                            <div>
                                <h2 class="text-lg font-extrabold text-dark">Buscador por Vehículo</h2>
                                <p class="text-xs text-gray-600">Filtra refacciones compatibles con tu auto o camioneta</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-base bg-surface border border-border text-xs font-bold text-primary">
                                Catálogo
                            </span>
                        </div>

                        <form action="{{ route('catalog.index') }}" method="GET" class="space-y-3.5">
                            <div>
                                <label for="finder-q" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                                    Pieza o Número de Parte (SKU)
                                </label>
                                <input
                                    id="finder-q"
                                    type="text"
                                    name="q"
                                    placeholder="Ej. Motor, Alternador, Faro derecho, Caja..."
                                    class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                >
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="finder-brand" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                                        Marca del Vehículo
                                    </label>
                                    <select
                                        id="finder-brand"
                                        name="brand"
                                        x-model="selectedBrand"
                                        class="w-full rounded-base border border-border bg-surface px-3 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                    >
                                        <option value="">Todas las marcas</option>
                                        @foreach($brandOptions as $brand)
                                            <option value="{{ $brand }}">{{ $brand }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="finder-model" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                                        Modelo
                                    </label>
                                    <select
                                        id="finder-model"
                                        name="model"
                                        class="w-full rounded-base border border-border bg-surface px-3 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                    >
                                        <option value="">Todos los modelos</option>
                                        @foreach($modelOptions as $modelName)
                                            <option value="{{ $modelName }}">{{ $modelName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="finder-year" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                                        Año Modelo
                                    </label>
                                    <select
                                        id="finder-year"
                                        name="year"
                                        class="w-full rounded-base border border-border bg-surface px-3 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                    >
                                        <option value="">Cualquier año</option>
                                        @foreach($yearOptions as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="finder-category" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                                        Categoría
                                    </label>
                                    <select
                                        id="finder-category"
                                        name="category"
                                        class="w-full rounded-base border border-border bg-surface px-3 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                    >
                                        <option value="">Todas las categorías</option>
                                        @foreach($categoryCards as $cat)
                                            <option value="{{ $cat['slug'] }}">{{ $cat['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="w-full bg-primary hover:bg-primary-hover text-white font-bold py-3 px-4 rounded-base text-sm transition-colors flex items-center justify-center gap-2 shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <span>Buscar Refacciones Disponibles</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categorías Principales -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-primary">Líneas de Inventario</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-dark mt-1">Explora por Categoría de Pieza</h2>
            </div>
            <a href="{{ route('catalog.index') }}" class="text-sm font-bold text-primary hover:underline inline-flex items-center gap-1">
                <span>Ver todo el catálogo</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($categoryCards as $cat)
                <a href="{{ route('catalog.index', ['category' => $cat['slug']]) }}"
                   class="group bg-white rounded-card border border-border p-5 hover:border-primary transition-colors flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-base bg-surface border border-border text-xs font-bold text-dark">
                                {{ $cat['warranty'] }}
                            </span>
                            <span class="text-xs font-semibold text-primary group-hover:translate-x-0.5 transition-transform">
                                {{ isset($cat['count']) && $cat['count'] > 0 ? $cat['count'] . ' piezas' : 'Ver piezas' }} &rarr;
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-dark group-hover:text-primary transition-colors">
                            {{ $cat['name'] }}
                        </h3>
                        <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">
                            {{ $cat['desc'] }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <!-- Sección de Piezas Destacadas -->
    @if(!empty($featuredProducts) && count($featuredProducts) > 0)
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-primary">Recién Ingresadas</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-dark mt-1">Piezas Destacadas en Inventario</h2>
                </div>
                <a href="{{ route('catalog.index') }}" class="text-sm font-bold text-primary hover:underline">
                    Ir al listado filtrable &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($featuredProducts as $item)
                    @include('public.partials.product-card', ['product' => $item])
                @endforeach
            </div>
        </section>
    @endif

    <!-- Sección de Garantía Oficial y Envíos a Toda la República -->
    <section class="bg-white border-y border-border mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <span class="text-xs font-bold uppercase tracking-wider text-primary">Respaldo y Cobertura Nacional</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-dark mt-1">Garantía Oficial por Escrito y Envíos Seguros</h2>
            </div>

            <!-- Tarjetas de Plazos Oficiales (30, 15 y 7 días) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
                @foreach($business['warranties'] as $warranty)
                    <div class="bg-surface rounded-card border border-border p-6">
                        <div class="inline-flex items-center justify-center px-3 py-1 rounded-base bg-primary text-white text-xs font-extrabold uppercase tracking-wider mb-3">
                            Garantía {{ $warranty['days'] }} Días
                        </div>
                        <h3 class="text-lg font-extrabold text-dark">
                            {{ $warranty['title'] }}
                        </h3>
                        <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                            {{ $warranty['description'] }}
                        </p>
                    </div>
                @endforeach
            </div>

            <!-- Banners Visuales Oficiales: Garantía, Certificado Oficial y Envíos -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface rounded-card border border-border overflow-hidden shadow-sm">
                    <img
                        src="{{ asset('imagenes/banner/garantia.jpg') }}"
                        alt="Toda nuestra mercancía tiene garantía del 100% en JC Autopartes"
                        class="w-full h-72 sm:h-80 object-cover"
                        loading="lazy"
                    >
                    <div class="p-4">
                        <h3 class="text-sm font-extrabold text-dark">Garantía del 100% en Mercancía</h3>
                        <p class="text-xs text-gray-600 mt-1">Todas nuestras piezas son revisadas y marcadas antes de su entrega o embarque.</p>
                    </div>
                </div>

                <div class="bg-surface rounded-card border border-border overflow-hidden shadow-sm">
                    <img
                        src="{{ asset('imagenes/banner/garantia2.jpg') }}"
                        alt="Certificado de Garantía Oficial de JC Autopartes: 30, 15 y 7 días"
                        class="w-full h-72 sm:h-80 object-contain bg-white"
                        loading="lazy"
                    >
                    <div class="p-4">
                        <h3 class="text-sm font-extrabold text-dark">Certificado de Garantía Oficial</h3>
                        <p class="text-xs text-gray-600 mt-1">Póliza impresa con folio en cada compra (30, 15 y 7 días según tipo de pieza).</p>
                    </div>
                </div>

                <div class="bg-surface rounded-card border border-border overflow-hidden shadow-sm">
                    <img
                        src="{{ asset('imagenes/banner/envio.jpg') }}"
                        alt="Envíos de autopartes y refacciones a toda la República Mexicana"
                        class="w-full h-72 sm:h-80 object-cover"
                        loading="lazy"
                    >
                    <div class="p-4">
                        <h3 class="text-sm font-extrabold text-dark">Envíos a Toda la República</h3>
                        <p class="text-xs text-gray-600 mt-1">Despachamos desde Tepatitlán, Jalisco, con empaque protegido para piezas delicadas y pesadas.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Instalaciones Reales en Tepatitlán -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-primary">Negocio Establecido</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-dark mt-1">Conoce nuestras instalaciones en Tepatitlán</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Compra con total tranquilidad: contamos con mostrador físico, patio y almacén organizado en {{ $business['address'] }}
                </p>
            </div>
            <a href="{{ route('pages.contact') }}" class="text-sm font-bold text-primary hover:underline shrink-0">
                Ver ubicación y horarios &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($facilityPhotos as $photo)
                <figure class="bg-white rounded-card border border-border overflow-hidden shadow-sm flex flex-col">
                    <div class="aspect-[4/3] bg-surface overflow-hidden">
                        <img
                            src="{{ $photo['src'] }}"
                            alt="{{ $photo['alt'] }}"
                            class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        >
                    </div>
                    <figcaption class="p-4 flex-1 flex flex-col justify-between">
                        <strong class="text-sm font-bold text-dark block">{{ $photo['title'] }}</strong>
                        <span class="text-xs text-gray-600 mt-1 block">{{ $photo['caption'] }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>

        <!-- Cinta de Contacto y Mostrador Tepatitlán -->
        <div class="mt-10 bg-dark text-white rounded-card p-6 sm:p-8 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wider text-primary">Atención Directa en Mostrador y Envíos</span>
                <h3 class="text-xl sm:text-2xl font-extrabold">¿Buscas una pieza específica o cotización por WhatsApp?</h3>
                <p class="text-sm text-gray-300">
                    Visítanos en {{ $business['address'] }} o envíanos foto/muestra de tu refacción.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, busco cotizar una refacción.') }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="bg-whatsapp hover:brightness-95 text-white font-bold px-5 py-3 rounded-base text-sm transition-colors inline-flex items-center gap-2">
                    <span>WhatsApp {{ $business['whatsapp_display'] }}</span>
                </a>
                <a href="tel:{{ $business['phone_link'] }}"
                   class="bg-white/10 hover:bg-white/20 text-white font-semibold px-5 py-3 rounded-base text-sm transition-colors">
                    Llamar: {{ $business['phone_display'] }}
                </a>
            </div>
        </div>
    </section>
@endsection
