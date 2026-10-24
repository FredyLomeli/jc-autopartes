@extends('layouts.public', [
    'title' => 'No se pudo procesar el pago — JC Autopartes',
    'metaDescription' => 'Hubo un inconveniente al procesar tu pago en Mercado Pago. Puedes intentar con otro método o contactarnos.',
    'canonicalUrl' => route('checkout.failure'),
])

@php
    $business = config('business');
    $orderNumber = $order?->order_number ?? 'Pendiente';
    $whatsappText = "Hola JC Autopartes, tuve problemas para pagar mi pedido web {$orderNumber}. ¿Podrían ayudarme a completarlo?";
@endphp

@section('content')
    <div class="bg-surface py-8 sm:py-12 border-b border-border">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm space-y-6">
                <!-- Encabezado de Error -->
                <div class="text-center space-y-3 pb-6 border-b border-border">
                    <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 inline-flex items-center justify-center mx-auto ring-8 ring-red-50">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <span class="inline-block px-3 py-1 rounded-full bg-red-100 text-red-800 text-xs font-extrabold uppercase tracking-wider">
                        Pago Rechazado o No Completado
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-dark tracking-tight">
                        No se pudo procesar tu pago
                    </h1>
                    <p class="text-sm text-gray-600 max-w-lg mx-auto">
                        La entidad bancaria o Mercado Pago no pudieron autorizar la transacción. No se ha realizado ningún cargo a tu cuenta.
                    </p>
                    @if($order)
                        <div class="pt-2">
                            <span class="text-xs uppercase font-extrabold text-gray-500 block">Número de Folio:</span>
                            <strong class="font-mono text-xl sm:text-2xl font-extrabold text-dark tracking-wider">
                                {{ $order->order_number }}
                            </strong>
                        </div>
                    @endif
                </div>

                <!-- Motivos Comunes y Soluciones -->
                <div class="bg-surface border border-border rounded-base p-4 text-xs sm:text-sm text-gray-700 space-y-2">
                    <strong class="block text-sm font-extrabold text-dark">¿Por qué pudo haber fallado?</strong>
                    <ul class="list-disc list-inside space-y-1 text-gray-600 leading-relaxed">
                        <li>Fondos insuficientes o límite de compras por internet superado.</li>
                        <li>Tu banco requiere autorización previa a través de su aplicación móvil (Token digital / CVV dinámico).</li>
                        <li>Datos de la tarjeta ingresados con algún error de captura.</li>
                        <li>La sesión de pago expiró por inactividad.</li>
                    </ul>
                </div>

                <!-- Botones de Acción -->
                <div class="pt-4 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <a
                            href="{{ route('checkout.index') }}"
                            class="w-full sm:w-auto bg-primary hover:bg-primary-dark text-white font-extrabold py-3 px-6 rounded-base text-xs sm:text-sm transition-colors text-center shadow-sm"
                        >
                            Reintentar Checkout
                        </a>
                        <a
                            href="{{ route('cart.index') }}"
                            class="text-xs font-bold text-gray-600 hover:text-dark hover:underline whitespace-nowrap"
                        >
                            Ver Carrito
                        </a>
                    </div>

                    <a
                        href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode($whatsappText) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="w-full sm:w-auto bg-whatsapp hover:brightness-95 text-white font-extrabold py-3 px-6 rounded-base text-xs sm:text-sm transition-colors flex items-center justify-center gap-2 shadow-sm"
                    >
                        <span>Asistencia por WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
