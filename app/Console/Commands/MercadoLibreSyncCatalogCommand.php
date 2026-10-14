<?php

namespace App\Console\Commands;

use App\Services\MercadoLibre\MercadoLibreCatalogSyncService;
use App\Services\PriceService;
use Illuminate\Console\Command;
use Throwable;

class MercadoLibreSyncCatalogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meli:sync-catalog
                            {--from-raw : Sincronizar a partir de las publicaciones crudas ya almacenadas}
                            {--fixture : Sincronizar directamente a partir del fixture local de autopartes}
                            {--limit= : Límite máximo de artículos a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transforma e importa publicaciones de Mercado Libre al catálogo de refacciones de la tienda';

    public function handle(
        MercadoLibreCatalogSyncService $syncService,
        PriceService $priceService
    ): int {
        $fromRaw = (bool) $this->option('from-raw');
        $fromFixture = (bool) $this->option('fixture');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info('===============================================================');
        $this->info(' JC Autopartes — Sincronización de Catálogo Real (Fase B)    ');
        $this->info('===============================================================');

        try {
            $result = $syncService->sync([
                'from_raw' => $fromRaw,
                'fixture' => $fromFixture,
                'limit' => $limit,
            ]);

            if ($result['total'] === 0) {
                $this->warn('No se encontraron publicaciones crudas para procesar. Ejecuta primero «php artisan meli:fetch-raw» o usa «--fixture».');

                return Command::SUCCESS;
            }

            $tableRows = [];
            foreach ($result['items'] as $item) {
                $tableRows[] = [
                    $item['mercadolibre_id'],
                    mb_substr($item['title'], 0, 36) . '...',
                    $item['sku'],
                    $priceService->format($item['price']),
                    $item['stock'],
                    $item['category'],
                    $item['status'] === 'published' ? 'Publicado' : 'Borrador',
                ];
            }

            $this->info("✅ Catálogo sincronizado con éxito.");
            $this->newLine();

            $this->table(
                ['ID ML', 'Título Refacción', 'SKU / Parte', 'Precio Web', 'Stock', 'Categoría', 'Estado'],
                array_slice($tableRows, 0, 8)
            );

            $this->table(
                ['Métrica', 'Resultado'],
                [
                    ['Total Publicaciones Procesadas', (string) $result['total']],
                    ['Nuevas Refacciones Creadas', (string) $result['created']],
                    ['Refacciones Actualizadas', (string) $result['updated']],
                    ['Fuente de Existencias', 'StockService (lockForUpdate)'],
                    ['Fuente de Precios', 'PriceService (Factor: ' . config('mercadolibre.price_factor', 1.0) . ')'],
                ]
            );

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error("❌ Error durante la sincronización del catálogo: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}

