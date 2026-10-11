@extends('layouts.public', [
    'title' => 'Términos y Condiciones de Uso y Compra',
    'metaDescription' => 'Términos y condiciones de compra, envío y garantía de autopartes usadas en JC Autopartes, Tepatitlán, Jalisco.',
    'canonicalUrl' => route('pages.terms'),
])

@php
    $business = config('business');
@endphp

@section('content')
    <div class="bg-white border-b border-border">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <span class="text-xs font-bold uppercase tracking-wider text-primary">Información Legal</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-dark mt-1">
                Términos y Condiciones de Compra
            </h1>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <div class="rounded-base bg-amber-50 border border-amber-300 px-4 py-3 text-xs sm:text-sm text-amber-900 font-semibold">
            Estado del documento: <strong>Texto provisional — pendiente de revisión legal</strong>.
        </div>

        <div class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm space-y-4 text-sm text-gray-700 leading-relaxed">
            <h2 class="text-base font-extrabold text-dark">1. Naturaleza de las Autopartes Comercializadas</h2>
            <p>
                Todas las refacciones ofrecidas por <strong>{{ $business['name'] }}</strong> son autopartes originales usadas de desmontaje legal, clasificadas por condición física (Grado A, B o C) y verificadas antes de su envío.
            </p>

            <h2 class="text-base font-extrabold text-dark">2. Compatibilidad y Responsabilidad del Comprador</h2>
            <p>
                El cliente deberá verificar con su mecánico o técnico las fotografías, número de parte y años compatibles publicados en la ficha antes de realizar la compra. Nuestro equipo brinda asesoría directa vía WhatsApp al {{ $business['whatsapp_display'] }}.
            </p>

            <h2 class="text-base font-extrabold text-dark">3. Precios, Existencias y Facturación</h2>
            <p>
                Las existencias y precios mostrados en el sitio están sujetos a validación en servidor al momento de confirmar el pedido. En caso de requerir factura, favor de solicitarla por WhatsApp antes de concluir su compra.
            </p>

            <h2 class="text-base font-extrabold text-dark">4. Garantías</h2>
            <p>
                Los plazos de garantía son de 30 días naturales en motores y transmisiones, 15 días en partes internas de motor y 7 días en piezas eléctricas, mecánicas y de carrocería, conforme a nuestra <a href="{{ route('pages.warranty') }}" class="text-primary font-semibold hover:underline">Política de Garantía Oficial</a>.
            </p>
        </div>
    </div>
@endsection
