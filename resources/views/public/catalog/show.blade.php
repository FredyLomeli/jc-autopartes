@extends('layouts.public', [
    'title' => "{$product->title} ({$product->sku})",
    'metaDescription' => "{$product->title} para {$product->vehicle_brand} {$product->vehicle_model} ({$product->year_start}-{$product->year_end}). Condición {$product->condition}, garantía escrita de {$product->warrantyDays()} días en JC Autopartes.",
    'canonicalUrl' => $canonicalUrl ?? route('catalog.show', $product->slug),
    'ogType' => 'product',
    'ogImage' => $product->primary_photo_url,
])

@php
    $business = config('business');
    $photos = $product->photos->count() > 0
        ? $product->photos->map(fn ($p) => ['url' => $p->url, 'alt' => $p->alt_text ?: $product->title])->values()->all()
        : [['url' => $product->primary_photo_url, 'alt' => $product->title]];
    $warrantyDays = $product->warrantyDays();
@endphp

@push('jsonld')
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
    <!-- Breadcrumbs Semánticos -->
    <div class="bg-white border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5">
            <nav aria-label="Breadcrumb" class="text-xs text-gray-500">
                <ol class="flex flex-wrap items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-primary">Inicio</a></li>
                    <li>/</li>
                    <li><a href="{{ route('catalog.index') }}" class="hover:text-primary">Catálogo</a></li>
                    <li>/</li>
                    <li>
                        <a href="{{ route('catalog.index', ['category' => $product->category]) }}" class="hover:text-primary">
                            {{ $product->category }}
                        </a>
                    </li>
                    <li>/</li>
                    <li class="font-semibold text-dark truncate max-w-xs" aria-current="page">{{ $product->title }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Ficha Principal del Producto -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-card border border-border p-6 lg:p-8 shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Galería Interactiva con Alpine.js -->
                <div
                    x-data="{
                        photos: @js($photos),
                        activeIndex: 0,
                        get activePhoto() {
                            return this.photos[this.activeIndex] || this.photos[0];
                        }
                    }"
                    class="lg:col-span-6 space-y-4"
                >
                    <div class="aspect-[4/3] rounded-card border border-border bg-surface overflow-hidden relative">
                        <img
                            :src="activePhoto.url"
                            :alt="activePhoto.alt"
                            src="{{ $photos[0]['url'] }}"
                            alt="{{ $photos[0]['alt'] }}"
                            class="w-full h-full object-cover bg-white"
                        >
                        <div class="absolute top-3 left-3 z-10 flex gap-1.5 pointer-events-none">
                            <span class="bg-[#111111]/90 text-white px-2.5 py-1 rounded text-xs font-semibold shadow-sm">
                                Condición {{ $product->condition }}
                            </span>
                            <span class="bg-[#E31221] text-white font-semibold px-2.5 py-1 rounded text-xs shadow-sm">
                                Garantía {{ $warrantyDays }} Días
                            </span>
                        </div>
                    </div>

                    @if(count($photos) > 1)
                        <div class="grid grid-cols-4 sm:grid-cols-5 gap-3">
                            @foreach($photos as $idx => $photo)
                                <button
                                    type="button"
                                    @click="activeIndex = {{ $idx }}"
                                    :class="activeIndex === {{ $idx }} ? 'border-primary ring-2 ring-primary/30' : 'border-border hover:border-dark'"
                                    class="aspect-[4/3] rounded-base border bg-surface overflow-hidden p-1 transition-all"
                                >
                                    <img
                                        src="{{ $photo['url'] }}"
                                        alt="{{ $photo['alt'] }}"
                                        class="w-full h-full object-cover rounded-sm"
                                    >
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Columna de Información Comercial, Existencia, Carrito y WhatsApp -->
                <div class="lg:col-span-6 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-primary">
                                {{ $product->category }} · {{ $product->vehicle_brand }}
                            </span>
                            <span class="font-mono text-xs font-bold bg-surface px-3 py-1 rounded-base border border-border text-dark">
                                SKU: {{ $product->sku }}
                            </span>
                        </div>

                        <!-- Único H1 de la página -->
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-dark tracking-tight leading-snug">
                            {{ $product->title }}
                        </h1>

                        <!-- Precio y Estado de Existencia (PriceService + StockService) -->
                        <div class="bg-surface rounded-card border border-border p-4 flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <span class="block text-xs font-semibold uppercase text-gray-500">Precio de Contado</span>
                                <span class="text-2xl sm:text-3xl font-extrabold text-dark">{{ $formattedPrice }}</span>
                            </div>
                            <div class="text-right">
                                @if($hasStock)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-base bg-green-100 text-green-800 text-xs font-extrabold">
                                        <span class="w-2 h-2 rounded-full bg-green-600"></span>
                                        En existencia ({{ $availableStock }} {{ $availableStock === 1 ? 'pieza' : 'piezas' }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-base bg-red-100 text-red-800 text-xs font-extrabold">
                                        <span class="w-2 h-2 rounded-full bg-red-600"></span>
                                        Agotado temporalmente
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Resumen de Compatibilidad y Condición -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="p-3.5 rounded-base border border-border bg-white">
                                <span class="block text-xs font-bold uppercase text-gray-500">Vehículo Compatible</span>
                                <strong class="text-dark font-bold mt-0.5 block">
                                    {{ $product->vehicle_brand }} {{ $product->vehicle_model }} ({{ $product->year_start }}–{{ $product->year_end }})
                                </strong>
                            </div>
                            <div class="p-3.5 rounded-base border border-border bg-white">
                                <span class="block text-xs font-bold uppercase text-gray-500">Estado de la Pieza</span>
                                <strong class="text-dark font-bold mt-0.5 block">
                                    {{ $product->conditionDescription() }}
                                </strong>
                            </div>
                        </div>

                        <!-- Descripción -->
                        @if($product->description)
                            <div class="text-sm text-gray-700 leading-relaxed border-t border-border pt-4">
                                <h2 class="text-xs font-bold uppercase tracking-wider text-dark mb-1.5">Descripción de la Refacción</h2>
                                <p>{{ $product->description }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Acciones: Agregar al Carrito y Botón de WhatsApp -->
                    <div class="space-y-3 pt-4 border-t border-border">
                        @if($hasStock)
                            <form action="{{ route('cart.add') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">

                                <div class="sm:w-36">
                                    <label for="qty-select" class="sr-only">Cantidad</label>
                                    <select
                                        id="qty-select"
                                        name="quantity"
                                        class="w-full rounded-base border border-border bg-surface px-3 py-3 text-sm font-bold text-dark focus:border-primary focus:ring-1 focus:ring-primary"
                                    >
                                        @for($i = 1; $i <= $availableStock; $i++)
                                            <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'pieza' : 'piezas' }}</option>
                                        @endfor
                                    </select>
                                </div>

                                <button
                                    type="submit"
                                    class="flex-1 bg-primary hover:bg-primary-hover text-white font-extrabold py-3 px-6 rounded-base text-sm transition-colors inline-flex items-center justify-center gap-2 shadow-sm"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span>Agregar al Carrito</span>
                                </button>
                            </form>
                        @else
                            <button
                                type="button"
                                disabled
                                class="w-full bg-gray-200 text-gray-500 font-bold py-3 px-6 rounded-base text-sm cursor-not-allowed"
                            >
                                Sin existencia disponible para carrito
                            </button>
                        @endif

                        <!-- Botón Directo de WhatsApp con el nombre y SKU de la pieza -->
                        <a
                            href="{{ $whatsappUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full bg-whatsapp hover:brightness-95 text-white font-extrabold py-3 px-6 rounded-base text-sm transition-colors inline-flex items-center justify-center gap-2 shadow-sm"
                        >
                            <span>Preguntar por esta pieza en WhatsApp ({{ $business['whatsapp_display'] }})</span>
                        </a>
                    </div>

                    <!-- Sello de Garantía Oficial y Envío -->
                    <div class="bg-surface rounded-base border border-border p-4 text-xs text-gray-700 space-y-1.5">
                        <p class="font-bold text-dark">
                            ✓ Garantía Oficial por Escrito de {{ $warrantyDays }} días naturales incluida en tu compra.
                        </p>
                        <p>
                            ✓ Envíos a todo México desde nuestra bodega en {{ $business['city'] }} o recolección directa en mostrador.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Especificaciones Técnicas y Dimensiones de Envío -->
            <div class="mt-10 pt-8 border-t border-border">
                <h2 class="text-lg font-extrabold text-dark mb-4">Ficha Técnica y Compatibilidad</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-surface rounded-card border border-border p-5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-primary mb-3">Datos de Identificación y Vehículo</h3>
                        <dl class="divide-y divide-border text-sm">
                            <div class="py-2 flex justify-between">
                                <dt class="text-gray-600">Número de Parte / SKU Interno</dt>
                                <dd class="font-mono font-bold text-dark">{{ $product->sku }}</dd>
                            </div>
                            @if($product->part_number)
                                <div class="py-2 flex justify-between">
                                    <dt class="text-gray-600">Número de Parte del Fabricante</dt>
                                    <dd class="font-mono font-bold text-dark">{{ $product->part_number }}</dd>
                                </div>
                            @endif
                            @if($product->oem_number)
                                <div class="py-2 flex justify-between">
                                    <dt class="text-gray-600">Número de Parte OEM</dt>
                                    <dd class="font-mono font-bold text-dark">{{ $product->oem_number }}</dd>
                                </div>
                            @endif
                            @if($product->sideAndPositionLabel())
                                <div class="py-2 flex justify-between">
                                    <dt class="text-gray-600">Lado / Posición</dt>
                                    <dd class="font-semibold text-primary">{{ $product->sideAndPositionLabel() }}</dd>
                                </div>
                            @endif
                            <div class="py-2 flex justify-between">
                                <dt class="text-gray-600">Categoría</dt>
                                <dd class="font-semibold text-dark">{{ $product->category }}</dd>
                            </div>
                            <div class="py-2 flex justify-between">
                                <dt class="text-gray-600">Marca Vehicular</dt>
                                <dd class="font-semibold text-dark">{{ $product->vehicle_brand }}</dd>
                            </div>
                            <div class="py-2 flex justify-between">
                                <dt class="text-gray-600">Modelo Compatible</dt>
                                <dd class="font-semibold text-dark">{{ $product->vehicle_model }}</dd>
                            </div>
                            <div class="py-2 flex justify-between">
                                <dt class="text-gray-600">Rango de Años</dt>
                                <dd class="font-semibold text-dark">{{ $product->year_start }} al {{ $product->year_end }}</dd>
                            </div>
                            @if($product->mercadolibre_permalink)
                                <div class="py-2 flex justify-between items-center">
                                    <dt class="text-gray-600">Mercado Libre</dt>
                                    <dd>
                                        <a href="{{ $product->mercadolibre_permalink }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs font-bold text-yellow-900 bg-yellow-100 hover:bg-yellow-200 border border-yellow-300 px-2 py-0.5 rounded transition-colors">
                                            <span>Ver publicación en Mercado Libre</span>
                                            <span>↗</span>
                                        </a>
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    <div class="bg-surface rounded-card border border-border p-5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-primary mb-3">Dimensiones para Embalaje y Garantía</h3>
                        <dl class="divide-y divide-border text-sm">
                            <div class="py-2 flex justify-between">
                                <dt class="text-gray-600">Peso Estimado</dt>
                                <dd class="font-semibold text-dark">{{ $product->weight_kg ? "{$product->weight_kg} kg" : 'Por confirmar en almacén' }}</dd>
                            </div>
                            <div class="py-2 flex justify-between">
                                <dt class="text-gray-600">Dimensiones (Largo × Ancho × Alto)</dt>
                                <dd class="font-semibold text-dark">
                                    @if($product->length_cm && $product->width_cm && $product->height_cm)
                                        {{ $product->length_cm }} × {{ $product->width_cm }} × {{ $product->height_cm }} cm
                                    @else
                                        Estándar de paquetería
                                    @endif
                                </dd>
                            </div>
                            <div class="py-2 flex justify-between">
                                <dt class="text-gray-600">Condición Física</dt>
                                <dd class="font-semibold text-dark">Grado {{ $product->condition }}</dd>
                            </div>
                            <div class="py-2 flex justify-between">
                                <dt class="text-gray-600">Cobertura de Garantía</dt>
                                <dd class="font-bold text-primary">{{ $warrantyDays }} días naturales por escrito</dd>
                            </div>
                            <div class="py-2 flex justify-between">
                                <dt class="text-gray-600">Ubicación de Salida</dt>
                                <dd class="font-semibold text-dark">Tepatitlán de Morelos, Jal. (C.P. 47600)</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Refacciones Relacionadas -->
        @if(!empty($relatedProducts) && count($relatedProducts) > 0)
            <section class="mt-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-dark">Otras Refacciones Compatibles o Relacionadas</h2>
                    <a href="{{ route('catalog.index', ['brand' => $product->vehicle_brand]) }}" class="text-sm font-bold text-primary hover:underline">
                        Ver más de {{ $product->vehicle_brand }} &rarr;
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $related)
                        @include('public.partials.product-card', ['product' => $related])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
