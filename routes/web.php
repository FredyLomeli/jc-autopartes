<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\MercadoLibreAuthController;
use App\Http\Controllers\Admin\MercadoLibreSyncController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PlaceholderModuleController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\CartController;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Support\Facades\Route;

// Rutas Públicas (Blade + Tailwind + Alpine.js — Renderizado en Servidor por SEO)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/refacciones', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/refacciones/lote', [CatalogController::class, 'batch'])->name('catalog.batch');
Route::get('/refacciones/{slug}', [CatalogController::class, 'show'])->name('catalog.show');

// Carrito en sesión del servidor y Checkout visual
Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito/agregar', [CartController::class, 'add'])->name('cart.add');
Route::delete('/carrito', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/carrito/cupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/carrito/cupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
Route::patch('/carrito/{product}', [CartController::class, 'update'])->whereNumber('product')->name('cart.update');
Route::delete('/carrito/{product}', [CartController::class, 'remove'])->whereNumber('product')->name('cart.remove');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/cotizar-envio', [CheckoutController::class, 'quoteShipping'])->name('checkout.quote-shipping');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/exito', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/fallo', [CheckoutController::class, 'failure'])->name('checkout.failure');
Route::get('/checkout/pendiente', [CheckoutController::class, 'pending'])->name('checkout.pending');

// Páginas informativas y legales
Route::get('/contacto', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/preguntas-frecuentes', [PageController::class, 'faqs'])->name('pages.faqs');
Route::get('/garantia', [PageController::class, 'warranty'])->name('pages.warranty');
Route::get('/garantia/documento.pdf', [PageController::class, 'warrantyPdf'])->name('pages.warranty.pdf');
Route::get('/aviso-de-privacidad', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/aviso-de-privacidad/documento.pdf', [PageController::class, 'privacyPdf'])->name('pages.privacy.pdf');
Route::get('/terminos-y-condiciones', [PageController::class, 'terms'])->name('pages.terms');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Redirección de compatibilidad /dashboard -> /admin
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
    ->middleware(['auth'])
    ->name('dashboard');

// Administración (/admin — Vue 3 + Inertia + Vite, protegido por sesión autenticada)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // CRUD de Artículos (/admin/articulos)
        Route::get('/articulos', [ProductController::class, 'index'])->name('products.index');
        Route::get('/articulos/crear', [ProductController::class, 'create'])->name('products.create');
        Route::post('/articulos', [ProductController::class, 'store'])->name('products.store');
        Route::post('/articulos/sincronizar-meli', [ProductController::class, 'syncFromMercadoLibre'])->name('products.sync-meli');
        Route::get('/articulos/{product}/editar', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/articulos/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/articulos/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        // Banners de Inicio (/admin/banners)
        Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
        Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
        Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
        Route::post('/banners/{banner}/reordenar', [BannerController::class, 'reorder'])->name('banners.reorder');
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');

        // Preguntas Frecuentes (/admin/faqs)
        Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
        Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store');
        Route::put('/faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
        Route::post('/faqs/{faq}/reordenar', [FaqController::class, 'reorder'])->name('faqs.reorder');
        Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');

        // Campañas de Promoción y Cupones (/admin/promociones)
        Route::get('/promociones', [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promociones', [PromotionController::class, 'storePromotion'])->name('promotions.store');
        Route::put('/promociones/{promotion}', [PromotionController::class, 'updatePromotion'])->name('promotions.update');
        Route::delete('/promociones/{promotion}', [PromotionController::class, 'destroyPromotion'])->name('promotions.destroy');
        Route::post('/cupones', [PromotionController::class, 'storeCoupon'])->name('coupons.store');
        Route::delete('/cupones/{coupon}', [PromotionController::class, 'destroyCoupon'])->name('coupons.destroy');

        Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/pedidos/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::get('/clientes', [PlaceholderModuleController::class, 'customers'])->name('customers.index');
        Route::get('/ajustes', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/ajustes', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/ajustes/garantia-pdf', [SettingController::class, 'uploadWarrantyPdf'])->name('settings.warranty-pdf.upload');
        Route::delete('/ajustes/garantia-pdf', [SettingController::class, 'destroyWarrantyPdf'])->name('settings.warranty-pdf.destroy');
        Route::post('/ajustes/privacidad-pdf', [SettingController::class, 'uploadPrivacyPdf'])->name('settings.privacy-pdf.upload');
        Route::delete('/ajustes/privacidad-pdf', [SettingController::class, 'destroyPrivacyPdf'])->name('settings.privacy-pdf.destroy');
        Route::get('/configuracion', [SettingController::class, 'index'])->name('settings.legacy');

        // Integración con Mercado Libre (/admin/integraciones/mercadolibre)
        Route::prefix('integraciones/mercadolibre')->name('mercadolibre.')->group(function () {
            Route::get('/conectar', [MercadoLibreAuthController::class, 'redirect'])->name('connect');
            Route::get('/callback', [MercadoLibreAuthController::class, 'callback'])->name('callback');
            Route::delete('/desconectar', [MercadoLibreAuthController::class, 'disconnect'])->name('disconnect');
            Route::post('/sincronizar-lote', [MercadoLibreSyncController::class, 'processChunk'])->name('sync-chunk');
            Route::get('/reporte', [MercadoLibreSyncController::class, 'report'])->name('sync-report');
            Route::post('/reiniciar-reporte', [MercadoLibreSyncController::class, 'reset'])->name('sync-reset');
        });
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
