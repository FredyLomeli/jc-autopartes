<?php

namespace App\Providers;

use App\Services\CatalogService;
use App\Services\CompanySettingService;
use Illuminate\Http\Middleware\TrustProxies;
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
        $this->app->scoped(CatalogService::class);

        $this->app->bind(
            \App\Contracts\MarketplaceClient::class,
            \App\Services\MercadoLibre\MercadoLibreClient::class
        );

        $this->app->bind(
            \App\Contracts\PaymentGateway::class,
            \App\Services\Payments\MercadoPagoPaymentGateway::class
        );

        $this->app->bind(
            \App\Contracts\ShippingQuoteProvider::class,
            \App\Services\Shipping\SimulatedPaquetexpressQuoteProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureTrustProxies();

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

    /**
     * Configura proxies de confianza a partir de config('app.trust_proxies').
     */
    protected function configureTrustProxies(): void
    {
        $value = config('app.trust_proxies');

        if ($value === null || $value === false || $value === '' || $value === 'false' || $value === '0') {
            return;
        }

        if ($value === true || $value === 'true' || $value === 1 || $value === '1' || $value === '*') {
            TrustProxies::at('*');

            return;
        }

        if (is_string($value)) {
            $proxies = array_values(array_filter(array_map('trim', explode(',', $value))));

            if ($proxies !== []) {
                TrustProxies::at($proxies);
            }

            return;
        }

        if (is_array($value) && $value !== []) {
            TrustProxies::at($value);
        }
    }
}
