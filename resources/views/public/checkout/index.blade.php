@extends('layouts.public', [
    'title' => 'Checkout — Datos de Envío y Pago',
    'metaDescription' => 'Completa tu pedido de autopartes en JC Autopartes de forma rápida y segura en 3 pasos.',
    'canonicalUrl' => route('checkout.index'),
])

@php
    $business = config('business');
    $items = $cart['items'] ?? [];
    $whatsappOrderLines = collect($items)->map(fn ($i) => "• {$i['quantity']}x {$i['title']} (SKU: {$i['sku']}) - {$i['formatted_line_subtotal']}")->implode("\n");
    $whatsappCheckoutText = "Hola JC Autopartes, quiero consultar sobre mi pedido web:\n{$whatsappOrderLines}\nTotal: " . ($cart['formattedTotal'] ?? $cart['formattedSubtotal']);
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
                Finalizar Compra — Pasarela Segura
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Completa tus datos de entrega en 3 etapas sencillas y procesa tu pago mediante Mercado Pago.
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
            step: {{ $errors->hasAny(['delivery_method', 'shipping_postal_code', 'shipping_address', 'shipping_neighborhood', 'shipping_city', 'shipping_state']) ? 2 : 1 }},
            deliveryMethod: '{{ old('delivery_method', 'shipping') }}',
            postalCode: '{{ old('shipping_postal_code', '') }}',
            postalCodeConfirmed: {{ old('shipping_postal_code') ? 'true' : 'false' }},
            customerName: '{{ old('customer_name', '') }}',
            customerPhone: '{{ old('customer_phone', '') }}',
            customerEmail: '{{ old('customer_email', '') }}',
            step1Valid() {
                return this.customerName.trim().length >= 3 && this.customerPhone.trim().length >= 10 && this.customerEmail.includes('@');
            },
            step2Valid() {
                if (this.deliveryMethod === 'pickup') return true;
                return this.postalCode.trim().length === 5;
            },
            goToStep(target) {
                if (target === 2 && !this.step1Valid()) {
                    alert('Por favor ingresa tu nombre completo, teléfono de 10 dígitos y correo electrónico válido.');
                    return;
                }
                if (target === 3) {
                    if (!this.step1Valid()) {
                        this.step = 1;
                        alert('Completa tus datos de contacto primero.');
                        return;
                    }
                    if (!this.step2Valid()) {
                        alert('Ingresa tu Código Postal válido de 5 dígitos para entrega a domicilio.');
                        return;
                    }
                }
                this.step = target;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }"
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
    >
        <!-- Barra de Progreso del Stepper de 3 Etapas -->
        <div class="mb-10 max-w-2xl mx-auto">
            <div class="flex items-center justify-between relative">
                <!-- Línea de fondo -->
                <div class="absolute left-6 right-6 top-5 -translate-y-1/2 h-1 bg-gray-200 -z-0"></div>
                <!-- Línea de progreso activa -->
                <div
                    class="absolute left-6 top-5 -translate-y-1/2 h-1 bg-primary transition-all duration-500 -z-0"
                    :style="step === 1 ? 'width: 0%' : (step === 2 ? 'width: calc(50% - 24px)' : 'width: calc(100% - 48px)')"
                ></div>

                <!-- Paso 1: Contacto -->
                <button
                    type="button"
                    @click="step = 1"
                    class="relative z-10 flex flex-col items-center gap-2 focus:outline-none group"
                >
                    <span
                        class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm border-2 transition-all duration-300"
                        :class="step >= 1 ? 'bg-primary text-white border-primary shadow-md' : 'bg-white text-gray-400 border-gray-300'"
                    >
                        <template x-if="step > 1">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <template x-if="step <= 1">
                            <span>1</span>
                        </template>
                    </span>
                    <span
                        class="text-xs font-bold uppercase tracking-wider transition-colors"
                        :class="step >= 1 ? 'text-primary font-black' : 'text-gray-400'"
                    >
                        1. Contacto
                    </span>
                </button>

                <!-- Paso 2: Entrega -->
                <button
                    type="button"
                    @click="goToStep(2)"
                    class="relative z-10 flex flex-col items-center gap-2 focus:outline-none group"
                >
                    <span
                        class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm border-2 transition-all duration-300"
                        :class="step >= 2 ? 'bg-primary text-white border-primary shadow-md' : 'bg-white text-gray-400 border-gray-300'"
                    >
                        <template x-if="step > 2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <template x-if="step <= 2">
                            <span>2</span>
                        </template>
                    </span>
                    <span
                        class="text-xs font-bold uppercase tracking-wider transition-colors"
                        :class="step >= 2 ? 'text-primary font-black' : 'text-gray-400'"
                    >
                        2. Entrega
                    </span>
                </button>

                <!-- Paso 3: Pago y Confirmación -->
                <button
                    type="button"
                    @click="goToStep(3)"
                    class="relative z-10 flex flex-col items-center gap-2 focus:outline-none group"
                >
                    <span
                        class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm border-2 transition-all duration-300"
                        :class="step === 3 ? 'bg-primary text-white border-primary shadow-md' : 'bg-white text-gray-400 border-gray-300'"
                    >
                        3
                    </span>
                    <span
                        class="text-xs font-bold uppercase tracking-wider transition-colors"
                        :class="step === 3 ? 'text-primary font-black' : 'text-gray-400'"
                    >
                        3. Pago
                    </span>
                </button>
            </div>
        </div>

        <form action="{{ route('checkout.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Columna Principal: Contenido según el Paso Activo -->
                <div class="lg:col-span-7 space-y-6">

                    <!-- ================= ETAPA 1: DATOS DEL COMPRADOR ================= -->
                    <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                        <section class="bg-white rounded-card border border-border p-6 sm:p-7 shadow-sm space-y-5">
                            <div class="flex items-center gap-2.5 border-b border-border pb-3">
                                <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-black inline-flex items-center justify-center">1</span>
                                <div>
                                    <h2 class="text-base sm:text-lg font-black text-dark">Datos de Contacto del Comprador</h2>
                                    <p class="text-xs text-gray-500">Información para emitir tu certificado de garantía y confirmar tu pedido.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label for="checkout-name" class="block text-xs font-bold uppercase text-gray-700 mb-1">Nombre y Apellidos *</label>
                                    <input
                                        id="checkout-name"
                                        type="text"
                                        name="customer_name"
                                        x-model="customerName"
                                        required
                                        placeholder="Nombre completo de quien recibe o recoge"
                                        class="w-full rounded-base border @error('customer_name') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                    >
                                    @error('customer_name')
                                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="checkout-phone" class="block text-xs font-bold uppercase text-gray-700 mb-1">Teléfono WhatsApp (10 dígitos) *</label>
                                    <input
                                        id="checkout-phone"
                                        type="tel"
                                        name="customer_phone"
                                        x-model="customerPhone"
                                        required
                                        maxlength="15"
                                        placeholder="Ej. 378 123 4567"
                                        class="w-full rounded-base border @error('customer_phone') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                    >
                                    @error('customer_phone')
                                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="checkout-email" class="block text-xs font-bold uppercase text-gray-700 mb-1">Correo Electrónico *</label>
                                    <input
                                        id="checkout-email"
                                        type="email"
                                        name="customer_email"
                                        x-model="customerEmail"
                                        required
                                        placeholder="correo@ejemplo.com"
                                        class="w-full rounded-base border @error('customer_email') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                                    >
                                    @error('customer_email')
                                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="pt-4 border-t border-border flex justify-end">
                                <button
                                    type="button"
                                    @click="goToStep(2)"
                                    class="w-full sm:w-auto bg-primary hover:bg-primary-hover text-white font-extrabold py-3 px-8 rounded-base text-sm uppercase tracking-wider transition-colors shadow flex items-center justify-center gap-2 cursor-pointer"
                                >
                                    <span>Continuar a Modalidad de Entrega</span>
                                    <span>→</span>
                                </button>
                            </div>
                        </section>
                    </div>

                    <!-- ================= ETAPA 2: MODALIDAD DE ENTREGA Y DOMICILIO ================= -->
                    <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                        <section class="bg-white rounded-card border border-border p-6 sm:p-7 shadow-sm space-y-5">
                            <div class="flex items-center gap-2.5 border-b border-border pb-3">
                                <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-black inline-flex items-center justify-center">2</span>
                                <div>
                                    <h2 class="text-base sm:text-lg font-black text-dark">Modalidad de Entrega</h2>
                                    <p class="text-xs text-gray-500">Selecciona si recibes en tu domicilio o recoges en nuestro mostrador en Tepatitlán.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label
                                    :class="deliveryMethod === 'shipping' ? 'border-primary bg-primary/5 ring-2 ring-primary' : 'border-border bg-white'"
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
                                        <span class="block text-sm font-black text-dark">Envío Nacional Asegurado</span>
                                        <span class="block text-xs text-gray-600 mt-0.5">Empaque especializado y paqueterías certificadas a todo México.</span>
                                    </div>
                                </label>

                                <label
                                    :class="deliveryMethod === 'pickup' ? 'border-primary bg-primary/5 ring-2 ring-primary' : 'border-border bg-white'"
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
                                        <span class="block text-sm font-black text-dark">Recoger en Mostrador (Gratis)</span>
                                        <span class="block text-xs text-gray-600 mt-0.5">Sucursal Tepatitlán de Morelos, Jalisco.</span>
                                    </div>
                                </label>
                            </div>
                            @error('delivery_method')
                                <p class="text-xs text-red-600 font-semibold">{{ $message }}</p>
                            @enderror

                            <!-- Bloque Domicilio de Envío -->
                            <div x-show="deliveryMethod === 'shipping'" class="space-y-4 pt-2">
                                <div class="bg-surface rounded-base border border-border p-4">
                                    <label for="checkout-postal-code" class="block text-xs font-black uppercase tracking-wide text-dark mb-1">
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
                                    </div>
                                    @error('shipping_postal_code')
                                        <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror
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
                                            placeholder="Entre calles o taller mecánico"
                                            class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Información Mostrador -->
                            <div x-cloak x-show="deliveryMethod === 'pickup'" class="bg-surface rounded-base border border-border p-4 text-sm text-gray-700 space-y-1">
                                <strong class="text-dark block font-black">Sucursal de entrega en mostrador:</strong>
                                <p>{{ $business['address'] }}</p>
                                <p class="text-xs text-gray-500">{{ $business['hours']['weekdays'] }} · {{ $business['hours']['saturday'] }}</p>
                            </div>

                            <div class="pt-4 border-t border-border flex items-center justify-between gap-4">
                                <button
                                    type="button"
                                    @click="step = 1"
                                    class="border border-border hover:bg-gray-100 text-dark font-bold py-3 px-6 rounded-base text-sm transition-colors cursor-pointer"
                                >
                                    ← Volver a Contacto
                                </button>
                                <button
                                    type="button"
                                    @click="goToStep(3)"
                                    class="bg-primary hover:bg-primary-hover text-white font-extrabold py-3 px-8 rounded-base text-sm uppercase tracking-wider transition-colors shadow flex items-center gap-2 cursor-pointer"
                                >
                                    <span>Continuar a Revisión y Pago</span>
                                    <span>→</span>
                                </button>
                            </div>
                        </section>
                    </div>

                    <!-- ================= ETAPA 3: REVISIÓN, RESUMEN Y PAGO ================= -->
                    <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                        <section class="bg-white rounded-card border border-border p-6 sm:p-7 shadow-sm space-y-5">
                            <div class="flex items-center gap-2.5 border-b border-border pb-3">
                                <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-black inline-flex items-center justify-center">3</span>
                                <div>
                                    <h2 class="text-base sm:text-lg font-black text-dark">Revisión Final y Pago</h2>
                                    <p class="text-xs text-gray-500">Verifica que tus datos sean correctos y procesa tu pago en la pasarela segura.</p>
                                </div>
                            </div>

                            <!-- Ficha de Confirmación Rápida -->
                            <div class="bg-surface rounded-base border border-border p-4 space-y-2.5 text-xs text-gray-700">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-gray-500 uppercase">Comprador:</span>
                                    <span class="font-black text-dark" x-text="customerName + ' (' + customerPhone + ')'"></span>
                                    <button type="button" @click="step = 1" class="text-primary font-bold hover:underline">Editar</button>
                                </div>
                                <div class="flex items-center justify-between border-t border-border/60 pt-2">
                                    <span class="font-bold text-gray-500 uppercase">Modalidad:</span>
                                    <span class="font-black text-dark" x-text="deliveryMethod === 'pickup' ? 'Recoger en Mostrador Tepatitlán' : 'Envío Asegurado (C.P. ' + postalCode + ')'"></span>
                                    <button type="button" @click="step = 2" class="text-primary font-bold hover:underline">Editar</button>
                                </div>
                            </div>

                            <!-- Método de Pago Seleccionado -->
                            <div class="space-y-3 pt-2">
                                <label class="block text-xs font-black uppercase text-dark">Método de Pago Autorizado</label>
                                <div class="p-4 rounded-base border-2 border-[#009EE3] bg-[#009EE3]/5 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-[#009EE3] text-white flex items-center justify-center font-black">
                                            MP
                                        </div>
                                        <div>
                                            <span class="block text-sm font-black text-dark">Mercado Pago Checkout Pro</span>
                                            <span class="block text-xs text-gray-600">Tarjetas de crédito, débito, SPEI o efectivo en OXXO.</span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-black text-[#009EE3] uppercase bg-white px-2.5 py-1 rounded border border-[#009EE3]/30">Activo</span>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-border flex items-center justify-between gap-4">
                                <button
                                    type="button"
                                    @click="step = 2"
                                    class="border border-border hover:bg-gray-100 text-dark font-bold py-3 px-6 rounded-base text-sm transition-colors cursor-pointer"
                                >
                                    ← Volver a Entrega
                                </button>
                                <button
                                    type="submit"
                                    class="bg-[#009EE3] hover:bg-[#0081BA] text-white font-black py-3.5 px-8 rounded-base text-sm uppercase tracking-wider transition-colors shadow flex items-center gap-2 cursor-pointer"
                                >
                                    <span>Pagar Pedido con Mercado Pago</span>
                                    <span>→</span>
                                </button>
                            </div>
                        </section>
                    </div>

                    <!-- Nota de Facturación -->
                    <div class="bg-white rounded-card border border-border p-4 text-xs sm:text-sm text-gray-700 flex items-center justify-between gap-4 shadow-sm">
                        <div>
                            <strong class="text-dark font-black">¿Requieres factura fiscal?</strong>
                            <span class="block text-xs text-gray-500">Envíanos tu constancia de situación fiscal por WhatsApp una vez generado tu pedido.</span>
                        </div>
                        <a
                            href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, requiero información para facturar mi compra.') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-xs font-black text-primary hover:underline shrink-0"
                        >
                            Facturar por WhatsApp &rarr;
                        </a>
                    </div>
                </div>

                <!-- Columna Derecha Fija: Resumen de la Orden & Desglose con Cupones -->
                <aside class="lg:col-span-5 bg-white rounded-card border border-border p-6 shadow-sm space-y-5 sticky top-6">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <h2 class="text-lg font-black text-dark">Resumen de tu Orden</h2>
                        <a href="{{ route('cart.index') }}" class="text-xs font-bold text-primary hover:underline">
                            Modificar carrito
                        </a>
                    </div>

                    <ul class="divide-y divide-border max-h-72 overflow-y-auto pr-1">
                        @foreach($items as $item)
                            <li class="py-3 flex items-start justify-between gap-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-bold text-dark leading-snug line-clamp-2">{{ $item['title'] }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        SKU: {{ $item['sku'] }} · Cantidad: <strong>{{ $item['quantity'] }}</strong> · Garantía {{ $item['warranty_days'] }} días
                                    </p>
                                </div>
                                <span class="font-black text-dark whitespace-nowrap shrink-0">
                                    {{ $item['formatted_line_subtotal'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-border pt-4 space-y-2.5 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal mercancía</span>
                            <span class="font-bold text-dark whitespace-nowrap">{{ $cart['formattedSubtotal'] }}</span>
                        </div>

                        @if(!empty($cart['coupon']))
                            <div class="flex justify-between text-emerald-700 font-bold">
                                <span>Cupón ({{ $cart['coupon']['code'] }})</span>
                                <span class="whitespace-nowrap">-{{ $cart['formattedDiscount'] }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-gray-600">
                            <span>Modalidad de entrega</span>
                            <span class="text-xs font-black text-dark" x-text="deliveryMethod === 'pickup' ? 'Recolección en Mostrador ($0.00)' : 'Envío Asegurado'"></span>
                        </div>

                        <div class="flex justify-between items-baseline pt-3 border-t border-border">
                            <span class="text-base font-black uppercase text-dark">Total Final</span>
                            <span class="text-2xl font-black text-primary whitespace-nowrap">{{ $cart['formattedTotal'] ?? $cart['formattedSubtotal'] }}</span>
                        </div>
                    </div>

                    <!-- Botón Flotante/Rápido en Paso 3 -->
                    <div x-show="step === 3" class="pt-2">
                        <button
                            type="submit"
                            class="w-full bg-[#009EE3] hover:bg-[#0081BA] text-white font-black py-3.5 px-5 rounded-base text-sm transition-colors shadow flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>Confirmar y Pagar Pedido</span>
                            <span>→</span>
                        </button>
                    </div>

                    <div class="flex items-center justify-center gap-2 text-[11px] text-gray-500 pt-2 border-t border-border">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Transacción cifrada y protegida por Mercado Pago</span>
                    </div>

                    <div>
                        <a
                            href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode($whatsappCheckoutText) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full bg-whatsapp hover:brightness-95 text-white font-extrabold py-2.5 px-4 rounded-base text-xs transition-colors flex items-center justify-center gap-2 shadow-xs"
                        >
                            <span>¿Dudas con tu pago? Escríbenos por WhatsApp</span>
                        </a>
                    </div>
                </aside>
            </div>
        </form>
    </div>
@endsection
