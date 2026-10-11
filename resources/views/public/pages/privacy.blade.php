@extends('layouts.public', [
    'title' => 'Aviso de Privacidad',
    'metaDescription' => 'Aviso de privacidad para clientes y usuarios del sitio web de JC Autopartes en Tepatitlán de Morelos, Jalisco.',
    'canonicalUrl' => route('pages.privacy'),
])

@php
    $business = config('business');
@endphp

@section('content')
    <div class="bg-white border-b border-border">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <span class="text-xs font-bold uppercase tracking-wider text-primary">Información Legal</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-dark mt-1">
                Aviso de Privacidad
            </h1>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <div class="rounded-base bg-amber-50 border border-amber-300 px-4 py-3 text-xs sm:text-sm text-amber-900 font-semibold">
            Estado del documento: <strong>Texto provisional — pendiente de revisión legal</strong>.
        </div>

        <div class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm space-y-4 text-sm text-gray-700 leading-relaxed">
            <h2 class="text-base font-extrabold text-dark">1. Identidad y Domicilio del Responsable</h2>
            <p>
                <strong>{{ $business['name'] }}</strong>, con domicilio ubicado en {{ $business['address'] }}, es responsable del tratamiento y protección de los datos personales recabados a través de este sitio web, vía telefónica y mediante atención por WhatsApp.
            </p>

            <h2 class="text-base font-extrabold text-dark">2. Datos Personales Recabados</h2>
            <p>
                Para gestionar cotizaciones, pedidos y envíos de refacciones recabamos nombre completo, teléfono de contacto, correo electrónico, dirección de entrega (incluyendo código postal) y datos de identificación del vehículo para corroborar compatibilidad.
            </p>

            <h2 class="text-base font-extrabold text-dark">3. Finalidades del Tratamiento</h2>
            <p>
                Sus datos personales se utilizan exclusivamente para procesar solicitudes de cotización, confirmar existencias en almacén, coordinar envíos por paquetería o entregas en sucursal y emitir certificados de garantía.
            </p>

            <h2 class="text-base font-extrabold text-dark">4. Contacto para Derechos ARCO</h2>
            <p>
                Para ejercer sus derechos de Acceso, Rectificación, Cancelación u Oposición (ARCO), puede comunicarse al correo electrónico <a href="mailto:{{ $business['email'] }}" class="text-primary font-semibold hover:underline">{{ $business['email'] }}</a> o al teléfono {{ $business['phone_display'] }}.
            </p>
        </div>
    </div>
@endsection
