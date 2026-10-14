<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use Inertia\Inertia;
use Inertia\Response;

class PlaceholderModuleController extends Controller
{
    public function __construct(
        private readonly MercadoLibreOAuthService $meliOAuth
    ) {}

    public function orders(): Response
    {
        return Inertia::render('Admin/Orders/Index');
    }

    public function customers(): Response
    {
        return Inertia::render('Admin/Customers/Index');
    }

    public function settings(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'business' => config('business'),
            'mercadolibre' => $this->meliOAuth->getConnectionStatus(),
        ]);
    }
}
