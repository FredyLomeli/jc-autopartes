@extends('layouts.public', [
    'title' => 'Checkout — Datos de Envío y Resumen',
    'metaDescription' => 'Completa tus datos de entrega y código postal para tu pedido en JC Autopartes.',
    'canonicalUrl' => route('checkout.index'),
])

@php
    $business = config('business');
    $items = $cart['items'] ?? [];
    $whatsappOrderLines = collect($items)->map(fn ($i) => "• {$i['quantity']}x {$i['title']} (SKU: {$i['sku']}) - {$i['formatted_line_subtotal']}")->implode("\n");
    $whatsappCheckoutText = "Hola JC Autopartes, quiero confirmar mi pedido web:\n{$whatsappOrderLines}\nSubtotal: {$cart['formattedSubtotal']}";
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
                Finalizar Compra — Datos de Envío y Resumen
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Ingresa tus datos de contacto y Código Postal de destino para preparar tu orden.
            </p>
        </div>
    </div>

    <div
        x-data="{
            deliveryMethod: 'shipping',
            postalCode: '',
            postalCodeConfirmed: false
        }"
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
    >
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Columna Izquierda: Captura de Datos y Flujo de Código Postal (Estilo UGS / AFR) -->
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
                                placeholder="Nombre y apellidos de quien recibe"
                                class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            >
                        </div>
                        <div>
                            <label for="checkout-phone" class="block text-xs font-bold uppercase text-gray-700 mb-1">Teléfono / WhatsApp (10 dígitos) *</label>
                            <input
                                id="checkout-phone"
                                type="tel"
                                placeholder="Ej. 33 1234 5678"
                                class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            >
                        </div>
                        <div>
                            <label for="checkout-email" class="block text-xs font-bold uppercase text-gray-700 mb-1">Correo electrónico *</label>
                            <input
                                id="checkout-email"
                                type="email"
                                placeholder="correo@ejemplo.com"
                                class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            >
                        </div>
                    </div>
                </section>

                <!-- Paso 2: Modalidad de Entrega y Código Postal -->
                <section class="bg-white rounded-card border border-border p-6 shadow-sm space-y-5">
                    <div class="flex items-center gap-2.5 border-b border-border pb-3">
                        <span class="w-6 h-6 rounded-full bg-primary text-white text-xs font-extrabold inline-flex items-center justify-center">2</span>
                        <h2 class="text-base font-extrabold text-dark">Modalidad de Entrega y Código Postal</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label
                            :class="deliveryMethod === 'shipping' ? 'border-primary bg-surface ring-1 ring-primary' : 'border-border bg-white'"
                            class="cursor-pointer rounded-base border p-4 flex items-start gap-3 transition-all"
                        >
                            <input type="radio" name="delivery_method" value="shipping" x-model="deliveryMethod" class="mt-1 text-primary focus:ring-primary">
                            <div>
                                <span class="block text-sm font-extrabold text-dark">Envío a Domicilio</span>
                                <span class="block text-xs text-gray-600 mt-0.5">Paquetería a toda la República Mexicana</span>
                            </div>
                        </label>

                        <label
                            :class="deliveryMethod === 'pickup' ? 'border-primary bg-surface ring-1 ring-primary' : 'border-border bg-white'"
                            class="cursor-pointer rounded-base border p-4 flex items-start gap-3 transition-all"
                        >
                            <input type="radio" name="delivery_method" value="pickup" x-model="deliveryMethod" class="mt-1 text-primary focus:ring-primary">
                            <div>
                                <span class="block text-sm font-extrabold text-dark">Recoger en Mostrador</span>
                                <span class="block text-xs text-gray-600 mt-0.5">Sucursal Tepatitlán, Jalisco (Sin costo)</span>
                            </div>
                        </label>
                    </div>

                    <!-- Bloque de Código Postal y Domicilio (cuando se elige envío) -->
                    <div x-show="deliveryMethod === 'shipping'" class="space-y-4 pt-2">
                        <!-- Estimador de envío por C.P. estilo UGS / AFR -->
                        <div class="bg-surface rounded-base border border-border p-4">
                            <label for="checkout-postal-code" class="block text-xs font-extrabold uppercase tracking-wide text-dark mb-1.5">
                                Código Postal de Destino (C.P.) *
                            </label>
                            <div class="flex flex-col sm:flex-row gap-2.5">
                                <input
                                    id="checkout-postal-code"
                                    type="text"
                                    name="postal_code"
                                    maxlength="5"
                                    x-model="postalCode"
                                    placeholder="Ingresa tu C.P. de 5 dígitos (ej. 44100)"
                                    class="flex-1 rounded-base border border-border bg-white px-3.5 py-2.5 text-sm font-bold text-dark focus:border-primary focus:ring-1 focus:ring-primary"
                                >
                                <button
                                    type="button"
                                    @click="postalCodeConfirmed = postalCode.trim().length === 5"
                                    class="bg-dark hover:bg-gray-800 text-white font-bold px-4 py-2.5 rounded-base text-xs uppercase tracking-wider transition-colors shrink-0"
                                >
                                    Validar C.P.
                                </button>
                            </div>
                            <p class="text-xs text-gray-600 mt-2">
                                La cotización automática de envío con Paquetexpress por peso y medidas se habilitará en la siguiente etapa.
                            </p>
                            <p x-cloak x-show="postalCodeConfirmed" class="text-xs font-bold text-green-700 mt-1.5">
                                ✓ C.P. <span x-text="postalCode"></span> registrado para tu orden de envío desde Tepatitlán (C.P. 47600).
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label for="checkout-street" class="block text-xs font-bold uppercase text-gray-700 mb-1">Calle y Número Exterior / Interior *</label>
                                <input id="checkout-street" type="text" placeholder="Av. / Calle y número" class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm">
                            </div>
                            <div>
                                <label for="checkout-colony" class="block text-xs font-bold uppercase text-gray-700 mb-1">Colonia *</label>
                                <input id="checkout-colony" type="text" placeholder="Colonia o fraccionamiento" class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm">
                            </div>
                            <div>
                                <label for="checkout-city" class="block text-xs font-bold uppercase text-gray-700 mb-1">Ciudad / Municipio *</label>
                                <input id="checkout-city" type="text" placeholder="Ciudad" class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm">
                            </div>
                            <div>
                                <label for="checkout-state" class="block text-xs font-bold uppercase text-gray-700 mb-1">Estado *</label>
                                <input id="checkout-state" type="text" placeholder="Ej. Jalisco, Nuevo León, CDMX..." class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm">
                            </div>
                            <div>
                                <label for="checkout-refs" class="block text-xs font-bold uppercase text-gray-700 mb-1">Referencias de entrega</label>
                                <input id="checkout-refs" type="text" placeholder="Entre calles, color de fachada o taller" class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm">
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
                        <span>Para factura, escríbenos por WhatsApp antes de realizar tu pago.</span>
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

            <!-- Columna Derecha: Resumen del Pedido y Botón de Pago Deshabilitado -->
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
                        <span>Costo de envío</span>
                        <span class="text-xs font-semibold text-gray-500">Pendiente de cotización por C.P.</span>
                    </div>
                    <div class="flex justify-between items-baseline pt-2 border-t border-border">
                        <span class="text-base font-extrabold text-dark">Total de Mercancía</span>
                        <span class="text-xl font-extrabold text-primary whitespace-nowrap">{{ $cart['formattedSubtotal'] }}</span>
                    </div>
                </div>

                <!-- Botón de Pago Deshabilitado (Requerimiento estricto Fase A0) -->
                <div class="space-y-3 pt-2">
                    <button
                        type="button"
                        disabled
                        class="w-full bg-gray-300 text-gray-600 font-extrabold py-3.5 px-5 rounded-base text-sm cursor-not-allowed border border-gray-300"
                    >
                        Disponible en la siguiente etapa
                    </button>

                    <p class="text-xs text-center text-gray-500">
                        El cobro en línea con Mercado Pago y cálculo de envío con Paquetexpress se activan en las siguientes fases.
                    </p>

                    <div class="border-t border-border pt-3">
                        <a
                            href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode($whatsappCheckoutText) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full bg-whatsapp hover:brightness-95 text-white font-extrabold py-3 px-5 rounded-base text-xs sm:text-sm transition-colors flex items-center justify-center gap-2 shadow-sm"
                        >
                            <span>Apartar / Confirmar este Pedido por WhatsApp ({{ $business['whatsapp_display'] }})</span>
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
