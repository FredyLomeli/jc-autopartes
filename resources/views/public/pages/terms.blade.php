@extends('layouts.public', [
    'title' => 'Términos y Condiciones de Uso y Compra',
    'metaDescription' => 'Términos y condiciones de compra, pago con Mercado Pago, envíos, facturación y garantía de autopartes usadas en JC Autopartes, Tepatitlán, Jalisco.',
    'canonicalUrl' => route('pages.terms'),
])

@php
    $business = config('business');
@endphp

@section('content')
    <div class="bg-white border-b border-border">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Marco Legal y Comercial
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold text-dark mt-2 tracking-tight">
                Términos y Condiciones de Compra y Uso
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-2">
                Última actualización: Octubre 2026 • Aplicable a ventas en sucursal y a través de nuestra plataforma web oficial.
            </p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

        <!-- Destacado de Facturación -->
        <div class="rounded-card bg-amber-50 border border-amber-300/80 p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <span class="inline-block px-2.5 py-0.5 rounded-base bg-amber-200 text-amber-900 font-bold text-xs uppercase tracking-wide">
                    Políticas de Facturación
                </span>
                <h3 class="text-sm font-bold text-dark mt-1">¿Requieres Factura Fiscal (CFDI)?</h3>
                <p class="text-xs sm:text-sm text-gray-700 leading-relaxed">
                    <strong>Para factura, escríbenos por WhatsApp</strong> dentro del mes calendario en el que realizaste tu compra, adjuntando tu <strong>Constancia de Situación Fiscal (CSF)</strong> actualizada en PDF, uso de CFDI y tu número de pedido oficial (<span class="font-mono font-semibold">JC-YYYY-XXXXX</span>).
                </p>
            </div>
            <a
                href="https://wa.me/{{ $business['whatsapp_number'] }}?text={{ urlencode('Hola JC Autopartes, solicito factura de mi pedido. Adjunto mi CSF y número de orden.') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-button shadow-sm transition-colors whitespace-nowrap"
            >
                Solicitar Factura por WhatsApp
            </a>
        </div>

        <div class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm space-y-8 text-sm text-gray-700 leading-relaxed">

            <!-- 1. Generalidades -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">1.</span>
                    Identidad del Vendedor y Aceptación de los Términos
                </h2>
                <p>
                    El presente contrato regula los términos de compraventa aplicables a los usuarios del sitio web y clientes en mostrador de <strong>{{ $business['name'] }}</strong>, con domicilio comercial en {{ $business['address'] }}.
                </p>
                <p>
                    Al navegar por este sitio web, registrar un pedido o efectuar un pago mediante nuestra pasarela en línea, el cliente declara ser mayor de edad con plena capacidad legal para obligarse y acepta expresamente las cláusulas estipuladas en este documento.
                </p>
            </section>

            <!-- 2. Naturaleza de las Autopartes -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">2.</span>
                    Naturaleza de las Autopartes Comercializadas
                </h2>
                <p>
                    Todas las piezas comercializadas por <strong>{{ $business['name'] }}</strong> son autopartes originales usadas recuperadas legalmente de vehículos dados de baja o en desarme autorizado.
                </p>
                <p>
                    Por tratarse de refacciones usadas de desmonte, las piezas pueden presentar marcas, ligeros rayones estéticos, grasa o polvo propio del uso y almacenamiento, los cuales no comprometen su funcionalidad mecánica ni estructural. Nuestras piezas son catalogadas en grados estéticos y de condición física (Grado A: excelente, Grado B: desgaste normal, Grado C: funcional con detalles cosméticos).
                </p>
            </section>

            <!-- 3. Compatibilidad Técnica -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">3.</span>
                    Responsabilidad del Comprador en Compatibilidad
                </h2>
                <p>
                    Es responsabilidad exclusiva del cliente verificar que el número de parte (OEM), versión de motorización, año, modelo y características físicas de la refacción mostrada en las fotografías coincidan exactamente con la pieza requerida por su vehículo antes de confirmar el pago.
                </p>
                <p>
                    JC Autopartes pone a disposición de sus clientes atención y asesoría técnica gratuita vía WhatsApp al <strong>{{ $business['whatsapp_display'] }}</strong> para corroborar medidas, números de serie y compatibilidad antes del envío.
                </p>
            </section>

            <!-- 4. Pasarela de Pago y Checkout Seguro -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">4.</span>
                    Checkout Seguro y Métodos de Pago con Mercado Pago
                </h2>
                <p>
                    Las operaciones de cobro en línea son procesadas de manera segura y encriptada por la plataforma <strong>Mercado Pago México S. de R.L. de C.V.</strong>, institución de tecnología financiera certificada bajo el estándar de seguridad de datos de la industria de tarjetas de pago (PCI-DSS).
                </p>
                <ul class="list-disc list-inside space-y-1.5 text-xs sm:text-sm pl-2">
                    <li><strong>Métodos Aceptados:</strong> Tarjetas de crédito (con opciones de mensualidades según promociones vigentes), tarjetas de débito bancarias mexicanas, transferencias electrónicas SPEI en tiempo real y pagos en efectivo en tiendas de conveniencia de la red Mercado Pago (ej. OXXO).</li>
                    <li><strong>Seguridad de Datos Bancarios:</strong> JC Autopartes no recopila, no tiene acceso y en ningún caso almacena números completos de tarjetas de crédito/débito, fechas de vencimiento o códigos CVV. La transacción se ejecuta en el entorno seguro de Mercado Pago.</li>
                    <li><strong>Acreditación de Pago:</strong> Los pagos con tarjeta y SPEI se confirman típicamente en cuestión de minutos. Los pagos en efectivo en tiendas de conveniencia se procesan en un plazo de 1 a 24 horas hábiles a partir de la recepción en caja. El inventario se reserva únicamente durante la vigencia de la orden de pago.</li>
                </ul>
            </section>

            <!-- 5. Envíos y Tiempos de Entrega -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">5.</span>
                    Envíos Nacionales y Entrega en Sucursal
                </h2>
                <p>
                    Realizamos envíos a toda la República Mexicana a través de empresas de paquetería consolidada de reconocido prestigio (como Paquetexpress u operadores equivalentes).
                </p>
                <ul class="list-disc list-inside space-y-1.5 text-xs sm:text-sm pl-2">
                    <li><strong>Dirección de Entrega:</strong> Es obligación del comprador proporcionar una dirección completa, con código postal correcto de 5 dígitos y referencias legibles. JC Autopartes no asume responsabilidad por retrasos derivados de direcciones erróneas o zonas de difícil acceso ("zonas extendidas").</li>
                    <li><strong>Embalaje Especializado:</strong> Cada autoparte se embala cuidadosamente en caja reforzada, película plástica o tarima de madera según su peso y volumen para garantizar su protección en tránsito.</li>
                    <li><strong>Recolección en Mostrador:</strong> El cliente puede optar por recoger personalmente su pedido en nuestras instalaciones de Tepatitlán de Morelos presentando una identificación oficial vigente y el folio de pedido.</li>
                </ul>
            </section>

            <!-- 6. Políticas de Facturación Formal -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">6.</span>
                    Políticas de Facturación Fiscal (CFDI 4.0)
                </h2>
                <p>
                    Todos los precios publicados en la tienda en línea y mostrador incluyen el Impuesto al Valor Agregado (IVA) correspondiente.
                </p>
                <p>
                    Conforme a la legislación fiscal vigente del Servicio de Administración Tributaria (SAT), las facturas electrónicas deben expedirse en el mismo mes en que se percibió el pago. Por ello, el cliente deberá solicitar su CFDI enviando su Constancia de Situación Fiscal (CSF) completa y legible vía WhatsApp o correo electrónico (<a href="mailto:{{ $business['email'] }}" class="text-primary font-semibold hover:underline">{{ $business['email'] }}</a>) antes de que concluya el mes de compra. No se emitirán facturas de meses fiscales anteriores una vez cerrado el periodo mensual de ventas al público en general.
                </p>
            </section>

            <!-- 7. Garantías y Devoluciones -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">7.</span>
                    Garantías, Devoluciones y Cancelaciones
                </h2>
                <p>
                    Todas las compras están respaldadas por nuestra <a href="{{ route('pages.warranty') }}" class="text-primary font-semibold hover:underline">Política de Garantía Oficial por Escrito</a> de 7, 15 o 30 días naturales según la categoría de la autoparte.
                </p>
                <p>
                    En cancelaciones solicitadas por el cliente antes del despacho de la mercancía, se reembolsará el importe íntegro por el mismo método de pago utilizado. En devoluciones por incompatibilidad atribuible a error de selección del comprador una vez despachado el producto, los costos logísticos de flete de ida y vuelta correrán por cuenta del cliente, y la refacción deberá devolverse en las mismas condiciones, con empaque original y sellos intactos.
                </p>
            </section>

            <!-- 8. Jurisdicción y Marco Legal -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">8.</span>
                    Legislación Aplicable y Jurisdicción
                </h2>
                <p>
                    Para la interpretación y cumplimiento del presente instrumento, las partes se someten a las leyes federales de los Estados Unidos Mexicanos, a la Ley Federal de Protección al Consumidor (PROFECO) y a la competencia de los tribunales correspondientes a la ciudad de Tepatitlán de Morelos, Jalisco, renunciando a cualquier otro fuero que por razón de sus domicilios presentes o futuros pudiera corresponderles.
                </p>
            </section>

        </div>

    </div>
@endsection
