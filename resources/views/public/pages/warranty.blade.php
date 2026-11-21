@extends('layouts.public', [
    'title' => 'Política de Garantía Oficial por Escrito',
    'metaDescription' => 'Consulta la política oficial de garantía escrita de JC Autopartes en Tepatitlán de Morelos, Jalisco.',
    'canonicalUrl' => route('pages.warranty'),
])

@php
    $business = config('business');
    $settings = $companySettings ?? [];
    $companyName = $settings['company_name'] ?? $business['name'];
    $companyEmail = $settings['contact_email'] ?? $business['email'];
    $companyPhone = $settings['contact_phone_office'] ?? $business['phone_display'];
    $companyAddress = isset($settings['contact_address'])
        ? trim("{$settings['contact_address']}, Col. " . ($settings['contact_colony'] ?? 'Los Adobes') . ', C.P. ' . ($settings['contact_zip'] ?? '47656') . ', ' . ($settings['contact_city'] ?? 'Tepatitlán de Morelos, Jalisco'))
        : $business['address'];
    $waDisplay = $settings['contact_whatsapp_1'] ?? $business['whatsapp_display'];
    $rawWa = preg_replace('/\D+/', '', (string) $waDisplay);
    $waPhone = strlen($rawWa) === 10 ? '52' . $rawWa : $rawWa;
    $hoursWeekdays = $settings['schedule_weekday'] ?? ($business['hours']['weekdays'] ?? 'Lunes a Viernes: 9:00 AM – 6:00 PM');
    $hoursSaturday = $settings['schedule_saturday'] ?? ($business['hours']['saturday'] ?? 'Sábados: 9:00 AM – 2:00 PM');
    $hasPdf = !empty($hasWarrantyPdf);
@endphp

@section('content')
    <!-- Encabezado de Página -->
    <div class="bg-white border-b border-border">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Respaldo Oficial {{ $companyName }}
                    </span>
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-dark mt-2 tracking-tight">
                        Política de Garantía Oficial por Escrito
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-2 max-w-2xl">
                        Documento oficial vigente que rige los términos, plazos y condiciones de garantía en todas las refacciones de {{ $companyName }}.
                    </p>
                </div>

                @if($hasPdf)
                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        <a
                            href="{{ route('pages.warranty.pdf') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-base border border-border bg-surface hover:border-dark text-xs font-bold text-dark transition-colors"
                        >
                            Abrir PDF
                        </a>
                        <a
                            href="{{ route('pages.warranty.pdf') }}"
                            download="Politica-de-Garantia-JC-Autopartes.pdf"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-base bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-colors shadow-xs"
                        >
                            Descargar PDF
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-6">
        @if($hasPdf)
            @include('public.partials.pdf-viewer', [
                'pdfUrl' => route('pages.warranty.pdf'),
                'viewerTitle' => 'Documento Oficial de Garantías (PDF)',
                'viewerSubtitle' => 'Consulta en línea o descarga nuestra póliza general de garantías.',
                'downloadFilename' => 'Politica-de-Garantia-JC-Autopartes.pdf',
            ])
        @else
            <div class="bg-white rounded-card border border-border p-8 sm:p-12 text-center shadow-sm space-y-4">
                <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h2 class="text-lg font-extrabold text-dark">
                    Documento de Política de Garantía en Actualización
                </h2>
                <p class="text-sm text-gray-600 max-w-lg mx-auto">
                    Próximamente estará disponible aquí el PDF oficial de garantías. Si necesitas consultar los términos aplicables a tu refacción o iniciar una revisión, contáctanos directamente por WhatsApp.
                </p>
                <div class="pt-2">
                    <a
                        href="https://wa.me/{{ $waPhone }}?text={{ urlencode('Hola JC Autopartes, solicito información sobre la política de garantía.') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-base bg-whatsapp hover:brightness-95 text-white text-xs sm:text-sm font-bold transition"
                    >
                        Consultar por WhatsApp ({{ $waDisplay }})
                    </a>
                </div>
            </div>
        @endif

        <!-- Contacto directo para atención a garantías -->
        <div class="bg-white rounded-card border border-border p-5 sm:p-6 text-xs sm:text-sm text-gray-600 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <p class="font-bold text-dark">Atención a Clientes y Garantías:</p>
                <p class="mt-0.5">{{ $companyName }} — {{ $companyAddress }}</p>
                <p class="mt-0.5 text-gray-500">{{ $hoursWeekdays }} | {{ $hoursSaturday }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="https://wa.me/{{ $waPhone }}" target="_blank" rel="noopener noreferrer" class="text-emerald-700 font-bold hover:underline">
                    WhatsApp: {{ $waDisplay }}
                </a>
                <span class="text-gray-300">|</span>
                <a href="tel:{{ preg_replace('/\D+/', '', $companyPhone) }}" class="text-primary font-bold hover:underline">
                    Tel: {{ $companyPhone }}
                </a>
                <span class="text-gray-300">|</span>
                <a href="mailto:{{ $companyEmail }}" class="text-primary font-bold hover:underline">
                    {{ $companyEmail }}
                </a>
            </div>
        </div>
    </div>
@endsection
