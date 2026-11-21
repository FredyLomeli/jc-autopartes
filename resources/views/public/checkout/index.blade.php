@extends('layouts.public', [
    'title' => 'Checkout — Datos de Envío y Pago',
    'metaDescription' => 'Completa tu pedido de autopartes en JC Autopartes de forma rápida, despejada y segura.',
    'canonicalUrl' => route('checkout.index'),
])

@php
    $business = config('business');
    $settings = $companySettings ?? [];
    $items = $cart['items'] ?? [];
    $totalFormatted = $cart['formattedTotal'] ?? $cart['formattedSubtotal'];

    $branchAddress = trim(
        ($settings['contact_address'] ?? $business['address'] ?? 'Bertha Padilla Gutierrez #2020') . ', ' .
        'Col. ' . ($settings['contact_colony'] ?? 'Los Adobes') . ', C.P. ' .
        ($settings['contact_zip'] ?? '47656') . ', ' .
        ($settings['contact_city'] ?? 'Tepatitlán de Morelos, Jalisco')
    );
    $hoursWeekdays = $settings['schedule_weekday'] ?? ($business['hours']['weekdays'] ?? 'Lunes a Viernes: 9:00 am - 6:00 pm');
    $hoursSaturday = $settings['schedule_saturday'] ?? ($business['hours']['saturday'] ?? 'Sábados: 9:00 am - 2:00 pm');
    $rawWaPhone = preg_replace('/\D+/', '', (string) ($settings['contact_whatsapp_1'] ?? $business['whatsapp_number'] ?? '523781455737'));
    $waPhone = strlen($rawWaPhone) === 10 ? '52' . $rawWaPhone : $rawWaPhone;
    $whatsappLinkBase = "https://wa.me/{$waPhone}";

    $whatsappOrderLines = collect($items)->map(fn ($i) => "• {$i['quantity']}x {$i['title']} (SKU: {$i['sku']}) - {$i['formatted_line_subtotal']}")->implode("\n");
    $whatsappCheckoutText = "Hola JC Autopartes, quiero consultar o solicitar factura de mi pedido web:\n{$whatsappOrderLines}\nTotal: {$totalFormatted}";
    $initialDeliveryChoice = match (old('delivery_variant')) {
        'pickup' => 'pickup',
        'ocurre' => 'ocurre',
        'domicilio' => 'domicilio',
        default => old('delivery_method') === 'pickup' ? 'pickup' : 'domicilio',
    };
    $initialStep = $errors->any() ? 2 : 1;
    $merchandiseTotal = round((float) ($cart['total'] ?? $cart['subtotal'] ?? 0.0), 2);
@endphp

