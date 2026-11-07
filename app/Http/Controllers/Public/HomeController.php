<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use App\Services\CatalogService;
use App\Services\PriceService;
use App\Services\StockService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly PriceService $priceService,
        private readonly StockService $stockService,
    ) {}

    public function index(): View
    {
        $banners = HomeBanner::query()
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $filterOptions = $this->catalogService->getFilterOptions();
        $featuredProducts = $this->catalogService->getFeaturedProducts(8);

        return view('public.home', [
            'banners' => $banners,
            'brands' => $filterOptions['brands'],
            'models' => $filterOptions['models'],
            'modelsByBrand' => $filterOptions['modelsByBrand'],
            'years' => $filterOptions['years'],
            'categories' => $filterOptions['categories'],
            'featuredProducts' => $featuredProducts,
            'priceService' => $this->priceService,
            'stockService' => $this->stockService,
        ]);
    }
}
