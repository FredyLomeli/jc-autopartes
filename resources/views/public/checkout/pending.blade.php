@extends('layouts.public', [
    'title' => 'Pago Pendiente de Acreditación — Orden ' . $order->order_number,
    'metaDescription' => 'Tu orden está reservada a la espera de confirmación de pago en OXXO o SPEI.',
    'canonicalUrl' => route('checkout.pending'),
])

@php
    $business = config('business');
    $whatsappText = "Hola JC Autopartes, realicé mi orden web con folio {$order->order_number} en modalidad de pago pendiente (OXXO/SPEI). Adjunto mi comprobante.";
@endphp

@section('content')
    <div class="bg-surface py-8 sm:py-12 border-b border-border">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm space-y-6">
                <!-- Encabezado de Pendiente -->
                <div class="text-center space-y-3 pb-6 border-b border-border">
                    <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 inline-flex items-center justify-center mx-auto ring-8 ring-amber-50">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-extrabold uppercase tracking-wider">
                        Pago en Proceso de Acreditación
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-dark tracking-tight">
                        Tu orden ha sido registrada
                    </h1>
                    <p class="text-sm text-gray-600 max-w-lg mx-auto">
                        Si seleccionaste pago en efectivo (OXXO) o transferencia SPEI, tu refacción queda apartada temporalmente mientras se acredita el pago en Mercado Pago.
                    </p>
                    <div class="pt-2">
                        <span class="text-xs uppercase font-extrabold text-gray-500 block">Número de Folio:</span>
                        <strong class="font-mono text-xl sm:text-2xl font-extrabold text-primary tracking-wider">
                            {{ $order->order_number }}
                        </strong>
                    </div>
                </div>

                <!-- Instrucciones para el Cliente -->
                <div class="bg-amber-50 border border-amber-200 rounded-base p-4 text-xs sm:text-sm text-amber-900 space-y-2">
                    <strong class="block text-sm font-extrabold text-amber-950">Pasos a seguir:</strong>
                    <ol class="list-decimal list-inside space-y-1.5 leading-relaxed text-amber-800">
                        <li>Revisa el correo electrónico o la ficha de pago que generó Mercado Pago con tu código de barras o CLABE interbancaria.</li>
                        <li>Realiza el pago antes de que expire la ficha de depósito.</li>
                        <li>En cuanto Mercado Pago acredite tu pago, el sistema actualizará tu orden automáticamente y comenzaremos a empaquetar tu pieza.</li>
                        <li>Opcional: puedes enviarnos una fotografía de tu ticket por WhatsApp para agilizar la preparación en almacén.</li>
                    </ol>
                </div>

                <!-- Resumen Compacto -->
                <div class="border border-border rounded-base p-4 space-y-2 text-sm bg-surface">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Comprador:</span>
                        <strong class="text-dark">{{ $order->customer_name }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total a pagar:</span>
                        <strong class="text-primary font-mono text-base">${{ number_format($order->total, 2) }} MXN</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Modalidad de entrega:</span>
                        <strong class="text-dark">{{ $order->deliveryMethodLabel() }}</strong>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="pt-4 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
                    <a
                        href="{{ route('home') }}"
                        class="text-xs font-bold text-gray-600 hover:text-dark hover:underline"
                    >
                        &larr; Volver a la página principal
                    </a>

                    <a
                        href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode($whatsappText) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="w-full sm:w-auto bg-whatsapp hover:brightness-95 text-white font-extrabold py-3 px-6 rounded-base text-xs sm:text-sm transition-colors flex items-center justify-center gap-2 shadow-sm"
                    >
                        <span>Enviar comprobante por WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
