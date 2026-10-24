@extends('layouts.public', [
    'title' => 'Aviso de Privacidad Integral',
    'metaDescription' => 'Aviso de privacidad integral para clientes y usuarios del sitio web y mostrador de JC Autopartes en Tepatitlán de Morelos, Jalisco. Cumplimiento con la LFPDPPP.',
    'canonicalUrl' => route('pages.privacy'),
])

@php
    $business = config('business');
@endphp

@section('content')
    <div class="bg-white border-b border-border">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Protección de Datos Personales
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold text-dark mt-2 tracking-tight">
                Aviso de Privacidad Integral
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-2">
                En cumplimiento con la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP) y su Reglamento.
            </p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

        <div class="bg-white rounded-card border border-border p-6 sm:p-8 shadow-sm space-y-8 text-sm text-gray-700 leading-relaxed">

            <!-- 1. Identidad del Responsable -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">1.</span>
                    Identidad y Domicilio del Responsable
                </h2>
                <p>
                    <strong>{{ $business['name'] }}</strong>, con domicilio comercial ubicado en {{ $business['address'] }}, es el responsable del uso, protección y tratamiento de los datos personales proporcionados por sus clientes y usuarios a través de este portal web, líneas de atención telefónica, mensajería WhatsApp y mostrador físico.
                </p>
            </section>

            <!-- 2. Datos Personales Recabados -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">2.</span>
                    Datos Personales que Recabamos
                </h2>
                <p>
                    Para llevar a cabo las finalidades descritas en el presente aviso, recabamos las siguientes categorías de datos personales:
                </p>
                <ul class="list-disc list-inside space-y-1.5 text-xs sm:text-sm pl-2">
                    <li><strong>Datos de Identificación y Contacto:</strong> Nombre y apellidos completos, número de teléfono móvil o WhatsApp, teléfono fijo y dirección de correo electrónico.</li>
                    <li><strong>Datos de Entrega y Logística:</strong> Dirección de destino completa (calle, número exterior e interior, colonia, municipio o alcaldía, estado, código postal de 5 dígitos y referencias visuales del predio).</li>
                    <li><strong>Datos de Facturación Fiscal (opcionales):</strong> Registro Federal de Contribuyentes (RFC), razón social o nombre legal, régimen fiscal, código postal del domicilio fiscal y Constancia de Situación Fiscal (CSF).</li>
                    <li><strong>Información Técnica de Vehículos:</strong> Marca, modelo, submarca, año de fabricación, cilindrada, número de serie vehicular (VIN) y fotografías de piezas muestra facilitadas por el comprador para corroborar compatibilidad técnica.</li>
                </ul>
            </section>

            <!-- 3. Tratamiento de Datos de Pago y Mercado Pago -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">3.</span>
                    Seguridad y Tratamiento de Información de Pago
                </h2>
                <p>
                    En <strong>{{ $business['name'] }}</strong> no solicitamos, no recopilamos ni tenemos acceso a números completos de tarjetas de crédito o débito, fechas de expiración ni códigos de validación de seguridad (CVV/CVC).
                </p>
                <p>
                    Las operaciones de cobro en línea se canalizan exclusivamente a través de los servidores seguros de <strong>Mercado Pago México S. de R.L. de C.V.</strong> mediante protocolos de encriptación TLS y cumplimiento estricto del estándar PCI-DSS Nivel 1. Mercado Pago únicamente notifica a nuestro sistema el resultado de la transacción (aprobado, pendiente o rechazado) y el identificador de operación necesario para generar el pedido y asegurar el inventario.
                </p>
            </section>

            <!-- 4. Finalidades del Tratamiento -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">4.</span>
                    Finalidades del Tratamiento de Datos
                </h2>
                <p>
                    Los datos personales recabados serán utilizados para las siguientes finalidades necesarias:
                </p>
                <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm pl-2">
                    <li>Verificar la compatibilidad de las refacciones automotrices con el vehículo del cliente.</li>
                    <li>Generar y gestionar las órdenes de compra y notas de pedido oficiales (<span class="font-mono font-semibold">JC-YYYY-XXXXX</span>).</li>
                    <li>Coordinar el despacho de mercancía mediante empresas de paquetería o preparar pedidos para recolección en sucursal.</li>
                    <li>Emitir los certificados de garantía por escrito correspondientes al tipo de autoparte.</li>
                    <li>Gestionar la emisión y timbrado de comprobantes fiscales digitales por internet (CFDI 4.0) cuando el cliente lo solicite en tiempo y forma.</li>
                    <li>Atender aclaraciones técnicas, reclamaciones de garantía y devoluciones.</li>
                </ul>
            </section>

            <!-- 5. Transferencia de Datos -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">5.</span>
                    Transferencia de Datos a Terceros
                </h2>
                <p>
                    Sus datos personales de envío (nombre del destinatario, teléfono de contacto y dirección física) son transferidos exclusivamente a empresas prestadoras de servicios de mensajería y paquetería (como Paquetexpress u operadores análogos) con el único y exclusivo propósito de concretar la entrega a domicilio del pedido adquirido. Dichas empresas se encuentran obligadas a mantener la confidencialidad de la información. No vendemos, cedemos ni transferimos sus datos a terceros con fines de comercialización publicitaria.
                </p>
            </section>

            <!-- 6. Derechos ARCO -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">6.</span>
                    Ejercicio de Derechos ARCO y Revocación del Consentimiento
                </h2>
                <p>
                    Usted tiene derecho en cualquier momento a ejercer sus derechos de <strong>Acceso, Rectificación, Cancelación u Oposición (ARCO)</strong>, así como a revocar el consentimiento que haya otorgado para el tratamiento de sus datos personales.
                </p>
                <p>
                    Para dar inicio a su solicitud, deberá enviar un correo electrónico con el asunto <em class="font-medium">"Solicitud de Derechos ARCO - JC Autopartes"</em> a la cuenta oficial <a href="mailto:{{ $business['email'] }}" class="text-primary font-semibold hover:underline">{{ $business['email'] }}</a>, o presentarse en nuestras oficinas en {{ $business['address'] }}, acompañando:
                </p>
                <ol class="list-decimal list-inside space-y-1 text-xs sm:text-sm pl-2">
                    <li>Copia simple de su identificación oficial vigente (INE o Pasaporte).</li>
                    <li>Descripción clara y precisa de los datos sobre los que desea ejercer alguno de los derechos ARCO.</li>
                    <li>En su caso, folio de pedido o fecha de compra para facilitar la localización del registro.</li>
                </ol>
                <p class="text-xs text-gray-500 mt-2">
                    Daremos respuesta a su solicitud en un plazo máximo de 20 (veinte) días hábiles a partir de la recepción formal de su documentación.
                </p>
            </section>

            <!-- 7. Modificaciones al Aviso -->
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-dark flex items-center gap-2">
                    <span class="text-primary font-mono text-sm">7.</span>
                    Actualizaciones al Presente Aviso
                </h2>
                <p>
                    El presente aviso de privacidad puede sufrir modificaciones derivadas de requerimientos legales, mejoras en nuestros procedimientos operativos de paquetería o actualización de políticas comerciales. Cualquier cambio sustancial será publicado directamente en este portal web oficial.
                </p>
            </section>

        </div>

    </div>
@endsection
