<?php

namespace App\Providers;

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
    }
}
