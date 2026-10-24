<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MercadoLibre\MercadoLibreCatalogSyncService;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MercadoLibreSyncController extends Controller
{
    public function __construct(
        private readonly MercadoLibreCatalogSyncService $syncService,
        private readonly MercadoLibreOAuthService $oauthService,
    ) {}

    /**
     * Procesa un lote individual de publicaciones de Mercado Libre.
     * Diseñado para llamadas AJAX continuas desde el navegador (evitando timeouts en DirectAdmin).
     */
    public function processChunk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scroll_id' => 'nullable|string',
            'offset' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string|in:active,paused',
            'fixture' => 'nullable|boolean',
        ]);

        $limit = (int) ($validated['limit'] ?? 50);
        $scrollId = $validated['scroll_id'] ?? null;
        $offset = (int) ($validated['offset'] ?? 0);
        $status = (string) ($validated['status'] ?? 'active');
        $fixture = (bool) ($validated['fixture'] ?? false);

        // Si no se solicita fixture, verificar que la cuenta esté conectada
        if (! $fixture && ! $this->oauthService->getActiveToken()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una cuenta de Mercado Libre vinculada. Conecta tu cuenta primero en Configuración.',
                'error_type' => 'UNAUTHENTICATED_MELI',
                'has_more' => false,
            ], 401);
        }

        try {
            $result = $this->syncService->syncBatch([
                'limit' => $limit,
                'scroll_id' => $scrollId,
                'offset' => $offset,
                'status' => $status,
                'fixture' => $fixture,
            ]);

            return response()->json([
                'success' => true,
                'processed_count' => $result['processed_count'],
                'created_count' => $result['created_count'],
                'updated_count' => $result['updated_count'],
                'failed_count' => $result['failed_count'],
                'total_remote' => $result['total_remote'],
                'next_scroll_id' => $result['next_scroll_id'],
                'next_offset' => $result['next_offset'],
                'has_more' => $result['has_more'],
                'items' => $result['items'],
                'errors' => $result['errors'],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'SYNC_BATCH_EXCEPTION',
                'has_more' => false,
            ], 500);
        }
    }

    /**
     * Retorna el contenido del archivo JSON de diagnóstico o lo entrega como descarga.
     */
    public function report(Request $request): JsonResponse|BinaryFileResponse
    {
        $reportPath = storage_path('app/mercadolibre_sync_report.json');

        if ($request->boolean('download') && file_exists($reportPath)) {
            return response()->download(
                $reportPath,
                'mercadolibre_sync_report_' . date('Y-m-d_His') . '.json',
                ['Content-Type' => 'application/json']
            );
        }

        $reportData = $this->syncService->getDiagnosticReport();

        return response()->json($reportData);
    }

    /**
     * Reinicia el reporte de auditoría para iniciar una nueva sincronización desde cero.
     */
    public function reset(): JsonResponse
    {
        $this->syncService->generateDiagnosticReport([
            'processed_count' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'failed_count' => 0,
            'total_remote' => 0,
            'items' => [],
            'errors' => [],
        ], ['scroll_id' => null, 'offset' => 0, 'mode' => 'reinicio_manual']);

        return response()->json([
            'success' => true,
            'message' => 'Reporte de diagnóstico reiniciado correctamente.',
        ]);
    }
}
