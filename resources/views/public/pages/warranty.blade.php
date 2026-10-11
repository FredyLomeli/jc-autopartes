@extends('layouts.public', [
    'title' => 'Política de Garantía Oficial por Escrito (7, 15 y 30 Días)',
    'metaDescription' => 'Conoce los plazos y condiciones de garantía escrita de JC Autopartes: 30 días en motores y transmisiones, 15 días en partes internas y 7 días en eléctrico, mecánica y carrocería.',
    'canonicalUrl' => route('pages.warranty'),
])

@php
    $business = config('business');
@endphp

@section('content')
    <div class="bg-white border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <span class="text-xs font-bold uppercase tracking-wider text-primary">Respaldo Oficial JC Autopartes</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-dark mt-1">
                Política de Garantía Oficial por Escrito
            </h1>
            <p class="text-sm text-gray-600 mt-1 max-w-2xl">
                Todas nuestras piezas usadas originales cuentan con cobertura de garantía funcional por escrito según su categoría.
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        <!-- Aviso de revisión legal -->
        <div class="rounded-base bg-amber-50 border border-amber-300 px-4 py-3 text-xs sm:text-sm text-amber-900 font-semibold">
            Nota: Texto informativo de condiciones comerciales — <strong>pendiente de revisión legal definitiva</strong>.
        </div>

        <!-- Plazos de Garantía -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($business['warranties'] as $warranty)
                <div class="bg-white rounded-card border border-border p-6 shadow-sm">
                    <span class="inline-block px-3 py-1 rounded-base bg-primary text-white text-xs font-extrabold uppercase mb-3">
                        {{ $warranty['days'] }} Días Naturales
                    </span>
                    <h2 class="text-lg font-extrabold text-dark">{{ $warranty['title'] }}</h2>
                    <p class="text-sm text-gray-600 mt-2 leading-relaxed">{{ $warranty['description'] }}</p>
                </div>
            @endforeach
        </div>

        <!-- Condiciones Esenciales y Certificado Oficial -->
        <div class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-4">
                <h2 class="text-xl font-extrabold text-dark">Condiciones Esenciales para Hacer Válida la Garantía</h2>
                <ul class="space-y-2.5 text-sm text-gray-700 list-disc list-inside leading-relaxed">
                    <li>Presentar nota de venta, ticket o comprobante de compra con folio de <strong>{{ $business['name'] }}</strong>.</li>
                    <li>La pieza deberá conservar intactos todos los sellos de pintura, marcas de seguridad y etiquetas de nuestro almacén.</li>
                    <li>La instalación debe ser realizada por personal técnico calificado utilizando los fluidos, empaques y torques especificados por el fabricante.</li>
                    <li>En motores y transmisiones no aplica garantía por calentamiento derivado de fallas en el sistema de enfriamiento externo o falta de lubricación.</li>
                    <li>En caso de requerir aplicación de garantía, se realiza el cambio físico por otra pieza igual; si no hubiera en existencia, se procede conforme a lo acordado en mostrador.</li>
                </ul>
            </div>
            <div class="lg:col-span-5">
                <img
                    src="{{ asset('imagenes/banner/garantia2.jpg') }}"
                    alt="Certificado de Garantía Oficial JC Autopartes"
                    class="w-full max-w-sm mx-auto rounded-card border border-border shadow-sm"
                >
            </div>
        </div>
    </div>
@endsection
