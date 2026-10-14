<?php

namespace App\Services\MercadoLibre;

use App\Models\MarketplaceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MercadoLibreOAuthService
{
    private readonly ?string $appId;
    private readonly ?string $clientSecret;
    private readonly string $redirectUri;
    private readonly string $authBaseUrl;
    private readonly string $apiBaseUrl;

    public function __construct()
    {
        $this->appId = config('mercadolibre.app_id');
        $this->clientSecret = config('mercadolibre.client_secret');
        $this->redirectUri = (string) config('mercadolibre.redirect_uri');
        $this->authBaseUrl = rtrim((string) config('mercadolibre.auth_base_url', 'https://auth.mercadolibre.com.mx'), '/');
        $this->apiBaseUrl = rtrim((string) config('mercadolibre.api_base_url', 'https://api.mercadolibre.com'), '/');
    }

    public function hasCredentials(): bool
    {
        return ! empty($this->appId) && ! empty($this->clientSecret);
    }

    public function getAuthorizationUrl(?string $state = null): string
    {
        if (! $this->hasCredentials()) {
            throw new RuntimeException('Las credenciales de Mercado Libre (MELI_APP_ID y MELI_CLIENT_SECRET) no están configuradas.');
        }

        $stateParam = $state ?? Str::random(40);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'state' => $stateParam,
        ]);

        return "{$this->authBaseUrl}/authorization?{$params}";
    }

    public function handleCallback(string $code): MarketplaceToken
    {
        if (! $this->hasCredentials()) {
            throw new RuntimeException('Credenciales de Mercado Libre faltantes en la configuración.');
        }

        $response = Http::asForm()->post("{$this->apiBaseUrl}/oauth/token", [
            'grant_type' => 'authorization_code',
            'client_id' => $this->appId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);

        if ($response->failed()) {
            $errorDetail = $response->json('message') ?? $response->body();
            throw new RuntimeException("Error al autenticar con Mercado Libre: {$errorDetail}");
        }

        $data = $response->json();
        $expiresIn = (int) ($data['expires_in'] ?? 21600);

        return MarketplaceToken::updateOrCreate(
            ['marketplace' => 'mercadolibre'],
            [
                'user_id' => (string) ($data['user_id'] ?? ''),
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'token_type' => $data['token_type'] ?? 'Bearer',
                'scope' => $data['scope'] ?? null,
                'expires_at' => now()->addSeconds($expiresIn),
            ]
        );
    }

    public function refreshToken(MarketplaceToken $token): MarketplaceToken
    {
        if (! $this->hasCredentials()) {
            throw new RuntimeException('Credenciales de Mercado Libre faltantes para refrescar token.');
        }

        $response = Http::asForm()->post("{$this->apiBaseUrl}/oauth/token", [
            'grant_type' => 'refresh_token',
            'client_id' => $this->appId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $token->refresh_token,
        ]);

        if ($response->failed()) {
            $errorDetail = $response->json('message') ?? $response->body();
            throw new RuntimeException("Error al refrescar el token de Mercado Libre: {$errorDetail}");
        }

        $data = $response->json();
        $expiresIn = (int) ($data['expires_in'] ?? 21600);

        $token->update([
            'user_id' => (string) ($data['user_id'] ?? $token->user_id),
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_at' => now()->addSeconds($expiresIn),
        ]);

        return $token->fresh();
    }

    public function getActiveToken(): ?MarketplaceToken
    {
        return MarketplaceToken::query()
            ->where('marketplace', 'mercadolibre')
            ->first();
    }

    public function getValidAccessToken(): ?string
    {
        $token = $this->getActiveToken();
        if (! $token) {
            return null;
        }

        if ($token->willExpireSoon(5)) {
            $token = $this->refreshToken($token);
        }

        return $token->access_token;
    }

    public function isConnected(): bool
    {
        $token = $this->getActiveToken();

        return $token !== null && ! empty($token->access_token);
    }

    public function disconnect(): void
    {
        MarketplaceToken::query()
            ->where('marketplace', 'mercadolibre')
            ->delete();
    }

    /**
     * @return array{
     *     has_credentials: bool,
     *     app_id: ?string,
     *     connected: bool,
     *     user_id: ?string,
     *     expires_at: ?string,
     *     expires_at_formatted: ?string,
     *     is_expired: bool,
     *     auth_url: ?string
     * }
     */
    public function getConnectionStatus(): array
    {
        $token = $this->getActiveToken();
        $hasCreds = $this->hasCredentials();

        $maskedAppId = null;
        if ($this->appId) {
            $len = strlen($this->appId);
            $maskedAppId = $len > 6 ? substr($this->appId, 0, 3) . '***' . substr($this->appId, -3) : '***';
        }

        return [
            'has_credentials' => $hasCreds,
            'app_id' => $maskedAppId,
            'connected' => $this->isConnected(),
            'user_id' => $token?->user_id,
            'expires_at' => $token?->expires_at?->toIso8601String(),
            'expires_at_formatted' => $token?->expires_at?->format('d/m/Y H:i'),
            'is_expired' => $token?->isExpired() ?? false,
            'auth_url' => $hasCreds ? $this->getAuthorizationUrl() : null,
        ];
    }
}
