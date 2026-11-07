<?php

namespace App\Providers;

use App\Services\CatalogService;
use App\Services\CompanySettingService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\MarketplaceClient::class,
            \App\Services\MercadoLibre\MercadoLibreClient::class
        );

        $this->app->bind(
            \App\Contracts\PaymentGateway::class,
            \App\Services\Payments\MercadoPagoPaymentGateway::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Compartir los ajustes de la empresa y opciones de filtro de búsqueda con todas las vistas Blade públicas
        View::composer(['layouts.public', 'public.*'], function ($view) {
            $settingService = app(CompanySettingService::class);
            $catalogService = app(CatalogService::class);
            $filterOptions = $catalogService->getFilterOptions();

            $view->with([
                'companySettings' => $settingService->getAll(),
                'headerSearchBrands' => $filterOptions['brands'] ?? [],
                'headerSearchYears' => $filterOptions['years'] ?? [],
            ]);
        });
    }
}