@section('content')
    <!-- Encabezado Minimalista -->
    <div class="bg-white border-b border-border">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-5">
            <nav aria-label="Breadcrumb" class="text-xs text-gray-500 mb-1.5">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('home') }}" class="hover:text-primary transition-colors">Inicio</a></li>
                    <li>/</li>
                    <li><a href="{{ route('cart.index') }}" class="hover:text-primary transition-colors">Carrito</a></li>
                    <li>/</li>
                    <li class="font-semibold text-dark" aria-current="page">Checkout</li>
                </ol>
            </nav>

            <h1 class="text-2xl sm:text-3xl font-black text-dark tracking-tight">
                Finalizar Pedido
            </h1>
        </div>
    </div>

    <div
        x-data="{
            step: {{ $initialStep }},
            showSummary: {{ !empty($cart['coupon']) ? 'true' : 'false' }},
            deliveryChoice: @js($initialDeliveryChoice),
            merchandiseTotal: {{ $merchandiseTotal }},
            shippingQuote: @js($initialQuote ?? null),
            quoteLoading: false,
            quoteError: '',
            quoteUrl: @js(route('checkout.quote-shipping')),
            csrfToken: @js(csrf_token()),
            customerName: @js(old('customer_name', '')),
            customerPhone: @js(old('customer_phone', '')),
            customerEmail: @js(old('customer_email', '')),
            postalCode: @js(old('shipping_postal_code', '')),
            shippingAddress: @js(old('shipping_address', '')),
            shippingNeighborhood: @js(old('shipping_neighborhood', '')),
            shippingCity: @js(old('shipping_city', '')),
            shippingState: @js(old('shipping_state', '')),
            shippingNotes: @js(old('shipping_notes', '')),
            formError: '',

            selectDelivery(choice) {
                this.deliveryChoice = choice;
                this.formError = '';
                this.quoteError = '';
                if (choice === 'ocurre' && this.shippingNeighborhood.trim() === '') {
                    this.shippingNeighborhood = 'Servicio Ocurre';
                } else if (choice === 'domicilio' && this.shippingNeighborhood === 'Servicio Ocurre') {
                    this.shippingNeighborhood = '';
                }

                if (choice === 'pickup') {
                    this.shippingQuote = {
                        valid: true,
                        postal_code: '47600',
                        delivery_variant: 'pickup',
                        carrier: 'Matriz JC Autopartes (Mostrador)',
                        zone_label: 'Tepatitlán de Morelos, Jalisco',
                        cost: 0,
                        formatted_cost: '$0.00 MXN',
                        estimated_days: 'Entrega en mostrador el mismo día hábil',
                        is_simulated: true,
                        notice: 'Recolección directa en tienda sin costo de paquetería.'
                    };
                    return;
                }

                if (/^[0-9]{5}$/.test(this.postalCode.trim())) {
                    this.fetchShippingQuote();
                } else {
                    this.shippingQuote = null;
                }
            },

            deliveryLabel() {
                if (this.deliveryChoice === 'pickup') return 'Recoger en Mostrador (Tepatitlán)';
                if (this.deliveryChoice === 'ocurre') return 'Servicio Ocurre en Paquetería';
                return 'Envío a Domicilio';
            },

            hasValidQuote() {
                if (this.deliveryChoice === 'pickup') return true;
                return Boolean(
                    this.shippingQuote &&
                    this.shippingQuote.valid &&
                    this.shippingQuote.postal_code === this.postalCode.trim() &&
                    this.shippingQuote.delivery_variant === this.deliveryChoice
                );
            },

            shippingCost() {
                if (this.deliveryChoice === 'pickup') return 0;
                return this.hasValidQuote() ? Number(this.shippingQuote.cost || 0) : 0;
            },

            formatMxn(amount) {
                return '$' + Number(amount || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' MXN';
            },

            formattedGrandTotal() {
                return this.formatMxn(this.merchandiseTotal + this.shippingCost());
            },

            async fetchShippingQuote() {
                if (this.deliveryChoice === 'pickup') {
                    this.quoteError = '';
                    return true;
                }

                const cleanCp = this.postalCode.replace(/\D/g, '').slice(0, 5);
                this.postalCode = cleanCp;

                if (!/^[0-9]{5}$/.test(cleanCp)) {
                    this.shippingQuote = null;
                    this.quoteError = 'Ingresa un Código Postal válido de 5 dígitos para validar cobertura y calcular el envío.';
                    return false;
                }

                this.quoteLoading = true;
                this.quoteError = '';

                try {
                    const response = await fetch(this.quoteUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({
                            postal_code: cleanCp,
                            delivery_variant: this.deliveryChoice,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok || !data.ok) {
                        this.shippingQuote = null;
                        this.quoteError = data.message || (data.errors && data.errors.postal_code ? data.errors.postal_code[0] : 'No se pudo cotizar el Código Postal.');
                        return false;
                    }

                    this.shippingQuote = data.quote;
                    return true;
                } catch (e) {
                    this.quoteError = 'Error de conexión al validar el Código Postal. Intenta nuevamente.';
                    return false;
                } finally {
                    this.quoteLoading = false;
                }
            },

            async continueFromStep1() {
                if (this.deliveryChoice !== 'pickup' && !this.hasValidQuote()) {
                    const ok = await this.fetchShippingQuote();
                    if (!ok) {
                        return;
                    }
                }
                this.step = 2;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            validateStep2() {
                if (this.customerName.trim().length < 3) {
                    this.formError = 'Ingresa tu nombre completo (mínimo 3 caracteres).';
                    return false;
                }
                if (this.customerPhone.replace(/\D/g, '').length < 10) {
                    this.formError = 'Ingresa un teléfono o WhatsApp válido de al menos 10 dígitos.';
                    return false;
                }
                if (!this.customerEmail.includes('@') || !this.customerEmail.includes('.')) {
                    this.formError = 'Ingresa un correo electrónico válido.';
                    return false;
                }
                if (this.deliveryChoice !== 'pickup') {
                    if (!/^[0-9]{5}$/.test(this.postalCode.trim())) {
                        this.formError = 'El Código Postal debe ser de 5 dígitos numéricos.';
                        return false;
                    }
                    if (this.shippingAddress.trim().length < 3) {
                        this.formError = this.deliveryChoice === 'ocurre'
                            ? 'Indica la paquetería y sucursal Ocurre donde recogerás tu pieza.'
                            : 'Ingresa la calle y número de entrega.';
                        return false;
                    }
                    if (this.shippingNeighborhood.trim().length < 2) {
                        this.formError = 'Ingresa la colonia o zona de referencia.';
                        return false;
                    }
                    if (this.shippingCity.trim().length < 2 || this.shippingState.trim().length < 2) {
                        this.formError = 'Ingresa la ciudad/municipio y el estado de destino.';
                        return false;
                    }
                }
                this.formError = '';
                return true;
            },

            async goToStep(target) {
                this.formError = '';
                if (target === 2) {
                    await this.continueFromStep1();
                    return;
                }
                if (target === 3) {
                    if (this.deliveryChoice !== 'pickup' && !this.hasValidQuote()) {
                        await this.fetchShippingQuote();
                    }
                    if (!this.validateStep2()) {
                        this.step = 2;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }
                }
                this.step = target;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            handleSubmit(event) {
                if (!this.validateStep2()) {
                    event.preventDefault();
                    this.step = 2;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }"
        class="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6"
    >
        <!-- Mensajes de Error / Éxito de Sesión -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-base p-4 text-sm flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-base p-4 text-sm flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div><strong class="font-bold">Atención:</strong> {{ session('error') }}</div>
            </div>
        @endif

        <!-- Barra Superior Colapsable del Resumen (Sin estorbar la pantalla) -->
        <div class="bg-white rounded-card border border-border shadow-xs overflow-hidden">
            <button
                type="button"
                @click="showSummary = !showSummary"
                class="w-full px-5 py-3.5 flex items-center justify-between gap-4 text-left hover:bg-surface/60 transition-colors cursor-pointer"
            >
                <div class="flex items-center gap-2.5 text-sm">
                    <svg class="w-5 h-5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span class="font-bold text-dark">
                        Tu pedido ({{ $cart['totalQuantity'] }} {{ $cart['totalQuantity'] === 1 ? 'pieza' : 'piezas' }})
                    </span>
                    <span class="text-xs font-semibold text-primary inline-flex items-center gap-1">
                        <span x-text="showSummary ? 'Ocultar detalle' : 'Ver detalle / Cupón'"></span>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="showSummary ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </div>
                <span class="text-base sm:text-lg font-black text-dark whitespace-nowrap" x-text="formattedGrandTotal()">
                    {{ $totalFormatted }}
                </span>
            </button>

            <!-- Cajón Desplegable de Artículos y Cupón -->
            <div x-cloak x-show="showSummary" x-transition class="border-t border-border bg-surface/50 px-5 py-4 space-y-4">
                <ul class="divide-y divide-border">
                    @foreach($items as $item)
                        <li class="py-2.5 flex items-center justify-between gap-4 text-sm">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $item['photo_url'] }}" alt="{{ $item['title'] }}" class="w-12 h-12 rounded-base object-cover border border-border bg-white shrink-0">
                                <div class="min-w-0">
                                    <p class="font-bold text-dark truncate">{{ $item['title'] }}</p>
                                    <p class="text-xs text-gray-500">Cant: {{ $item['quantity'] }} · SKU: {{ $item['sku'] }}</p>
                                </div>
                            </div>
                            <span class="font-bold text-dark whitespace-nowrap shrink-0">{{ $item['formatted_line_subtotal'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <!-- Cupón de Descuento dentro del Resumen Colapsable -->
                <div class="pt-3 border-t border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    @if(!empty($cart['coupon']))
                        <div class="bg-emerald-50 border border-emerald-200 rounded-base px-3 py-2 flex items-center justify-between gap-4 w-full sm:w-auto">
                            <span class="text-xs font-bold text-emerald-800">
                                Cupón <span class="font-mono">{{ $cart['coupon']['code'] }}</span> aplicado (-{{ $cart['formattedDiscount'] }})
                            </span>
                            <form action="{{ route('cart.coupon.remove') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Quitar</button>
                            </form>
                        </div>
                    @else
                        <form action="{{ route('cart.coupon.apply') }}" method="POST" class="flex items-center gap-2 w-full sm:max-w-xs">
                            @csrf
                            <input
                                type="text"
                                name="coupon_code"
                                placeholder="Código de cupón"
                                class="flex-1 rounded-base border border-border bg-white px-3 py-1.5 text-xs font-mono uppercase text-dark focus:border-primary focus:ring-1 focus:ring-primary"
                                required
                            >
                            <button type="submit" class="bg-dark hover:bg-gray-800 text-white text-xs font-bold px-3.5 py-1.5 rounded-base uppercase transition-colors cursor-pointer">
                                Aplicar
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('cart.index') }}" class="text-xs font-bold text-primary hover:underline shrink-0">
                        Editar carrito &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- Stepper Minimalista en 3 Etapas -->
        <nav aria-label="Progreso del checkout" class="py-2">
            <div class="flex items-center justify-between max-w-lg mx-auto relative">
                <div class="absolute left-5 right-5 top-4 h-0.5 bg-gray-200 -z-0"></div>
                <div
                    class="absolute left-5 top-4 h-0.5 bg-primary transition-all duration-300 -z-0"
                    :style="step === 1 ? 'width: 0%' : (step === 2 ? 'width: calc(50% - 20px)' : 'width: calc(100% - 40px)')"
                ></div>

                <button type="button" @click="goToStep(1)" class="relative z-10 flex flex-col items-center gap-1.5 cursor-pointer">
                    <span
                        class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black border-2 transition-colors"
                        :class="step === 1 ? 'bg-primary text-white border-primary' : (step > 1 ? 'bg-[#0c1a30] text-white border-[#0c1a30]' : 'bg-white text-gray-400 border-gray-300')"
                    >
                        <span x-show="step > 1">✓</span>
                        <span x-show="step <= 1">1</span>
                    </span>
                    <span class="text-xs font-bold" :class="step === 1 ? 'text-primary' : (step > 1 ? 'text-[#0c1a30]' : 'text-gray-400')">
                        1. Entrega
                    </span>
                </button>

                <button type="button" @click="goToStep(2)" class="relative z-10 flex flex-col items-center gap-1.5 cursor-pointer">
                    <span
                        class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black border-2 transition-colors"
                        :class="step === 2 ? 'bg-primary text-white border-primary' : (step > 2 ? 'bg-[#0c1a30] text-white border-[#0c1a30]' : 'bg-white text-gray-400 border-gray-300')"
                    >
                        <span x-show="step > 2">✓</span>
                        <span x-show="step <= 2">2</span>
                    </span>
                    <span class="text-xs font-bold" :class="step === 2 ? 'text-primary' : (step > 2 ? 'text-[#0c1a30]' : 'text-gray-400')">
                        2. Datos
                    </span>
                </button>

                <button type="button" @click="goToStep(3)" class="relative z-10 flex flex-col items-center gap-1.5 cursor-pointer">
                    <span
                        class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black border-2 transition-colors"
                        :class="step === 3 ? 'bg-primary text-white border-primary' : 'bg-white text-gray-400 border-gray-300'"
                    >
                        3
                    </span>
                    <span class="text-xs font-bold" :class="step === 3 ? 'text-primary' : 'text-gray-400'">
                        3. Pago
                    </span>
                </button>
            </div>
        </nav>

        <!-- Formulario Principal Limpio de Una Sola Columna -->
        <form action="{{ route('checkout.store') }}" method="POST" @submit="handleSubmit($event)">
            @csrf
            <input type="hidden" name="delivery_method" :value="deliveryChoice === 'pickup' ? 'pickup' : 'shipping'">
            <input type="hidden" name="delivery_variant" :value="deliveryChoice">

            <!-- ================= PASO 1: MÉTODO DE ENTREGA (SOLO TARJETAS) ================= -->
            <section x-show="step === 1" x-transition.opacity class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm space-y-6">
                <div class="text-center space-y-1">
                    <h2 class="text-xl sm:text-2xl font-black text-dark">¿Cómo deseas recibir tu refacción?</h2>
                    <p class="text-sm text-gray-500">Elige la opción más cómoda para ti antes de ingresar tus datos.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Tarjeta 1: Envío a Domicilio -->
                    <button
                        type="button"
                        @click="selectDelivery('domicilio')"
                        :class="deliveryChoice === 'domicilio' ? 'border-primary bg-red-50/30 ring-2 ring-primary' : 'border-border bg-white hover:border-gray-400'"
                        class="rounded-card border p-5 text-left flex flex-col justify-between gap-3 transition-all cursor-pointer"
                    >
                        <div class="space-y-2.5">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="deliveryChoice === 'domicilio' ? 'bg-primary text-white' : 'bg-surface text-dark'">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 17a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4zM1 4h14v11H1V4zm14 4h4l3 3v4h-7V8z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-black text-dark">Envío a Domicilio</h3>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Paquetería asegurada a todo México (FedEx, Estafeta, Paquetexpress). Entrega de 2 a 5 días hábiles.
                            </p>
                        </div>
                        <span class="text-xs font-bold" :class="deliveryChoice === 'domicilio' ? 'text-primary' : 'text-gray-400'">
                            <span x-show="deliveryChoice === 'domicilio'">● Seleccionado</span>
                            <span x-show="deliveryChoice !== 'domicilio'">Seleccionar</span>
                        </span>
                    </button>

                    <!-- Tarjeta 2: Servicio Ocurre -->
                    <button
                        type="button"
                        @click="selectDelivery('ocurre')"
                        :class="deliveryChoice === 'ocurre' ? 'border-primary bg-red-50/30 ring-2 ring-primary' : 'border-border bg-white hover:border-gray-400'"
                        class="rounded-card border p-5 text-left flex flex-col justify-between gap-3 transition-all cursor-pointer"
                    >
                        <div class="space-y-2.5">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="deliveryChoice === 'ocurre' ? 'bg-primary text-white' : 'bg-surface text-dark'">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-black text-dark">Servicio Ocurre</h3>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Recoge en la sucursal de paquetería de tu ciudad. Ideal para motores, transmisiones o zonas extendidas.
                            </p>
                        </div>
                        <span class="text-xs font-bold" :class="deliveryChoice === 'ocurre' ? 'text-primary' : 'text-gray-400'">
                            <span x-show="deliveryChoice === 'ocurre'">● Seleccionado</span>
                            <span x-show="deliveryChoice !== 'ocurre'">Seleccionar</span>
                        </span>
                    </button>

                    <!-- Tarjeta 3: Recoger en Mostrador -->
                    <button
                        type="button"
                        @click="selectDelivery('pickup')"
                        :class="deliveryChoice === 'pickup' ? 'border-primary bg-red-50/30 ring-2 ring-primary' : 'border-border bg-white hover:border-gray-400'"
                        class="rounded-card border p-5 text-left flex flex-col justify-between gap-3 transition-all cursor-pointer"
                    >
                        <div class="space-y-2.5">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="deliveryChoice === 'pickup' ? 'bg-primary text-white' : 'bg-surface text-dark'">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-black text-dark">Recoger en Mostrador</h3>
                            <p class="text-xs text-gray-600 leading-relaxed">
                                Sin costo adicional en Matriz Tepatitlán, Jalisco. Pasa por tu refacción directamente en tienda.
                            </p>
                        </div>
                        <span class="text-xs font-bold" :class="deliveryChoice === 'pickup' ? 'text-primary' : 'text-gray-400'">
                            <span x-show="deliveryChoice === 'pickup'">● Seleccionado</span>
                            <span x-show="deliveryChoice !== 'pickup'">Seleccionar</span>
                        </span>
                    </button>
                </div>

                <!-- Cotizador de Envío por Código Postal (Domicilio / Ocurre) -->
                <div x-cloak x-show="deliveryChoice !== 'pickup'" class="bg-surface rounded-card border border-border p-4 sm:p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                        <div class="flex-1">
                            <label for="step1-postal-code" class="block text-xs font-black uppercase tracking-wider text-dark mb-1">
                                Código Postal destino (5 dígitos) *
                            </label>
                            <p class="text-xs text-gray-500 mb-2">
                                Valida tu zona y conoce el costo estimado de Paquetexpress antes de continuar.
                            </p>
                            <div class="flex flex-col sm:flex-row gap-2.5">
                                <input
                                    id="step1-postal-code"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="5"
                                    x-model="postalCode"
                                    @keydown.enter.prevent="fetchShippingQuote()"
                                    placeholder="Ej. 47600 o 44100"
                                    class="sm:w-48 rounded-base border border-border bg-white px-3.5 py-2.5 text-sm font-bold text-dark tracking-wider focus:border-primary focus:ring-1 focus:ring-primary"
                                >
                                <button
                                    type="button"
                                    @click="fetchShippingQuote()"
                                    :disabled="quoteLoading"
                                    class="bg-dark hover:bg-gray-800 disabled:opacity-60 text-white font-bold px-5 py-2.5 rounded-base text-xs uppercase tracking-wider transition-colors cursor-pointer whitespace-nowrap"
                                >
                                    <span x-show="!quoteLoading">Validar C.P. y Cotizar</span>
                                    <span x-show="quoteLoading">Cotizando...</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-cloak x-show="quoteError" class="bg-red-50 border border-red-200 text-red-800 rounded-base p-3 text-xs font-semibold">
                        <span x-text="quoteError"></span>
                    </div>

                    <div x-cloak x-show="hasValidQuote()" class="bg-white border border-emerald-200 rounded-base p-4 space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[11px] font-black uppercase">
                                    Ejemplo Paquetexpress
                                </span>
                                <span class="text-xs font-bold text-dark" x-text="shippingQuote?.zone_label"></span>
                            </div>
                            <span class="text-base font-black text-primary" x-text="shippingQuote?.formatted_cost"></span>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600">
                            <span>Modalidad: <strong class="text-dark" x-text="deliveryLabel()"></strong></span>
                            <span>Entrega estimada: <strong class="text-dark" x-text="shippingQuote?.estimated_days"></strong></span>
                            <span x-show="shippingQuote?.billable_weight_kg">Peso cobrable: <strong class="text-dark" x-text="(shippingQuote?.billable_weight_kg || '') + ' kg'"></strong></span>
                        </div>
                        <p class="text-[11px] text-gray-500" x-text="shippingQuote?.notice"></p>
                    </div>
                </div>

                <div class="pt-4 border-t border-border flex justify-end">
                    <button
                        type="button"
                        @click="continueFromStep1()"
                        :disabled="quoteLoading"
                        class="w-full sm:w-auto bg-primary hover:bg-primary-hover disabled:opacity-60 text-white font-extrabold py-3 px-8 rounded-base text-sm uppercase tracking-wider transition-colors shadow-sm cursor-pointer"
                    >
                        Continuar a Datos &rarr;
                    </button>
                </div>
            </section>

            <!-- ================= PASO 2: DATOS DE CONTACTO Y DESTINO ================= -->
            <section x-cloak x-show="step === 2" x-transition.opacity class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border pb-4">
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-dark">Tus Datos de Entrega</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Modalidad elegida: <strong class="text-primary" x-text="deliveryLabel()"></strong>
                        </p>
                    </div>
                    <button type="button" @click="step = 1" class="text-xs font-bold text-primary hover:underline cursor-pointer">
                        Cambiar modalidad
                    </button>
                </div>

                <!-- Alerta de Validación Cliente / Servidor -->
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-base p-3.5 text-xs space-y-1">
                        <strong class="block font-bold">Revisa los campos marcados antes de continuar:</strong>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div x-cloak x-show="formError" class="bg-red-50 border border-red-200 text-red-800 rounded-base p-3.5 text-xs font-semibold">
                    <span x-text="formError"></span>
                </div>

                <!-- Bloque A: Contacto -->
                <div class="space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-wider text-gray-400">1. ¿Quién recibe o recoge?</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="checkout-name" class="block text-xs font-bold text-gray-700 mb-1">Nombre completo *</label>
                            <input
                                id="checkout-name"
                                type="text"
                                name="customer_name"
                                x-model="customerName"
                                placeholder="Nombre y apellidos"
                                class="w-full rounded-base border @error('customer_name') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            >
                            @error('customer_name')
                                <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="checkout-phone" class="block text-xs font-bold text-gray-700 mb-1">Teléfono / WhatsApp (10 dígitos) *</label>
                            <input
                                id="checkout-phone"
                                type="tel"
                                name="customer_phone"
                                x-model="customerPhone"
                                maxlength="15"
                                placeholder="Ej. 378 123 4567"
                                class="w-full rounded-base border @error('customer_phone') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            >
                            @error('customer_phone')
                                <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="checkout-email" class="block text-xs font-bold text-gray-700 mb-1">Correo electrónico *</label>
                            <input
                                id="checkout-email"
                                type="email"
                                name="customer_email"
                                x-model="customerEmail"
                                placeholder="correo@ejemplo.com"
                                class="w-full rounded-base border @error('customer_email') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            >
                            @error('customer_email')
                                <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Bloque B: Dirección (Solo si NO es recolección en mostrador) -->
                <div x-show="deliveryChoice !== 'pickup'" class="space-y-4 pt-4 border-t border-border">
                    <h3 class="text-xs font-black uppercase tracking-wider text-gray-400" x-text="deliveryChoice === 'ocurre' ? '2. Sucursal Ocurre y Ciudad Destino' : '2. Dirección de Envío'"></h3>

                    <div x-show="deliveryChoice === 'ocurre'" class="bg-blue-50 border border-blue-200 rounded-base p-3 text-xs text-blue-900">
                        Tu pedido llegará a la oficina de paquetería que indiques para que pases a recogerlo con tu INE.
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="checkout-postal-code" class="block text-xs font-bold text-gray-700 mb-1">Código Postal (5 dígitos) *</label>
                            <input
                                id="checkout-postal-code"
                                type="text"
                                name="shipping_postal_code"
                                maxlength="5"
                                x-model="postalCode"
                                @input="if (postalCode.replace(/\D/g, '').length === 5) fetchShippingQuote()"
                                placeholder="Ej. 47600"
                                class="w-full rounded-base border @error('shipping_postal_code') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm font-bold text-dark focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary"
                            >
                            @error('shipping_postal_code')
                                <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="checkout-colony" class="block text-xs font-bold text-gray-700 mb-1">
                                <span x-text="deliveryChoice === 'ocurre' ? 'Zona / Colonia de Sucursal *' : 'Colonia / Fraccionamiento *'"></span>
                            </label>
                            <input
                                id="checkout-colony"
                                type="text"
                                name="shipping_neighborhood"
                                x-model="shippingNeighborhood"
                                placeholder="Colonia o zona"
                                class="w-full rounded-base border @error('shipping_neighborhood') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                            >
                            @error('shipping_neighborhood')
                                <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="checkout-street" class="block text-xs font-bold text-gray-700 mb-1">
                                <span x-text="deliveryChoice === 'ocurre' ? 'Paquetería y Sucursal Ocurre deseada *' : 'Calle y Número Exterior / Interior *'"></span>
                            </label>
                            <input
                                id="checkout-street"
                                type="text"
                                name="shipping_address"
                                x-model="shippingAddress"
                                :placeholder="deliveryChoice === 'ocurre' ? 'Ej. Paquetexpress Sucursal Centro / Estafeta Blvd...' : 'Av. / Calle, número exterior e interior'"
                                class="w-full rounded-base border @error('shipping_address') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                            >
                            @error('shipping_address')
                                <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="checkout-city" class="block text-xs font-bold text-gray-700 mb-1">Ciudad / Municipio *</label>
                            <input
                                id="checkout-city"
                                type="text"
                                name="shipping_city"
                                x-model="shippingCity"
                                placeholder="Ciudad o municipio"
                                class="w-full rounded-base border @error('shipping_city') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                            >
                            @error('shipping_city')
                                <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="checkout-state" class="block text-xs font-bold text-gray-700 mb-1">Estado *</label>
                            <input
                                id="checkout-state"
                                type="text"
                                name="shipping_state"
                                x-model="shippingState"
                                placeholder="Ej. Jalisco, CDMX, Nuevo León..."
                                class="w-full rounded-base border @error('shipping_state') border-red-500 @else border-border @enderror bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                            >
                            @error('shipping_state')
                                <p class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="checkout-refs" class="block text-xs font-bold text-gray-700 mb-1">Referencias o indicaciones (opcional)</label>
                            <input
                                id="checkout-refs"
                                type="text"
                                name="shipping_notes"
                                x-model="shippingNotes"
                                placeholder="Entre calles, nombre de taller o indicaciones para la paquetería"
                                class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm text-dark focus:border-primary focus:bg-white"
                            >
                        </div>
                    </div>
                </div>

                <!-- Bloque B Alternativo: Recolección en Sucursal Tepatitlán -->
                <div x-cloak x-show="deliveryChoice === 'pickup'" class="bg-surface rounded-base border border-border p-4 space-y-1.5 text-sm">
                    <strong class="block text-dark font-black">Punto de recolección en Mostrador (Sin costo):</strong>
                    <p class="text-gray-700">{{ $branchAddress }}</p>
                    <p class="text-xs text-gray-500">{{ $hoursWeekdays }} · {{ $hoursSaturday }}</p>
                </div>

                <div class="pt-4 border-t border-border flex items-center justify-between gap-4">
                    <button
                        type="button"
                        @click="step = 1"
                        class="border border-border hover:bg-gray-100 text-dark font-bold py-2.5 px-5 rounded-base text-sm transition-colors cursor-pointer"
                    >
                        &larr; Atrás
                    </button>
                    <button
                        type="button"
                        @click="goToStep(3)"
                        class="bg-primary hover:bg-primary-hover text-white font-extrabold py-3 px-8 rounded-base text-sm uppercase tracking-wider transition-colors shadow-sm cursor-pointer"
                    >
                        Continuar al Pago &rarr;
                    </button>
                </div>
            </section>

            <!-- ================= PASO 3: REVISIÓN FINAL Y PAGO ================= -->
            <section x-cloak x-show="step === 3" x-transition.opacity class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm space-y-6">
                <div class="border-b border-border pb-4">
                    <h2 class="text-lg sm:text-xl font-black text-dark">Confirmación y Pago</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Verifica tus datos y realiza tu pago de forma segura con Mercado Pago.</p>
                </div>

                <!-- Ficha Limpia de Datos Capturados -->
                <div class="bg-surface rounded-base border border-border divide-y divide-border text-xs sm:text-sm">
                    <div class="p-4 flex items-start justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="block text-[11px] font-black uppercase text-gray-400">Contacto</span>
                            <p class="font-bold text-dark" x-text="customerName"></p>
                            <p class="text-gray-600" x-text="customerPhone + ' · ' + customerEmail"></p>
                        </div>
                        <button type="button" @click="step = 2" class="text-xs font-bold text-primary hover:underline shrink-0 cursor-pointer">Editar</button>
                    </div>

                    <div class="p-4 flex items-start justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="block text-[11px] font-black uppercase text-gray-400" x-text="deliveryLabel()"></span>
                            <template x-if="deliveryChoice === 'pickup'">
                                <p class="text-gray-700">{{ $branchAddress }}</p>
                            </template>
                            <template x-if="deliveryChoice !== 'pickup'">
                                <p class="text-gray-700" x-text="shippingAddress + ', ' + shippingNeighborhood + ', C.P. ' + postalCode + ', ' + shippingCity + ', ' + shippingState"></p>
                            </template>
                        </div>
                        <button type="button" @click="step = 1" class="text-xs font-bold text-primary hover:underline shrink-0 cursor-pointer">Cambiar</button>
                    </div>
                </div>

                <!-- Desglose Limpio de Importes -->
                <div class="space-y-2 text-sm pt-1">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal ({{ $cart['totalQuantity'] }} {{ $cart['totalQuantity'] === 1 ? 'pieza' : 'piezas' }})</span>
                        <span class="font-bold text-dark">{{ $cart['formattedSubtotal'] }}</span>
                    </div>

                    @if(!empty($cart['coupon']))
                        <div class="flex justify-between text-emerald-700 font-bold">
                            <span>Cupón ({{ $cart['coupon']['code'] }})</span>
                            <span>-{{ $cart['formattedDiscount'] }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-gray-600">
                        <span>Entrega (<span x-text="deliveryLabel()"></span>)</span>
                        <span class="font-semibold text-dark" x-text="deliveryChoice === 'pickup' ? '$0.00 MXN (Mostrador)' : (hasValidQuote() ? shippingQuote.formatted_cost : 'Por cotizar con C.P.')"></span>
                    </div>

                    <div class="flex justify-between items-baseline pt-3 border-t border-border">
                        <span class="text-base font-black text-dark uppercase">Total a Pagar</span>
                        <span class="text-2xl font-black text-primary" x-text="formattedGrandTotal()">{{ $totalFormatted }}</span>
                    </div>
                </div>

                <!-- Pasarela de Pago Mercado Pago -->
                <div class="rounded-base border border-[#009EE3]/40 bg-[#009EE3]/5 p-4 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#009EE3] text-white flex items-center justify-center font-black text-xs shrink-0">
                            MP
                        </div>
                        <div>
                            <strong class="block text-sm font-black text-dark">Mercado Pago Checkout Pro</strong>
                            <span class="block text-xs text-gray-600">Tarjetas de crédito/débito, SPEI o efectivo en OXXO.</span>
                        </div>
                    </div>
                    <span class="text-[11px] font-black text-[#009EE3] bg-white px-2.5 py-1 rounded border border-[#009EE3]/30 shrink-0">Seguro</span>
                </div>

                <div class="pt-2 flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="step = 2"
                        class="w-full sm:w-auto border border-border hover:bg-gray-100 text-dark font-bold py-3 px-6 rounded-base text-sm transition-colors cursor-pointer"
                    >
                        &larr; Atrás
                    </button>

                    <button
                        type="submit"
                        class="w-full sm:flex-1 bg-[#009EE3] hover:bg-[#0081BA] text-white font-black py-3.5 px-8 rounded-base text-sm uppercase tracking-wider transition-colors shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>Pagar <span x-text="formattedGrandTotal()">{{ $totalFormatted }}</span> con Mercado Pago</span>
                        <span>&rarr;</span>
                    </button>
                </div>

                <div class="text-center pt-2 border-t border-border space-y-1.5">
                    <p class="text-[11px] text-gray-500">
                        Transacción cifrada y protegida por Mercado Pago · Todas las piezas incluyen garantía escrita.
                    </p>
                    <p class="text-xs text-gray-600">
                        ¿Necesitas factura o tienes dudas?
                        <a
                            href="{{ $whatsappLinkBase }}?text={{ urlencode($whatsappCheckoutText) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-bold text-primary hover:underline"
                        >
                            Escríbenos por WhatsApp &rarr;
                        </a>
                    </p>
                </div>
            </section>
        </form>
    </div>
@endsection
