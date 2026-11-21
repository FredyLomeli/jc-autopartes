<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MercadoLibreAuthController extends Controller
{
    public function __construct(
        private readonly MercadoLibreOAuthService $oauthService
    ) {}

    /**
     * Inicia el flujo OAuth 2.0 redirigiendo al portal de autorización de Mercado Libre.
     */
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->oauthService->hasCredentials()) {
            return redirect()
                ->route('admin.settings.index')
                ->with('error', 'La integración con Mercado Libre aún no está habilitada en el servidor. Contacta al administrador técnico.');
        }

        $state = Str::random(40);
        $request->session()->put('meli_oauth_state', $state);

        $authUrl = $this->oauthService->getAuthorizationUrl($state);

        return redirect()->away($authUrl);
    }

    /**
     * Procesa la respuesta de autorización (Callback) desde Mercado Libre.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            $errorDescription = $request->get('error_description') ?? $request->get('error');

            return redirect()
                ->route('admin.settings.index')
                ->with('error', "Mercado Libre rechazó la autorización: {$errorDescription}");
        }

        $expectedState = $request->session()->pull('meli_oauth_state');
        $receivedState = $request->get('state');

        if ($expectedState && $expectedState !== $receivedState) {
            return redirect()
                ->route('admin.settings.index')
                ->with('error', 'Error de seguridad en la respuesta de Mercado Libre (el parámetro State no coincide). Intenta nuevamente.');
        }

        $code = (string) $request->get('code');
        if (empty($code)) {
            return redirect()
                ->route('admin.settings.index')
                ->with('error', 'No se recibió el código de autorización desde Mercado Libre.');
        }

        try {
            $token = $this->oauthService->handleCallback($code);

            return redirect()
                ->route('admin.settings.index')
                ->with('success', "¡Cuenta de Mercado Libre (ID: {$token->user_id}) conectada exitosamente! Ya se puede iniciar la sincronización del catálogo.");
        } catch (Exception $e) {
            Log::error('Error en callback OAuth de Mercado Libre', ['error' => $e->getMessage()]);

            return redirect()
                ->route('admin.settings.index')
                ->with('error', 'No se pudo completar la autorización con Mercado Libre. Intenta nuevamente.');
        }
    }

    /**
     * Desvincula la cuenta de Mercado Libre eliminando los tokens almacenados.
     */
    public function disconnect(): RedirectResponse
    {
        $this->oauthService->disconnect();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Se ha desvinculado la cuenta de Mercado Libre.');
    }
}
