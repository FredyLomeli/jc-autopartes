@extends('layouts.public', [
    'title' => 'Política de Garantía Oficial por Escrito (7, 15 y 30 Días)',
    'metaDescription' => 'Conoce los plazos y condiciones de garantía escrita de JC Autopartes: 30 días en motores y transmisiones, 15 días en partes internas y 7 días en eléctrico, mecánica y carrocería.',
    'canonicalUrl' => route('pages.warranty'),
])

@php
    $business = config('business');
@endphp

@section('content')
    <!-- Encabezado de Página -->
    <div class="bg-white border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Respaldo Oficial JC Autopartes
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold text-dark mt-2 tracking-tight">
                Política de Garantía Oficial por Escrito
            </h1>
            <p class="text-sm sm:text-base text-gray-600 mt-2 max-w-3xl leading-relaxed">
                Cada una de nuestras piezas originales usadas es inspeccionada en almacén y cuenta con cobertura de garantía funcional escrita a partir de la fecha de entrega.
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

        <!-- Banner de Facturación y Asesoría Rápida -->
        <div class="rounded-card bg-primary/5 border border-primary/20 p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <h3 class="text-sm font-bold text-dark flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-primary"></span>
                    ¿Necesitas Factura de tu Compra?
                </h3>
                <p class="text-xs sm:text-sm text-gray-600">
                    Para factura fiscal (CFDI), contáctanos por WhatsApp dentro del mes en curso enviando tu <strong>Constancia de Situación Fiscal (CSF)</strong> actualizada y tu número de pedido.
                </p>
            </div>
            <a
                href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, requiero solicitar mi factura fiscal. Adjunto mi CSF.') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-xs font-bold rounded-button shadow-sm hover:bg-primary-dark transition-colors whitespace-nowrap"
            >
                Solicitar Factura por WhatsApp
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>

        <!-- Coberturas por Categoría -->
        <div class="space-y-4">
            <div class="border-b border-border pb-3">
                <h2 class="text-xl font-extrabold text-dark tracking-tight">
                    Plazos de Cobertura por Categoría de Autoparte
                </h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                    El plazo de garantía comienza a correr a partir del momento en que recibes la pieza en tu domicilio o en nuestro mostrador.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- 30 Días -->
                <div class="bg-white rounded-card border-2 border-primary/30 p-6 shadow-sm hover:shadow-md transition-shadow relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 rounded-base bg-primary text-white text-xs font-extrabold uppercase tracking-wide">
                                30 Días Naturales
                            </span>
                            <span class="text-xs font-bold text-gray-400">Categoría Mayor</span>
                        </div>
                        <h3 class="text-lg font-extrabold text-dark">Motores y Transmisiones</h3>
                        <p class="text-xs text-gray-500 mt-1 font-medium">Motores 3/4, 7/8, motores completos y transmisiones (automáticas / manuales).</p>
                        <ul class="mt-4 space-y-2 text-xs text-gray-600 leading-relaxed list-disc list-inside">
                            <li>Cubre compresión adecuada de cilindros.</li>
                            <li>Presión normal de aceite y ausencia de ruidos anormales de biela o cigüeñal.</li>
                            <li>En transmisiones: embrague de velocidades y ausencia de patinamiento interno comprobado.</li>
                        </ul>
                    </div>
                    <div class="mt-6 pt-4 border-t border-border text-[11px] text-gray-500">
                        * Requiere instalación con lubricantes nuevos y reemplazo preventivo de retenes/empaques.
                    </div>
                </div>

                <!-- 15 Días -->
                <div class="bg-white rounded-card border border-border p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 rounded-base bg-amber-500 text-white text-xs font-extrabold uppercase tracking-wide">
                                15 Días Naturales
                            </span>
                            <span class="text-xs font-bold text-gray-400">Partes Internas</span>
                        </div>
                        <h3 class="text-lg font-extrabold text-dark">Componentes de Motor</h3>
                        <p class="text-xs text-gray-500 mt-1 font-medium">Cabezas (culatas), cigüeñales, árboles de levas, múltiples y bombas.</p>
                        <ul class="mt-4 space-y-2 text-xs text-gray-600 leading-relaxed list-disc list-inside">
                            <li>Cabezas sin fisuras internas ni deformaciones fuera de tolerancia de fábrica.</li>
                            <li>Cigüeñales en medida estándar o rectificable certificada en la ficha.</li>
                            <li>Árboles de levas con levas íntegras sin desgaste excesivo.</li>
                        </ul>
                    </div>
                    <div class="mt-6 pt-4 border-t border-border text-[11px] text-gray-500">
                        * Se debe presentar diagnóstico técnico de rectificadora o taller mecánico formal.
                    </div>
                </div>

                <!-- 7 Días -->
                <div class="bg-white rounded-card border border-border p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 rounded-base bg-slate-700 text-white text-xs font-extrabold uppercase tracking-wide">
                                7 Días Naturales
                            </span>
                            <span class="text-xs font-bold text-gray-400">Eléctrico y Mecánica</span>
                        </div>
                        <h3 class="text-lg font-extrabold text-dark">Eléctrico, Colisión y Suspensión</h3>
                        <p class="text-xs text-gray-500 mt-1 font-medium">Alternadores, marchas, calipers, cremalleras, faros, cofres y sensores.</p>
                        <ul class="mt-4 space-y-2 text-xs text-gray-600 leading-relaxed list-disc list-inside">
                            <li>Marchas y alternadores probados en banco de pruebas antes del desmontaje.</li>
                            <li>Sistemas de dirección y suspensión sin holguras estructurales ni fugas de retén.</li>
                            <li>Carrocería sin fracturas no declaradas en la ficha de condición.</li>
                        </ul>
                    </div>
                    <div class="mt-6 pt-4 border-t border-border text-[11px] text-gray-500">
                        * No incluye piezas con sellos rotos o arneses cortados/modificados por el instalador.
                    </div>
                </div>
            </div>
        </div>

        <!-- Requisitos y Condiciones Esenciales -->
        <div class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm">
            <h2 class="text-xl font-extrabold text-dark mb-4">Condiciones Obligatorias para Aplicar la Garantía</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700 leading-relaxed">
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary font-bold text-xs shrink-0">1</span>
                        <div>
                            <strong>Folio o Comprobante de Compra:</strong>
                            <p class="text-xs text-gray-600 mt-0.5">Presentar el número de pedido en línea (formato <code class="bg-gray-100 px-1 py-0.5 rounded text-dark font-mono font-bold">JC-YYYY-XXXXX</code>) o la nota de venta física expedida en sucursal.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary font-bold text-xs shrink-0">2</span>
                        <div>
                            <strong>Sellos de Seguridad Intactos:</strong>
                            <p class="text-xs text-gray-600 mt-0.5">Todas nuestras autopartes están marcadas con pintura indeleble de seguridad, sellos térmicos o marcas de identificación. Si los sellos han sido borrados, rotos, desarmados o alterados, la garantía se anula de inmediato.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary font-bold text-xs shrink-0">3</span>
                        <div>
                            <strong>Instalación por Personal Calificado:</strong>
                            <p class="text-xs text-gray-600 mt-0.5">La refacción debe haber sido instalada por un mecánico o técnico calificado respetando las especificaciones del fabricante del vehículo, torques recomendados y utilizando fluidos nuevos limpios.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-800 font-bold text-xs shrink-0">4</span>
                        <div>
                            <strong>Exclusión por Sobrecalentamiento o Falta de Lubricación:</strong>
                            <p class="text-xs text-gray-600 mt-0.5">En motores y cajas no aplica garantía cuando el daño sea producto de radiadores tapados, termostatos defectuosos, bombas de agua rotas o reutilización de aceites usados.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-800 font-bold text-xs shrink-0">5</span>
                        <div>
                            <strong>Piezas Electrónicas Sensibles (ECU / Módulos):</strong>
                            <p class="text-xs text-gray-600 mt-0.5">Las computadoras (ECU/ECM) requieren diagnóstico escaneado previo de la instalación. No hay garantía si el módulo sufre quemaduras por cortocircuito provocado por la instalación eléctrica del vehículo.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary/10 text-primary font-bold text-xs shrink-0">6</span>
                        <div>
                            <strong>Resolución de Garantía:</strong>
                            <p class="text-xs text-gray-600 mt-0.5">Al validar la garantía se procederá prioritariamente al reemplazo por otra refacción equivalente de nuestro inventario. Si no hubiera existencia disponible, se expedirá nota de crédito o reembolso del importe de la pieza.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Procedimiento Paso a Paso para Reclamar Garantía -->
        <div class="bg-gray-50 rounded-card border border-border p-6 sm:p-8">
            <h2 class="text-xl font-extrabold text-dark mb-4">¿Cómo Iniciar un Proceso de Garantía?</h2>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-base border border-border">
                    <span class="text-xs font-bold text-primary">Paso 1</span>
                    <h4 class="font-bold text-dark text-sm mt-1">Escríbenos por WhatsApp</h4>
                    <p class="text-xs text-gray-600 mt-1">Envía mensaje al <strong>{{ $business['whatsapp_display'] }}</strong> indicando tu folio de compra <span class="font-mono font-semibold">JC-YYYY-XXXXX</span>.</p>
                </div>
                <div class="bg-white p-4 rounded-base border border-border">
                    <span class="text-xs font-bold text-primary">Paso 2</span>
                    <h4 class="font-bold text-dark text-sm mt-1">Envía Evidencia Visual</h4>
                    <p class="text-xs text-gray-600 mt-1">Comparte fotos claras donde se aprecien los sellos intactos de JC Autopartes y un video breve ilustrando la falla reportada.</p>
                </div>
                <div class="bg-white p-4 rounded-base border border-border">
                    <span class="text-xs font-bold text-primary">Paso 3</span>
                    <h4 class="font-bold text-dark text-sm mt-1">Diagnóstico Rápido</h4>
                    <p class="text-xs text-gray-600 mt-1">Nuestro equipo técnico de almacén revisa el caso en menos de 24 a 48 horas hábiles para autorizar el retorno.</p>
                </div>
                <div class="bg-white p-4 rounded-base border border-border">
                    <span class="text-xs font-bold text-primary">Paso 4</span>
                    <h4 class="font-bold text-dark text-sm mt-1">Reemplazo o Reembolso</h4>
                    <p class="text-xs text-gray-600 mt-1">Una vez recibida la pieza física en bodega, enviamos tu refacción de reemplazo o procesamos la devolución correspondiente.</p>
                </div>
            </div>
        </div>

        <!-- Ubicación física y contacto de atención -->
        <div class="bg-white rounded-card border border-border p-6 text-xs sm:text-sm text-gray-600 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <p class="font-bold text-dark">Atención a Clientes y Devoluciones en Mostrador:</p>
                <p class="mt-0.5">{{ $business['name'] }} — {{ $business['address'] }}</p>
                <p class="mt-0.5 text-gray-500">Horario: {{ $business['hours']['weekdays'] }} | {{ $business['hours']['saturday'] }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="tel:{{ $business['phone_link'] }}" class="text-primary font-bold hover:underline">
                    {{ $business['phone_display'] }}
                </a>
                <span class="text-gray-300">|</span>
                <a href="mailto:{{ $business['email'] }}" class="text-primary font-bold hover:underline">
                    {{ $business['email'] }}
                </a>
            </div>
        </div>

    </div>
@endsection
