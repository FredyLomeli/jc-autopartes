@extends('layouts.public', [
    'title' => 'Checkout — Datos de Envío y Resumen',
    'metaDescription' => 'Completa tus datos de entrega y código postal para tu pedido en JC Autopartes.',
    'canonicalUrl' => route('checkout.index'),
])

@php
    $business = config('business');
    $items = $cart['items'] ?? [];
    $whatsappOrderLines = collect($items)->map(fn ($i) => "• {$i['quantity']}x {$i['title']} (SKU: {$i['sku']}) - {$i['formatted_line_subtotal']}")->implode("\n");
    $whatsappCheckoutText = "Hola JC Autopartes, quiero consultar sobre mi pedido web:\n{$whatsappOrderLines}\nSubtotal: {$cart['formattedSubtotal']}";
@endphp

@section('content')
    <div class="bg-white border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <nav aria-label="Breadcrumb" class="text-xs text-gray-500 mb-2">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-primary">Inicio</a></li>
                    <li>/</li>
                    <li><a href="{{ route('cart.index') }}" class="hover:text-primary">Carrito</a></li>
                    <li>/</li>
                    <li class="font-semibold text-dark" aria-current="page">Checkout</li>
                </ol>
            </nav>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-dark tracking-tight">
                Finalizar Compra — Datos de Envío y Pago
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Ingresa tus datos de entrega y procede a pagar de forma 100% segura mediante Mercado Pago.
            </p>
        </div>
    </div>

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-base p-4 text-sm flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <strong class="font-bold">Atención:</strong> {{ session('error') }}
                </div>
            </div>
        </div>
    @endif

    <div
        x-data="{
            deliveryMethod: '{{ old('delivery_method', 'shipping') }}',
            postalCode: '{{ old('shipping_postal_code', '') }}',
            postalCodeConfirmed: {{ old('shipping_postal_code') ? 'true' : 'false' }}
        }"
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
    >
        <form action="{{ route('checkout.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Columna Izquierda: Captura de Datos y Flujo de Entrega -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Paso 1: Datos del Comprador -->
                    <section class="bg-white rounded-card border border-border p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-2.5 border-b border-border pb-3">
                            <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-extrabold inline-flex items-center justify-center">1</span>
                            <h2 class="text-base font-extrabold text-dark">Datos de Contacto del Comprador</h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label for="checkout-name" class="block text-xs font-bold uppercase text-gray-700 mb-1">Nombre completo *</label>
                                <input
                                    id="checkout-name"
                                    type="text"
                                    name="customer_name"
                                    value="{{ old('customer_name') }}"
                                    required
                                    placeholder="Nombre y apellidos de quien recibe"
                                    class="w-full rounded-base border @error('customer_name') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                >
                                @error('customer_name')
                                    <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="checkout-phone" class="block text-xs font-bold uppercase text-gray-700 mb-1">Teléfono / WhatsApp (10 dígitos) *</label>
                                <input
                                    id="checkout-phone"
                                    type="tel"
                                    name="customer_phone"
                                    value="{{ old('customer_phone') }}"
                                    required
                                    placeholder="Ej. 33 1234 5678"
                                    class="w-full rounded-base border @error('customer_phone') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                >
                                @error('customer_phone')
                                    <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="checkout-email" class="block text-xs font-bold uppercase text-gray-700 mb-1">Correo electrónico *</label>
                                <input
                                    id="checkout-email"
                                    type="email"
                                    name="customer_email"
                                    value="{{ old('customer_email') }}"
                                    required
                                    placeholder="correo@ejemplo.com"
                                    class="w-full rounded-base border @error('customer_email') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                >
                                @error('customer_email')
                                    <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <!-- Paso 2: Modalidad de Entrega y Código Postal -->
                    <section class="bg-white rounded-card border border-border p-6 shadow-sm space-y-5">
                        <div class="flex items-center gap-2.5 border-b border-border pb-3">
                            <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-extrabold inline-flex items-center justify-center">2</span>
                            <h2 class="text-base font-extrabold text-dark">Modalidad de Entrega</h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label
                                :class="deliveryMethod === 'shipping' ? 'border-primary bg-surface ring-1 ring-primary' : 'border-border bg-white'"
                                class="cursor-pointer rounded-base border p-4 flex items-start gap-3 transition-all"
                            >
                                <input
                                    type="radio"
                                    name="delivery_method"
                                    value="shipping"
                                    x-model="deliveryMethod"
                                    class="mt-1 text-primary focus:ring-primary"
                                >
                                <div>
                                    <span class="block text-sm font-extrabold text-dark">Envío a Domicilio</span>
                                    <span class="block text-xs text-gray-600 mt-0.5">Paquetería a toda la República Mexicana</span>
                                </div>
                            </label>

                            <label
                                :class="deliveryMethod === 'pickup' ? 'border-primary bg-surface ring-1 ring-primary' : 'border-border bg-white'"
                                class="cursor-pointer rounded-base border p-4 flex items-start gap-3 transition-all"
                            >
                                <input
                                    type="radio"
                                    name="delivery_method"
                                    value="pickup"
                                    x-model="deliveryMethod"
                                    class="mt-1 text-primary focus:ring-primary"
                                >
                                <div>
                                    <span class="block text-sm font-extrabold text-dark">Recoger en Mostrador</span>
                                    <span class="block text-xs text-gray-600 mt-0.5">Sucursal Tepatitlán, Jalisco (Sin costo)</span>
                                </div>
                            </label>
                        </div>
                        @error('delivery_method')
                            <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror

                        <!-- Bloque de Domicilio de Envío (cuando se elige envío) -->
                        <div x-show="deliveryMethod === 'shipping'" class="space-y-4 pt-2">
                            <!-- Código Postal -->
                            <div class="bg-surface rounded-base border border-border p-4">
                                <label for="checkout-postal-code" class="block text-xs font-extrabold uppercase tracking-wide text-dark mb-1.5">
                                    Código Postal de Destino (C.P.) *
                                </label>
                                <div class="flex flex-col sm:flex-row gap-2.5">
                                    <input
                                        id="checkout-postal-code"
                                        type="text"
                                        name="shipping_postal_code"
                                        maxlength="5"
                                        x-model="postalCode"
                                        placeholder="Ingresa tu C.P. de 5 dígitos (ej. 47600)"
                                        class="flex-1 rounded-base border @error('shipping_postal_code') border-red-500 @else border-border @enderror bg-white px-3.5 py-2.5 text-sm font-bold text-dark focus:border-primary focus:ring-1 focus:ring-primary"
                                    >
                                    <button
                                        type="button"
                                        @click="postalCodeConfirmed = postalCode.trim().length === 5"
                                        class="bg-dark hover:bg-gray-800 text-white font-bold px-4 py-2.5 rounded-base text-xs uppercase tracking-wider transition-colors shrink-0"
                                    >
                                        Validar C.P.
                                    </button>
                                </div>
                                @error('shipping_postal_code')
                                    <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-600 mt-2">
                                    Envíos asegurados y empaquetados con protección para autopartes desde Tepatitlán de Morelos, Jalisco.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label for="checkout-street" class="block text-xs font-bold uppercase text-gray-700 mb-1">Calle y Número Exterior / Interior *</label>
                                    <input
                                        id="checkout-street"
                                        type="text"
                                        name="shipping_address"
                                        value="{{ old('shipping_address') }}"
                                        placeholder="Av. / Calle y número"
                                        class="w-full rounded-base border @error('shipping_address') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                                    >
                                    @error('shipping_address')
                                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="checkout-colony" class="block text-xs font-bold uppercase text-gray-700 mb-1">Colonia / Fraccionamiento *</label>
                                    <input
                                        id="checkout-colony"
                                        type="text"
                                        name="shipping_neighborhood"
                                        value="{{ old('shipping_neighborhood') }}"
                                        placeholder="Colonia o fraccionamiento"
                                        class="w-full rounded-base border @error('shipping_neighborhood') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                                    >
                                    @error('shipping_neighborhood')
                                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="checkout-city" class="block text-xs font-bold uppercase text-gray-700 mb-1">Ciudad / Municipio *</label>
                                    <input
                                        id="checkout-city"
                                        type="text"
                                        name="shipping_city"
                                        value="{{ old('shipping_city') }}"
                                        placeholder="Ciudad"
                                        class="w-full rounded-base border @error('shipping_city') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                                    >
                                    @error('shipping_city')
                                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="checkout-state" class="block text-xs font-bold uppercase text-gray-700 mb-1">Estado *</label>
                                    <input
                                        id="checkout-state"
                                        type="text"
                                        name="shipping_state"
                                        value="{{ old('shipping_state') }}"
                                        placeholder="Ej. Jalisco, Michoacán, CDMX..."
                                        class="w-full rounded-base border @error('shipping_state') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                                    >
                                    @error('shipping_state')
                                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="checkout-refs" class="block text-xs font-bold uppercase text-gray-700 mb-1">Referencias de entrega (opcional)</label>
                                    <input
                                        id="checkout-refs"
                                        type="text"
                                        name="shipping_notes"
                                        value="{{ old('shipping_notes') }}"
                                        placeholder="Entre calles, fachada o taller mecánico"
                                        class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Información de Recolección en Sucursal -->
                        <div x-cloak x-show="deliveryMethod === 'pickup'" class="bg-surface rounded-base border border-border p-4 text-sm text-gray-700 space-y-1">
                            <strong class="text-dark block">Sucursal de entrega en mostrador:</strong>
                            <p>{{ $business['address'] }}</p>
                            <p class="text-xs text-gray-500">{{ $business['hours']['weekdays'] }} · {{ $business['hours']['saturday'] }}</p>
                        </div>
                    </section>

                    <!-- Nota de Facturación -->
                    <div class="bg-white rounded-card border border-border p-4 text-xs sm:text-sm text-gray-700 flex items-center justify-between gap-4">
                        <div>
                            <strong class="text-dark">¿Requieres factura?</strong>
                            <span>Para factura fiscal, escríbenos por WhatsApp antes o después de tu pago.</span>
                        </div>
                        <a
                            href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, requiero información para facturar mi compra.') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-xs font-bold text-primary hover:underline shrink-0"
                        >
                            Solicitar por WhatsApp &rarr;
                        </a>
                    </div>
                </div>

                <!-- Columna Derecha: Resumen del Pedido y Botón de Pago Mercado Pago -->
                <aside class="lg:col-span-5 bg-white rounded-card border border-border p-6 shadow-sm space-y-5">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <h2 class="text-lg font-extrabold text-dark">Resumen de tu Orden</h2>
                        <a href="{{ route('cart.index') }}" class="text-xs font-bold text-primary hover:underline">
                            Editar carrito
                        </a>
                    </div>

                    <ul class="divide-y divide-border">
                        @foreach($items as $item)
                            <li class="py-3 flex items-start justify-between gap-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-bold text-dark leading-snug">{{ $item['title'] }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        SKU: {{ $item['sku'] }} · Cantidad: <strong>{{ $item['quantity'] }}</strong> · Garantía {{ $item['warranty_days'] }} días
                                    </p>
                                </div>
                                <span class="font-extrabold text-dark whitespace-nowrap shrink-0">
                                    {{ $item['formatted_line_subtotal'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-border pt-4 space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal ({{ $cart['totalQuantity'] }} {{ $cart['totalQuantity'] === 1 ? 'pieza' : 'piezas' }})</span>
                            <span class="font-bold text-dark whitespace-nowrap">{{ $cart['formattedSubtotal'] }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Modalidad de entrega</span>
                            <span class="text-xs font-bold text-dark" x-text="deliveryMethod === 'pickup' ? 'Recolección en Mostrador ($0.00)' : 'Envío a Domicilio'"></span>
                        </div>
                        <div class="flex justify-between items-baseline pt-2 border-t border-border">
                            <span class="text-base font-extrabold text-dark">Total a Pagar</span>
                            <span class="text-xl font-extrabold text-primary whitespace-nowrap">{{ $cart['formattedSubtotal'] }}</span>
                        </div>
                    </div>

                    <!-- Botón de Pago Activo con Mercado Pago -->
                    <div class="space-y-3 pt-2">
                        <button
                            type="submit"
                            class="w-full bg-[#009EE3] hover:bg-[#0081BA] text-white font-extrabold py-3.5 px-5 rounded-base text-sm transition-colors shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="none"/>
                                <path d="M21 11.11V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4.11a2 2 0 0 0 .5 1.41L7 16v3a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-3l3.5-3.48a2 2 0 0 0 .5-1.41zM7.5 7h9a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm7.5 11h-6v-1.5h6V18zm3.62-7.22l-3.12 3.1a1 1 0 0 0-.28.62H8.28a1 1 0 0 0-.28-.62L4.88 10.8A.5.5 0 0 1 5 10.5V10h14v.5a.5.5 0 0 1-.12.28z"/>
                            </svg>
                            <span>Pagar con Mercado Pago (Tarjetas, SPEI, OXXO)</span>
                        </button>

                        <div class="flex items-center justify-center gap-2 text-[11px] text-gray-500">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Transacción cifrada y protegida por Mercado Pago</span>
                        </div>

                        <div class="border-t border-border pt-3">
                            <a
                                href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode($whatsappCheckoutText) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full bg-whatsapp hover:brightness-95 text-white font-extrabold py-3 px-5 rounded-base text-xs sm:text-sm transition-colors flex items-center justify-center gap-2 shadow-sm"
                            >
                                <span>¿Prefieres atención personalizada? Escríbenos</span>
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>
@endsection
