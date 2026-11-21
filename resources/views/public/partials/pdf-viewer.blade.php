@once
    @push('head')
        @vite(['resources/js/pdf-viewer.js'])
    @endpush
@endonce

<div class="bg-white rounded-card border border-border shadow-sm overflow-hidden">
    <div class="px-5 sm:px-6 py-4 border-b border-border bg-surface/60 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-sm sm:text-base font-extrabold text-dark">
                    {{ $viewerTitle }}
                </h2>
                <span data-pdf-count class="hidden px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 text-[11px] font-bold"></span>
            </div>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $viewerSubtitle }}
            </p>
        </div>
        <a
            href="{{ $pdfUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            class="text-xs font-bold text-primary hover:underline shrink-0"
        >
            Ver en pantalla completa &rarr;
        </a>
    </div>

    <div
        data-pdf-viewer
        data-pdf-url="{{ $pdfUrl }}"
        class="bg-gray-100 p-3 sm:p-5 space-y-4"
    >
        <!-- Estado de carga -->
        <div data-pdf-loading class="py-12 text-center space-y-2">
            <div class="inline-block w-7 h-7 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
            <p class="text-xs font-semibold text-gray-600">Cargando documento oficial...</p>
        </div>

        <!-- Respaldo si el navegador bloquea el canvas/fetch -->
        <div data-pdf-error class="hidden bg-white rounded-base border border-border p-6 text-center space-y-3">
            <p class="text-sm font-bold text-dark">No se pudo previsualizar el documento directamente en este navegador.</p>
            <div class="flex flex-wrap justify-center gap-2.5">
                <a
                    href="{{ $pdfUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="px-4 py-2 rounded-base bg-primary text-white text-xs font-bold"
                >
                    Abrir PDF
                </a>
                <a
                    href="{{ $pdfUrl }}"
                    download="{{ $downloadFilename }}"
                    class="px-4 py-2 rounded-base border border-border text-dark text-xs font-bold"
                >
                    Descargar PDF
                </a>
            </div>
        </div>

        <!-- Páginas renderizadas en canvas (Mobile + Desktop) -->
        <div data-pdf-pages class="space-y-4 max-w-4xl mx-auto"></div>
    </div>
</div>
