@php
    /** @var \App\Models\Product $product */
    $priceService = $priceService ?? app(\App\Services\PriceService::class);
    $stockService = $stockService ?? app(\App\Services\StockService::class);

    $unitPrice = $priceService->getUnitPrice($product);
    $formattedPrice = $priceService->format($unitPrice);
    $availableStock = $stockService->getAvailableStock($product);
    $hasStock = $stockService->hasStock($product, 1);
    $warrantyDays = $product->warrantyDays();
@endphp

<article class="group flex flex-col h-full justify-between bg-white rounded-lg border border-border hover:border-primary transition-colors shadow-sm overflow-hidden">
    <div class="flex flex-col flex-1">
        <!-- Imagen y Badges Superiores -->
        <a href="{{ route('catalog.show', $product->slug) }}" class="block relative w-full h-48 bg-surface overflow-hidden border-b border-border rounded-t-lg">
            <img
                src="{{ $product->primary_photo_url }}"
                alt="{{ $product->title }} ({{ $product->sku }})"
                class="object-cover w-full h-48 rounded-t-lg group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
                decoding="async"
            >
            <div class="absolute top-3 left-3 z-10 flex gap-1.5 pointer-events-none">
                <span class="bg-[#111111]/90 text-white px-2 py-0.5 rounded text-xs font-semibold shadow-sm">
                    Condición {{ $product->condition }}
                </span>
                <span class="bg-[#E31221] text-white font-semibold px-2 py-0.5 rounded text-xs shadow-sm">
                    Garantía {{ $warrantyDays }} días
                </span>
            </div>
        </a>

        <!-- Información Técnica y Compatibilidad -->
        <div class="p-4 flex flex-col flex-1 justify-between">
            <div>
                <div class="flex items-center justify-between gap-2 text-xs text-gray-500 mb-1.5">
                    <span class="font-semibold text-primary uppercase tracking-wider truncate">{{ $product->category }}</span>
                    <span class="font-mono text-[11px] bg-surface px-2 py-0.5 rounded border border-border text-gray-700 whitespace-nowrap shrink-0">
                        {{ $product->sku }}
                    </span>
                </div>

                <h3 class="text-sm sm:text-base font-extrabold text-dark group-hover:text-primary transition-colors line-clamp-2 min-h-[2.5rem] leading-snug">
                    <a href="{{ route('catalog.show', $product->slug) }}">
                        {{ $product->title }}
                    </a>
                </h3>

                @if($product->part_number || $product->sideAndPositionLabel())
                    <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px]">
                        @if($product->part_number)
                            <span class="bg-surface px-1.5 py-0.5 rounded border border-border text-gray-600 font-mono" title="Número de parte del fabricante">
                                P/N: {{ $product->part_number }}
                            </span>
                        @endif
                        @if($product->sideAndPositionLabel())
                            <span class="bg-red-50 text-primary border border-red-100 px-1.5 py-0.5 rounded font-medium">
                                {{ $product->sideAndPositionLabel() }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            <p class="mt-3 text-xs font-semibold text-gray-700 bg-surface rounded px-2.5 py-1.5 border border-border truncate">
                Compatible: <span class="text-dark">{{ $product->vehicle_brand }} {{ $product->vehicle_model }} ({{ $product->year_start }}–{{ $product->year_end }})</span>
            </p>
        </div>
    </div>

    <!-- Precio, Existencia y Acción -->
    <div class="px-4 pb-4 pt-2.5 border-t border-border/60 flex flex-col gap-3">
        <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="block text-[10px] text-gray-500 uppercase font-semibold leading-none mb-1">Precio de contado</span>
                <span class="block text-base sm:text-lg font-extrabold text-dark whitespace-nowrap leading-none">{{ $formattedPrice }}</span>
            </div>
            <div class="text-right shrink-0">
                @if($hasStock)
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-green-700 whitespace-nowrap">
                        <span class="w-2 h-2 rounded-full bg-green-600 shrink-0"></span>
                        {{ $availableStock }} {{ $availableStock === 1 ? 'disp.' : 'disp.' }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-red-700 whitespace-nowrap">
                        <span class="w-2 h-2 rounded-full bg-red-600 shrink-0"></span>
                        Agotado
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('catalog.show', $product->slug) }}"
               class="inline-flex items-center justify-center px-3 py-2 rounded-base border border-border hover:border-dark text-xs font-bold text-dark transition-colors text-center whitespace-nowrap">
                Ver Ficha
            </a>

            @if(\Illuminate\Support\Facades\Route::has('cart.add') && $hasStock)
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-base bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-colors whitespace-nowrap">
                        + Al Carrito
                    </button>
                </form>
            @else
                <a href="{{ route('catalog.show', $product->slug) }}"
                   class="inline-flex items-center justify-center px-3 py-2 rounded-base bg-primary hover:bg-primary-hover text-white text-xs font-bold transition-colors text-center whitespace-nowrap">
                    Cotizar / Comprar
                </a>
            @endif
        </div>
    </div>
</article>
