<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Rutas Públicas (Blade + Tailwind + Alpine.js — Renderizado en Servidor por SEO)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/refacciones', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/refacciones/{slug}', [CatalogController::class, 'show'])->name('catalog.show');

Route::get('/contacto', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/garantia', [PageController::class, 'warranty'])->name('pages.warranty');
Route::get('/aviso-de-privacidad', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terminos-y-condiciones', [PageController::class, 'terms'])->name('pages.terms');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
