@extends('layouts.public', [
    'title' => 'Contacto y Ubicación en Tepatitlán, Jalisco',
    'metaDescription' => 'Visítanos en Blvd. Lic. Anacleto González Flores Sur #2090, C.P. 47600, Tepatitlán de Morelos, Jalisco, o cotiza al WhatsApp 33 3166 5290 y Tel. 378 688 2205.',
    'canonicalUrl' => route('pages.contact'),
])

@php
    $business = config('business');
@endphp

@section('content')
    <div class="bg-white border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <span class="text-xs font-bold uppercase tracking-wider text-primary">Atención Personalizada</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-dark mt-1">
                Contacto y Sucursal en Tepatitlán, Jalisco
            </h1>
            <p class="text-sm text-gray-600 mt-1 max-w-2xl">
                Atendemos entregas en mostrador, cotizaciones por WhatsApp y envíos por paquetería a toda la República Mexicana.
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Datos Oficiales del Negocio -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-card border border-border p-6 shadow-sm space-y-5">
                    <h2 class="text-lg font-extrabold text-dark border-b border-border pb-3">
                        Datos de Mostrador y Bodega
                    </h2>

                    <div class="space-y-4 text-sm">
                        <div>
                            <span class="block text-xs font-bold uppercase text-gray-500">Dirección Física</span>
                            <p class="font-semibold text-dark mt-0.5">{{ $business['address'] }}</p>
                        </div>

                        <div>
                            <span class="block text-xs font-bold uppercase text-gray-500">WhatsApp de Ventas</span>
                            <a href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, solicito información sobre una refacción.') }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 text-base font-extrabold text-whatsapp hover:underline mt-0.5">
                                <span>{{ $business['whatsapp_display'] }}</span>
                            </a>
                        </div>

                        <div>
                            <span class="block text-xs font-bold uppercase text-gray-500">Teléfono de Sucursal</span>
                            <a href="tel:{{ $business['phone_link'] }}" class="text-base font-extrabold text-dark hover:text-primary mt-0.5 inline-block">
                                {{ $business['phone_display'] }}
                            </a>
                        </div>

                        <div>
                            <span class="block text-xs font-bold uppercase text-gray-500">Correo Electrónico</span>
                            <a href="mailto:{{ $business['email'] }}" class="font-semibold text-primary hover:underline mt-0.5 inline-block">
                                {{ $business['email'] }}
                            </a>
                        </div>

                        <div>
                            <span class="block text-xs font-bold uppercase text-gray-500">Horarios de Atención</span>
                            <ul class="mt-1 space-y-1 text-gray-700">
                                <li>{{ $business['hours']['weekdays'] }}</li>
                                <li>{{ $business['hours']['saturday'] }}</li>
                                <li>{{ $business['hours']['sunday'] }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Fotografías de la Sucursal -->
                <div class="grid grid-cols-2 gap-4">
                    <img
                        src="{{ asset('imagenes/local/fachada.jpg') }}"
                        alt="Fachada de JC Autopartes en Tepatitlán"
                        class="w-full h-40 object-cover rounded-card border border-border"
                    >
                    <img
                        src="{{ asset('imagenes/local/fachada2.jpg') }}"
                        alt="Ingreso a sucursal JC Autopartes"
                        class="w-full h-40 object-cover rounded-card border border-border"
                    >
                </div>
            </div>

            <!-- Formulario Visual de Solicitud de Pieza -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm">
                    <h2 class="text-lg font-extrabold text-dark">Solicitar Cotización de Refacción</h2>
                    <p class="text-xs text-gray-600 mt-1 mb-6">
                        Para respuesta inmediata te recomendamos escribirnos directamente a nuestro WhatsApp oficial.
                    </p>

                    <form action="{{ route('pages.contact') }}" method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact-name" class="block text-xs font-bold uppercase text-gray-700 mb-1">Nombre completo</label>
                                <input id="contact-name" type="text" placeholder="Tu nombre" class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm">
                            </div>
                            <div>
                                <label for="contact-phone" class="block text-xs font-bold uppercase text-gray-700 mb-1">Teléfono / WhatsApp</label>
                                <input id="contact-phone" type="tel" placeholder="10 dígitos" class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact-vehicle" class="block text-xs font-bold uppercase text-gray-700 mb-1">Marca, Modelo y Año del Vehículo</label>
                                <input id="contact-vehicle" type="text" placeholder="Ej. Nissan Versa 2018 1.6L" class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm">
                            </div>
                            <div>
                                <label for="contact-cp" class="block text-xs font-bold uppercase text-gray-700 mb-1">Código Postal de Destino</label>
                                <input id="contact-cp" type="text" placeholder="Ej. 44100" class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm">
                            </div>
                        </div>

                        <div>
                            <label for="contact-message" class="block text-xs font-bold uppercase text-gray-700 mb-1">Descripción de la pieza que buscas</label>
                            <textarea id="contact-message" rows="4" placeholder="Indica qué pieza necesitas, lado o número de parte..." class="w-full rounded-base border border-border bg-surface px-3.5 py-2.5 text-sm"></textarea>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            <a
                                href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, busco cotizar una refacción.') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="bg-whatsapp hover:brightness-95 text-white font-extrabold px-6 py-3 rounded-base text-sm transition-colors inline-flex items-center gap-2"
                            >
                                <span>Enviar consulta por WhatsApp ({{ $business['whatsapp_display'] }})</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
