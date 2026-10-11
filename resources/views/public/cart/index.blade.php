@extends('layouts.public', [
    'title' => 'Carrito de Compras',
    'metaDescription' => 'Revisa las refacciones y autopartes seleccionadas en tu carrito de compras en JC Autopartes.',
    'canonicalUrl' => route('cart.index'),
])

@php
    $business = config('business');
    $items = $cart['items'] ?? [];
@endphp

@section('content')
    <div class="bg-white border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <nav aria-label="Breadcrumb" class="text-xs text-gray-500 mb-2">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-primary">Inicio</a></li>
                    <li>/</li>
                    <li><a href="{{ route('catalog.index') }}" class="hover:text-primary">Catálogo</a></li>
                    <li>/</li>
                    <li class="font-semibold text-dark" aria-current="page">Carrito de Compras</li>
                </ol>
            </nav>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-dark tracking-tight">
                    Tu Carrito de Refacciones
                </h1>

                @if(count($items) > 0)
                    <form action="{{ route('cart.clear') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-bold text-gray-500 hover:text-primary transition-colors">
                            Vaciar carrito completo
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(count($items) > 0)
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Lista de Piezas en Carrito -->
                <div class="lg:col-span-8 space-y-4">
                    @foreach($items as $item)
                        <div class="bg-white rounded-card border border-border p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row gap-4 sm:items-center justify-between">
                            <div class="flex items-start gap-4">
                                <a href="{{ route('catalog.show', $item['slug']) }}" class="w-24 h-20 rounded-base bg-surface border border-border overflow-hidden shrink-0">
                                    <img
                                        src="{{ $item['photo_url'] }}"
                                        alt="{{ $item['title'] }}"
                                        class="w-full h-full object-cover"
                                    >
                                </a>

                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        <span class="font-mono font-bold text-gray-600 bg-surface px-2 py-0.5 rounded border border-border">
                                            SKU: {{ $item['sku'] }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded bg-[#111111]/90 text-white text-[11px] font-semibold">
                                            Condición {{ $item['condition'] }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded bg-[#E31221] text-white text-[11px] font-semibold">
                                            Garantía {{ $item['warranty_days'] }} días
                                        </span>
                                    </div>

                                    <h2 class="text-sm sm:text-base font-extrabold text-dark hover:text-primary transition-colors">
                                        <a href="{{ route('catalog.show', $item['slug']) }}">
                                            {{ $item['title'] }}
                                        </a>
                                    </h2>

                                    <p class="text-xs text-gray-600">
                                        Compatible: <strong class="text-dark">{{ $item['vehicle_label'] }}</strong>
                                    </p>

                                    <p class="text-xs font-semibold text-gray-500">
                                        Precio unitario: <span class="text-dark font-bold whitespace-nowrap">{{ $item['formatted_unit_price'] }}</span>
                                        · Disponibles en almacén: <span class="text-green-700 font-bold">{{ $item['available_stock'] }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Controles de Cantidad y Subtotal -->
                            <div class="flex sm:flex-col items-center sm:items-end justify-between gap-3 pt-3 sm:pt-0 border-t sm:border-t-0 border-border">
                                <div class="text-left sm:text-right">
                                    <span class="block text-[11px] text-gray-500 uppercase font-semibold">Subtotal</span>
                                    <span class="text-base sm:text-lg font-extrabold text-dark whitespace-nowrap">
                                        {{ $item['formatted_line_subtotal'] }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <form action="{{ route('cart.update', $item['product_id']) }}" method="POST" class="flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <label for="qty-{{ $item['product_id'] }}" class="sr-only">Cantidad</label>
                                        <select
                                            id="qty-{{ $item['product_id'] }}"
                                            name="quantity"
                                            onchange="this.form.submit()"
                                            class="rounded-base border border-border bg-surface pl-2.5 pr-7 py-1.5 text-xs font-bold text-dark focus:border-primary focus:ring-1 focus:ring-primary"
                                        >
                                            @for($i = 1; $i <= $item['available_stock']; $i++)
                                                <option value="{{ $i }}" @selected($item['quantity'] === $i)>
                                                    {{ $i }} {{ $i === 1 ? 'pza' : 'pzas' }}
                                                </option>
                                            @endfor
                                        </select>
                                        <noscript>
                                            <button type="submit" class="px-2 py-1 rounded bg-dark text-white text-xs font-bold">Actualizar</button>
                                        </noscript>
                                    </form>

                                    <form action="{{ route('cart.remove', $item['product_id']) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="p-1.5 rounded-base border border-border text-gray-500 hover:text-primary hover:border-primary transition-colors"
                                            title="Quitar del carrito"
                                            aria-label="Quitar {{ $item['title'] }} del carrito"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Resumen del Carrito (Estilo UGS / AFR Autopartes) -->
                <aside class="lg:col-span-4 bg-white rounded-card border border-border p-6 shadow-sm space-y-5">
                    <h2 class="text-lg font-extrabold text-dark border-b border-border pb-3">
                        Resumen del Pedido
                    </h2>

                    <div class="space-y-2.5 text-sm">
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Total de piezas</span>
                            <span class="font-bold text-dark">{{ $cart['totalQuantity'] }} {{ $cart['totalQuantity'] === 1 ? 'pieza' : 'piezas' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Subtotal de mercancía</span>
                            <span class="font-bold text-dark whitespace-nowrap">{{ $cart['formattedSubtotal'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Envío por paquetería</span>
                            <span class="text-xs font-semibold text-gray-500">Se ingresa C.P. en Checkout</span>
                        </div>
                    </div>

                    <div class="border-t border-border pt-4 flex items-baseline justify-between">
                        <span class="text-sm font-extrabold uppercase text-dark">Total parcial</span>
                        <span class="text-xl font-extrabold text-primary whitespace-nowrap">{{ $cart['formattedSubtotal'] }}</span>
                    </div>

                    <div class="space-y-2.5 pt-2">
                        <a
                            href="{{ route('checkout.index') }}"
                            class="w-full bg-primary hover:bg-primary-hover text-white font-extrabold py-3 px-5 rounded-base text-sm transition-colors flex items-center justify-center gap-2 shadow-sm"
                        >
                            <span>Continuar al Checkout</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>

                        <a
                            href="{{ route('catalog.index') }}"
                            class="w-full bg-surface hover:bg-gray-200/70 border border-border text-dark font-bold py-2.5 px-5 rounded-base text-xs transition-colors flex items-center justify-center"
                        >
                            Seguir buscando refacciones
                        </a>
                    </div>

                    <div class="bg-surface rounded-base border border-border p-3.5 text-xs text-gray-600 space-y-1">
                        <p class="font-bold text-dark">✓ Existencias verificadas en servidor</p>
                        <p>Todas tus piezas incluyen Certificado de Garantía Oficial de 7 a 30 días.</p>
                    </div>
                </aside>
            </div>
        @else
            <!-- Estado vacío del carrito -->
            <div class="bg-white rounded-card border border-border p-10 sm:p-14 text-center max-w-2xl mx-auto shadow-sm">
                <div class="w-14 h-14 rounded-full bg-surface border border-border flex items-center justify-center mx-auto mb-4 text-primary">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-extrabold text-dark">Tu carrito de compras está vacío</h2>
                <p class="text-sm text-gray-600 mt-1.5">
                    Explora nuestro catálogo de motores, transmisiones, colisión, suspensión y partes eléctricas originales con garantía escrita.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('catalog.index') }}" class="bg-primary hover:bg-primary-hover text-white font-bold px-6 py-3 rounded-base text-sm transition-colors">
                        Explorar Catálogo de Refacciones
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
