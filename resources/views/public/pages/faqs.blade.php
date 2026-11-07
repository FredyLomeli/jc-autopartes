@extends('layouts.public')

@section('content')
<div class="bg-gray-50 py-12 border-b border-border">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20 uppercase tracking-wider mb-3">
            Centro de Ayuda y Soporte
        </span>
        <h1 class="text-3xl sm:text-4xl font-black text-dark tracking-tight">
            Preguntas Frecuentes
        </h1>
        <p class="text-gray-600 mt-3 text-base max-w-2xl mx-auto">
            Resolvemos tus dudas sobre compatibilidad técnica, envíos seguros a toda la República, métodos de pago y nuestra garantía escrita.
        </p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="space-y-4" x-data="{ openFaq: null }">
        @forelse($faqs as $faq)
            <div class="bg-white rounded-base border border-gray-200 overflow-hidden shadow-sm transition hover:border-gray-300">
                <button
                    type="button"
                    @click="openFaq = openFaq === {{ $faq->id }} ? null : {{ $faq->id }}"
                    class="w-full text-left px-6 py-5 flex items-center justify-between gap-4 focus:outline-none"
                >
                    <span class="font-extrabold text-dark text-base sm:text-lg flex items-center gap-3">
                        <span class="text-primary font-black">Q.</span>
                        {{ $faq->question }}
                    </span>
                    <span class="flex-shrink-0 text-gray-400 p-1 rounded-full hover:bg-gray-100 transition">
                        <svg
                            :class="openFaq === {{ $faq->id }} ? 'rotate-180 text-primary' : ''"
                            class="w-5 h-5 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </button>

                <div
                    x-show="openFaq === {{ $faq->id }}"
                    x-collapse
                    class="px-6 pb-6 pt-1 text-sm text-gray-600 border-t border-gray-100 bg-gray-50/50 leading-relaxed"
                >
                    <div class="pt-2">
                        {{ $faq->answer }}
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-gray-500 bg-white rounded-base border border-dashed border-gray-300 p-8">
                Próximamente estaremos publicando nuevas preguntas frecuentes.
            </div>
        @endforelse
    </div>

    <!-- Banner de Soporte Directo -->
    <div class="mt-12 bg-dark rounded-base p-8 text-white text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-6 shadow-md">
        <div>
            <h3 class="text-xl font-black text-white">¿No encontraste lo que buscabas?</h3>
            <p class="text-gray-400 text-sm mt-1">Escríbenos directamente a nuestras líneas de WhatsApp y un técnico te asesorará en minutos.</p>
        </div>
        <a
            href="{{ $companySettings['contact_whatsapp_1_url'] ?? 'https://wa.me/3781455737' }}"
            target="_blank"
            rel="noopener noreferrer"
            class="px-6 py-3 bg-whatsapp hover:bg-emerald-600 text-white font-black text-sm rounded-base transition inline-flex items-center gap-2 shrink-0 shadow"
        >
            <span>Consultar por WhatsApp</span>
        </a>
    </div>
</div>
@endsection
