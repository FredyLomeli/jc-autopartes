<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MercadoLibreAuthController;
use App\Http\Controllers\Admin\MercadoLibreSyncController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PlaceholderModuleController;
use App\Http\Controllers\Admin\ProductController;
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
Route::get('/refacciones/{slug}', [CatalogController::class, 'show'])->name('catalog.show');

// Carrito en sesión del servidor y Checkout visual
Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito/agregar', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/carrito', [CartController::class, 'clear'])->name('cart.clear');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/exito', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/fallo', [CheckoutController::class, 'failure'])->name('checkout.failure');
Route::get('/checkout/pendiente', [CheckoutController::class, 'pending'])->name('checkout.pending');

// Páginas informativas y legales
Route::get('/contacto', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/garantia', [PageController::class, 'warranty'])->name('pages.warranty');
Route::get('/aviso-de-privacidad', [PageController::class, 'privacy'])->name('pages.privacy');
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

        Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/pedidos/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::get('/clientes', [PlaceholderModuleController::class, 'customers'])->name('customers.index');
        Route::get('/configuracion', [PlaceholderModuleController::class, 'settings'])->name('settings.index');

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
