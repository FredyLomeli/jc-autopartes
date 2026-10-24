<?php

namespace App\Jobs;

use App\Services\MercadoLibre\MercadoLibreCatalogSyncService;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMercadoLibreIncrementalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos en caso de falla transitoria de red.
     */
    public int $tries = 3;

    /**
     * Tiempo límite de ejecución por lote (60 segundos máximo).
     */
    public int $timeout = 60;

    public function __construct(
        public int $limit = 50,
        public ?string $scrollId = null,
        public int $offset = 0,
        public string $status = 'active',
        public bool $autoContinue = false,
        public bool $fixture = false,
    ) {}

    public function handle(
        MercadoLibreCatalogSyncService $syncService,
        MercadoLibreOAuthService $oauthService,
    ): void {
        // En modo real, validar que exista un token activo de Mercado Libre
        if (! $this->fixture && ! $oauthService->getActiveToken()) {
            Log::warning('[SyncMercadoLibreJob] Tarea en segundo plano abortada: No hay token activo de Mercado Libre.');

            return;
        }

        try {
            $result = $syncService->syncBatch([
                'limit' => $this->limit,
                'scroll_id' => $this->scrollId,
                'offset' => $this->offset,
                'status' => $this->status,
                'fixture' => $this->fixture,
                'mode' => 'segundo_plano_queue',
            ]);

            Log::info("[SyncMercadoLibreJob] Lote procesado con éxito: {$result['processed_count']} piezas ({$result['created_count']} nuevas, {$result['updated_count']} actualizadas).");

            // Si autoContinue está activo y hay más piezas por procesar, encadenar el siguiente lote
            if ($this->autoContinue && $result['has_more'] && ! empty($result['next_scroll_id'])) {
                self::dispatch(
                    limit: $this->limit,
                    scrollId: $result['next_scroll_id'],
                    offset: $result['next_offset'],
                    status: $this->status,
                    autoContinue: true,
                    fixture: $this->fixture,
                )->delay(now()->addSeconds(2));
            }
        } catch (Throwable $e) {
            Log::error("[SyncMercadoLibreJob] Error durante la sincronización en segundo plano: {$e->getMessage()}");
            throw $e;
        }
    }
}
