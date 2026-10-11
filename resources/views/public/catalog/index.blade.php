@extends('layouts.public', [
    'title' => 'Catálogo de Refacciones y Autopartes Originales',
    'metaDescription' => 'Explora nuestro catálogo de motores, transmisiones, colisión, suspensión y partes eléctricas originales usadas. Filtra por vehículo, año, categoría y condición.',
    'canonicalUrl' => $canonicalUrl ?? route('catalog.index'),
])

@php
    $business = config('business');
    $activeFiltersCount = collect($filters ?? [])->except(['sort'])->filter(fn ($v) => $v !== null && $v !== '')->count();
@endphp

@section('content')
    <!-- Encabezado de Catálogo y Breadcrumb -->
    <div class="bg-white border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <nav aria-label="Breadcrumb" class="text-xs text-gray-500 mb-2">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-primary">Inicio</a></li>
                    <li>/</li>
                    <li class="font-semibold text-dark" aria-current="page">Catálogo de Refacciones</li>
                </ol>
            </nav>

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-dark tracking-tight">
                        Catálogo de Refacciones y Autopartes Originales
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Existencias verificadas en almacén y respaldadas con garantía escrita de 7 a 30 días.
                    </p>
                </div>
                <div class="text-xs sm:text-sm font-semibold text-gray-700 bg-surface px-3.5 py-2 rounded-base border border-border self-start md:self-auto">
                    Mostrando <span class="font-extrabold text-dark">{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</span>
                    de <span class="font-extrabold text-primary">{{ $products->total() }}</span> piezas disponibles
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal del Catálogo (Sidebar de Filtros + Grid) -->
    <div x-data="{ filtersOpen: false }" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Botón de Filtros en Móvil -->
        <div class="lg:hidden mb-4 flex items-center justify-between gap-3">
            <button
                type="button"
                @click="filtersOpen = !filtersOpen"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-base bg-dark text-white text-sm font-bold"
            >
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span>Filtrar Refacciones</span>
                @if($activeFiltersCount > 0)
                    <span class="px-2 py-0.5 rounded-full bg-primary text-white text-xs">{{ $activeFiltersCount }}</span>
                @endif
            </button>

            @if($activeFiltersCount > 0)
                <a href="{{ route('catalog.index') }}" class="text-xs font-bold text-primary hover:underline">
                    Limpiar filtros
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Panel Lateral de Filtros (Estilo UGS / AFR Autopartes) -->
            <aside
                :class="filtersOpen ? 'block' : 'hidden lg:block'"
                class="lg:col-span-3 bg-white rounded-card border border-border p-5 shadow-sm"
                aria-label="Filtros de catálogo"
            >
                <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
                    <h2 class="text-base font-extrabold text-dark">Filtros de Búsqueda</h2>
                    @if($activeFiltersCount > 0)
                        <a href="{{ route('catalog.index') }}" class="text-xs font-bold text-primary hover:underline">
                            Restablecer
                        </a>
                    @endif
                </div>

                <form action="{{ route('catalog.index') }}" method="GET" class="space-y-4">
                    <!-- Palabra clave o SKU -->
                    <div>
                        <label for="filter-q" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                            Pieza o SKU
                        </label>
                        <input
                            id="filter-q"
                            type="text"
                            name="q"
                            value="{{ $filters['q'] ?? '' }}"
                            placeholder="Ej. Motor, Alternador, JC-NIS..."
                            class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        >
                    </div>

                    <!-- Categoría -->
                    <div>
                        <label for="filter-category" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                            Categoría
                        </label>
                        <select
                            id="filter-category"
                            name="category"
                            class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat['slug'] }}" @selected(($filters['category'] ?? '') === $cat['slug'])>
                                    {{ $cat['name'] }} ({{ $cat['count'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Marca del Vehículo -->
                    <div>
                        <label for="filter-brand" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                            Marca
                        </label>
                        <select
                            id="filter-brand"
                            name="brand"
                            class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Todas las marcas</option>
                            @foreach($brands as $brandName)
                                <option value="{{ $brandName }}" @selected(($filters['brand'] ?? '') === $brandName)>
                                    {{ $brandName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Modelo del Vehículo -->
                    <div>
                        <label for="filter-model" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                            Modelo
                        </label>
                        <select
                            id="filter-model"
                            name="model"
                            class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Todos los modelos</option>
                            @foreach($models as $modelName)
                                <option value="{{ $modelName }}" @selected(($filters['model'] ?? '') === $modelName)>
                                    {{ $modelName }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Año Compatible -->
                    <div>
                        <label for="filter-year" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                            Año del Vehículo
                        </label>
                        <select
                            id="filter-year"
                            name="year"
                            class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Cualquier año</option>
                            @foreach($years as $yr)
                                <option value="{{ $yr }}" @selected((string) ($filters['year'] ?? '') === (string) $yr)>
                                    {{ $yr }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Condición (A / B / C) -->
                    <div>
                        <label for="filter-condition" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                            Condición de la Pieza
                        </label>
                        <select
                            id="filter-condition"
                            name="condition"
                            class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Todas las condiciones</option>
                            @foreach($conditions as $code => $label)
                                <option value="{{ $code }}" @selected(($filters['condition'] ?? '') === $code)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rango de Precio -->
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                            Rango de Precio (MXN)
                        </span>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="filter-min-price" class="sr-only">Precio mínimo</label>
                                <input
                                    id="filter-min-price"
                                    type="number"
                                    name="min_price"
                                    min="0"
                                    step="100"
                                    value="{{ $filters['min_price'] ?? '' }}"
                                    placeholder="Mín $"
                                    class="w-full rounded-base border border-border bg-surface px-2.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                >
                            </div>
                            <div>
                                <label for="filter-max-price" class="sr-only">Precio máximo</label>
                                <input
                                    id="filter-max-price"
                                    type="number"
                                    name="max_price"
                                    min="0"
                                    step="100"
                                    value="{{ $filters['max_price'] ?? '' }}"
                                    placeholder="Máx $"
                                    class="w-full rounded-base border border-border bg-surface px-2.5 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Ordenamiento -->
                    <div>
                        <label for="filter-sort" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1">
                            Ordenar por
                        </label>
                        <select
                            id="filter-sort"
                            name="sort"
                            class="w-full rounded-base border border-border bg-surface px-3 py-2 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                        >
                            <option value="recent" @selected(($filters['sort'] ?? 'recent') === 'recent')>Destacados y recientes</option>
                            <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Precio: menor a mayor</option>
                            <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Precio: mayor a menor</option>
                            <option value="title_asc" @selected(($filters['sort'] ?? '') === 'title_asc')>Nombre: A–Z</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-primary hover:bg-primary-hover text-white font-bold py-2.5 px-4 rounded-base text-sm transition-colors"
                    >
                        Aplicar Filtros
                    </button>
                </form>
            </aside>

            <!-- Listado de Resultados -->
            <div class="lg:col-span-9">
                <!-- Chips de Filtros Activos -->
                @if($activeFiltersCount > 0)
                    <div class="bg-white rounded-card border border-border p-3.5 mb-5 flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-gray-600 mr-1">Filtros activos:</span>
                        @foreach(['q' => 'Búsqueda', 'category' => 'Categoría', 'brand' => 'Marca', 'model' => 'Modelo', 'year' => 'Año', 'condition' => 'Condición', 'min_price' => 'Mín $', 'max_price' => 'Máx $'] as $key => $label)
                            @if(!empty($filters[$key]))
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-base bg-surface border border-border text-xs font-semibold text-dark">
                                    <span class="text-gray-500">{{ $label }}:</span> {{ $filters[$key] }}
                                </span>
                            @endif
                        @endforeach
                        <a href="{{ route('catalog.index') }}" class="ml-auto text-xs font-bold text-primary hover:underline">
                            Quitar todos los filtros &times;
                        </a>
                    </div>
                @endif

                @if($products->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($products as $product)
                            @include('public.partials.product-card', ['product' => $product])
                        @endforeach
                    </div>

                    <!-- Paginación del Servidor conservando Query String -->
                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @else
                    <!-- Estado Vacío Cuidado -->
                    <div class="bg-white rounded-card border border-border p-8 sm:p-12 text-center">
                        <div class="w-12 h-12 rounded-full bg-surface border border-border flex items-center justify-center mx-auto mb-4 text-primary">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-lg font-extrabold text-dark">No encontramos piezas con esos filtros exactos</h2>
                        <p class="text-sm text-gray-600 mt-1 max-w-md mx-auto">
                            Contamos con más inventario en patio y almacén en Tepatitlán que aún está en proceso de catalogación.
                        </p>
                        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                            <a href="{{ route('catalog.index') }}" class="px-4 py-2.5 rounded-base bg-dark text-white text-xs sm:text-sm font-bold hover:bg-gray-800 transition-colors">
                                Ver todo el catálogo
                            </a>
                            <a href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, busco una refacción que no encontré en el catálogo web.') }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="px-4 py-2.5 rounded-base bg-whatsapp text-white text-xs sm:text-sm font-bold hover:brightness-95 transition-colors">
                                Preguntar por WhatsApp {{ $business['whatsapp_display'] }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
