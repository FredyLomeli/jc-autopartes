<?php

namespace Tests\Unit;

use App\Contracts\MarketplaceClient;
use App\Services\MercadoLibre\MercadoLibreClient;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MercadoLibreServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('mercadolibre.app_id', '123456789');
        Config::set('mercadolibre.client_secret', 'secret_meli_key');
        Config::set('mercadolibre.redirect_uri', 'http://localhost:8000/admin/integraciones/mercadolibre/callback');
        Config::set('mercadolibre.auth_base_url', 'https://auth.mercadolibre.com.mx');
        Config::set('mercadolibre.api_base_url', 'https://api.mercadolibre.com');
    }

    public function test_has_credentials_detects_configuration(): void
    {
        $service = new MercadoLibreOAuthService();
        $this->assertTrue($service->hasCredentials());

        Config::set('mercadolibre.app_id', null);
        $serviceWithoutAppId = new MercadoLibreOAuthService();
        $this->assertFalse($serviceWithoutAppId->hasCredentials());
    }

    public function test_get_authorization_url_generates_valid_url(): void
    {
        $service = new MercadoLibreOAuthService();
        $url = $service->getAuthorizationUrl('custom_state_token');

        $this->assertStringStartsWith('https://auth.mercadolibre.com.mx/authorization?', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('client_id=123456789', $url);
        $this->assertStringContainsString('redirect_uri=' . urlencode('http://localhost:8000/admin/integraciones/mercadolibre/callback'), $url);
        $this->assertStringContainsString('state=custom_state_token', $url);
    }

    public function test_get_connection_status_masks_app_id(): void
    {
        $service = $this->getMockBuilder(MercadoLibreOAuthService::class)
            ->onlyMethods(['getActiveToken'])
            ->getMock();
        $service->method('getActiveToken')->willReturn(null);

        $status = $service->getConnectionStatus();

        $this->assertTrue($status['has_credentials']);
        $this->assertEquals('123***789', $status['app_id']);
        $this->assertFalse($status['connected']);
        $this->assertNotNull($status['auth_url']);
    }

    public function test_client_fetch_listing_with_token(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/items/MLM123456789' => Http::response([
                'id' => 'MLM123456789',
                'title' => 'Alternador Original Nissan Versa 2018',
                'price' => 2450.00,
                'available_quantity' => 2,
                'status' => 'active',
            ], 200),
        ]);

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);
        $oauthMock->method('getValidAccessToken')->willReturn('fake_valid_access_token');

        $client = new MercadoLibreClient($oauthMock);
        $listing = $client->fetchListing('MLM123456789');

        $this->assertEquals('MLM123456789', $listing['id']);
        $this->assertEquals('Alternador Original Nissan Versa 2018', $listing['title']);
        $this->assertEquals(2450.00, $listing['price']);
        $this->assertEquals(2, $listing['available_quantity']);
    }

    public function test_client_fetch_listings_multi_get(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/items?ids=MLM101%2CMLM102' => Http::response([
                [
                    'code' => 200,
                    'body' => [
                        'id' => 'MLM101',
                        'title' => 'Cofre Versa 2018',
                        'price' => 3200,
                    ],
                ],
                [
                    'code' => 200,
                    'body' => [
                        'id' => 'MLM102',
                        'title' => 'Faro Izquierdo Versa 2018',
                        'price' => 1400,
                    ],
                ],
            ], 200),
        ]);

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);
        $oauthMock->method('getValidAccessToken')->willReturn('fake_valid_access_token');

        $client = new MercadoLibreClient($oauthMock);
        $listings = $client->fetchListings(['MLM101', 'MLM102']);

        $this->assertCount(2, $listings);
        $this->assertEquals('MLM101', $listings[0]['id']);
        $this->assertEquals('MLM102', $listings[1]['id']);
    }

    public function test_client_search_listings_with_token(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/users/998877/items/search*' => Http::response([
                'seller_id' => '998877',
                'results' => ['MLM1001', 'MLM1002', 'MLM1003'],
                'paging' => ['total' => 3, 'offset' => 0, 'limit' => 50],
            ], 200),
        ]);

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);
        $oauthMock->method('getValidAccessToken')->willReturn('fake_valid_access_token');

        $client = new MercadoLibreClient($oauthMock);
        $results = $client->searchListings('998877');

        $this->assertCount(3, $results['results']);
        $this->assertEquals(['MLM1001', 'MLM1002', 'MLM1003'], $results['results']);
    }

    public function test_client_search_listings_with_scroll(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/users/998877/items/search*search_type=scan*' => Http::response([
                'results' => ['MLM1001', 'MLM1002'],
                'scroll_id' => 'next_scroll_token_123',
                'paging' => ['total' => 12000, 'limit' => 50],
            ], 200),
        ]);

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);
        $oauthMock->method('getValidAccessToken')->willReturn('fake_valid_access_token');

        $client = new MercadoLibreClient($oauthMock);
        $results = $client->searchListingsWithScroll('998877', null, 50);

        $this->assertCount(2, $results['results']);
        $this->assertEquals('next_scroll_token_123', $results['scroll_id']);
        $this->assertEquals(12000, $results['total']);
    }

    public function test_client_throws_exception_when_not_authenticated(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No hay una sesión activa de Mercado Libre');

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);
        $oauthMock->method('getValidAccessToken')->willReturn(null);

        $client = new MercadoLibreClient($oauthMock);
        $client->fetchListing('MLM123456789');
    }

    public function test_client_update_available_stock(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/items/MLM123456789' => Http::response(['status' => 'updated'], 200),
        ]);

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);
        $oauthMock->method('getValidAccessToken')->willReturn('fake_valid_access_token');

        $client = new MercadoLibreClient($oauthMock);
        $result = $client->updateAvailableStock('MLM123456789', 5);

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mercadolibre.com/items/MLM123456789'
                && $request->method() === 'PUT'
                && $request['available_quantity'] === 5;
        });
    }

    public function test_client_get_user_profile(): void
    {
        Http::fake([
            'https://api.mercadolibre.com/users/me' => Http::response([
                'id' => 123456,
                'nickname' => 'JC_AUTOPARTES',
                'site_id' => 'MLM',
            ], 200),
        ]);

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);
        $oauthMock->method('getValidAccessToken')->willReturn('fake_valid_access_token');

        $client = new MercadoLibreClient($oauthMock);
        $profile = $client->getUserProfile();

        $this->assertEquals(123456, $profile['id']);
        $this->assertEquals('JC_AUTOPARTES', $profile['nickname']);
    }

    public function test_marketplace_client_is_bound_in_container(): void
    {
        $resolved = $this->app->make(MarketplaceClient::class);
        $this->assertInstanceOf(MercadoLibreClient::class, $resolved);
    }
}
