<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use App\Services\PriceService;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly PriceService $priceService,
        private readonly StockService $stockService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'q',
            'category',
            'brand',
            'model',
            'year',
            'condition',
            'min_price',
            'max_price',
            'sort',
        ]);

        $filterOptions = $this->catalogService->getFilterOptions();
        $products = $this->catalogService->filterProducts($filters, 12);

        return view('public.catalog.index', [
            'products' => $products,
            'filters' => $filters,
            'brands' => $filterOptions['brands'],
            'models' => $filterOptions['models'],
            'modelsByBrand' => $filterOptions['modelsByBrand'],
            'years' => $filterOptions['years'],
            'categories' => $filterOptions['categories'],
            'conditions' => $filterOptions['conditions'],
            'priceService' => $this->priceService,
            'stockService' => $this->stockService,
            // Regla SEO: toda página filtrada apunta su canonical al catálogo limpio sin query params
            'canonicalUrl' => route('catalog.index'),
        ]);
    }

    public function show(string $slug): View
    {
        $product = $this->catalogService->getPublishedProductBySlug($slug);
        $relatedProducts = $this->catalogService->getRelatedProducts($product, 4);
        $jsonLd = $this->catalogService->buildProductJsonLd($product);

        $unitPrice = $this->priceService->getUnitPrice($product);
        $formattedPrice = $this->priceService->format($unitPrice);
        $availableStock = $this->stockService->getAvailableStock($product);
        $hasStock = $this->stockService->hasStock($product, 1);

        $whatsappNumber = config('business.whatsapp_number', '523331665290');
        $whatsappMessage = "Hola JC Autopartes, me interesa la pieza: {$product->title} (SKU: {$product->sku}) para {$product->vehicle_brand} {$product->vehicle_model} {$product->year_start}-{$product->year_end}. ¿Sigue disponible?";
        $whatsappUrl = "https://wa.me/{$whatsappNumber}?text=" . urlencode($whatsappMessage);

        return view('public.catalog.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'unitPrice' => $unitPrice,
            'formattedPrice' => $formattedPrice,
            'availableStock' => $availableStock,
            'hasStock' => $hasStock,
            'whatsappUrl' => $whatsappUrl,
            'jsonLd' => $jsonLd,
            'priceService' => $this->priceService,
            'stockService' => $this->stockService,
            'canonicalUrl' => route('catalog.show', $product->slug),
        ]);
    }
}
