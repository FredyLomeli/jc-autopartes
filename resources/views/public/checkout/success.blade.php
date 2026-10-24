@extends('layouts.public', [
    'title' => '¡Pago Confirmado! — Orden ' . $order->order_number,
    'metaDescription' => 'Tu compra ha sido acreditada exitosamente en JC Autopartes. Consulta aquí los detalles de tu orden y envío.',
    'canonicalUrl' => route('checkout.success'),
])

@php
    $business = config('business');
    $items = $order->items;
    $whatsappText = "Hola JC Autopartes, acabo de realizar mi compra web con folio {$order->order_number} a nombre de {$order->customer_name}. Deseo dar seguimiento a mi pedido.";
@endphp

@section('content')
    <div class="bg-surface py-8 sm:py-12 border-b border-border">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Tarjeta de Confirmación de Compra -->
            <div class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm space-y-6">
                <!-- Encabezado de Aprobación -->
                <div class="text-center space-y-3 pb-6 border-b border-border">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 inline-flex items-center justify-center mx-auto ring-8 ring-emerald-50">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-extrabold uppercase tracking-wider">
                        Pago Acreditado Exitosamente
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-dark tracking-tight">
                        ¡Gracias por tu compra, {{ $order->customer_name }}!
                    </h1>
                    <p class="text-sm text-gray-600 max-w-xl mx-auto">
                        Hemos recibido el pago de tu refacción a través de Mercado Pago. Tu pedido ya está siendo preparado en nuestro almacén en Tepatitlán, Jalisco.
                    </p>
                    <div class="pt-2">
                        <span class="text-xs uppercase font-extrabold text-gray-500 block">Número de Folio:</span>
                        <strong class="font-mono text-xl sm:text-2xl font-extrabold text-primary tracking-wider">
                            {{ $order->order_number }}
                        </strong>
                    </div>
                </div>

                <!-- Detalles de Entrega y Comprador -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div class="bg-surface rounded-base border border-border p-4 space-y-2">
                        <h2 class="text-xs font-extrabold uppercase tracking-wider text-dark border-b border-border pb-2">
                            Datos del Comprador
                        </h2>
                        <p><strong class="text-gray-600">Nombre:</strong> <span class="font-bold text-dark">{{ $order->customer_name }}</span></p>
                        <p><strong class="text-gray-600">Correo:</strong> <span class="font-bold text-dark">{{ $order->customer_email }}</span></p>
                        <p><strong class="text-gray-600">Teléfono:</strong> <span class="font-bold text-dark">{{ $order->customer_phone }}</span></p>
                    </div>

                    <div class="bg-surface rounded-base border border-border p-4 space-y-2">
                        <h2 class="text-xs font-extrabold uppercase tracking-wider text-dark border-b border-border pb-2">
                            Modalidad de Entrega
                        </h2>
                        <p>
                            <span class="px-2 py-0.5 rounded text-xs font-bold {{ $order->isShipping() ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $order->deliveryMethodLabel() }}
                            </span>
                        </p>
                        @if($order->isShipping())
                            <p class="text-xs text-gray-700 leading-relaxed">
                                <strong>Destino:</strong> {{ $order->shipping_address }}, Col. {{ $order->shipping_neighborhood }}, {{ $order->shipping_city }}, {{ $order->shipping_state }}. C.P. {{ $order->shipping_postal_code }}.
                            </p>
                            @if($order->shipping_notes)
                                <p class="text-xs text-gray-500 italic">Notas: {{ $order->shipping_notes }}</p>
                            @endif
                        @else
                            <p class="text-xs text-gray-700 leading-relaxed">
                                <strong>Dirección de Sucursal:</strong> {{ $business['address'] }}
                            </p>
                            <p class="text-xs text-gray-500">Horario: {{ $business['hours']['weekdays'] }}</p>
                        @endif
                    </div>
                </div>

                <!-- Partidas del Pedido -->
                <div class="space-y-3">
                    <h2 class="text-base font-extrabold text-dark border-b border-border pb-2">
                        Refacciones Compradas
                    </h2>
                    <div class="divide-y divide-border border border-border rounded-base overflow-hidden">
                        @foreach($items as $item)
                            <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white hover:bg-surface/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    @if($item->photo_url)
                                        <img src="{{ $item->photo_url }}" alt="{{ $item->product_title }}" class="w-14 h-14 rounded object-cover border border-border shrink-0">
                                    @else
                                        <div class="w-14 h-14 rounded bg-gray-100 border border-border flex items-center justify-center text-gray-400 shrink-0">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-extrabold text-dark text-sm leading-snug">{{ $item->product_title }}</p>
                                        <p class="text-xs text-gray-500">
                                            SKU: <strong class="font-mono">{{ $item->product_sku }}</strong>
                                            @if($item->part_number)
                                                · No. Parte: <strong class="font-mono">{{ $item->part_number }}</strong>
                                            @endif
                                            · Cantidad: <strong>{{ $item->quantity }}</strong>
                                            · Garantía: <strong>{{ $item->warranty_days }} días</strong>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right sm:shrink-0">
                                    <span class="text-sm font-extrabold text-dark block">
                                        ${{ number_format($item->subtotal, 2) }} MXN
                                    </span>
                                    <span class="text-xs text-gray-400 block">
                                        (${{ number_format($item->unit_price, 2) }} c/u)
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Desglose de Totales -->
                <div class="bg-surface rounded-base border border-border p-4 space-y-2 text-sm max-w-sm ml-auto">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal de mercancía:</span>
                        <span class="font-bold text-dark">${{ number_format($order->items_subtotal, 2) }} MXN</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Costo de envío:</span>
                        <span class="font-bold text-dark">
                            {{ $order->shipping_cost > 0 ? '$' . number_format($order->shipping_cost, 2) . ' MXN' : 'Sin costo adicional' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-baseline pt-2 border-t border-border">
                        <span class="font-extrabold text-dark text-base">Total Pagado:</span>
                        <span class="font-extrabold text-primary text-xl font-mono">${{ number_format($order->total, 2) }} MXN</span>
                    </div>
                </div>

                <!-- Acciones y Soporte WhatsApp -->
                <div class="pt-4 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
                    <a
                        href="{{ route('catalog.index') }}"
                        class="text-xs font-bold text-primary hover:underline"
                    >
                        &larr; Volver al catálogo de refacciones
                    </a>

                    <a
                        href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode($whatsappText) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="w-full sm:w-auto bg-whatsapp hover:brightness-95 text-white font-extrabold py-3 px-6 rounded-base text-xs sm:text-sm transition-colors flex items-center justify-center gap-2 shadow-sm"
                    >
                        <span>Escribir por WhatsApp sobre mi pedido (Folio {{ $order->order_number }})</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
