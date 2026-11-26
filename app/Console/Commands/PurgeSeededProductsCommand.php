<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\CatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeSeededProductsCommand extends Command
{
    protected $signature = 'catalog:purge-seeded
                            {--force : Ejecuta la eliminación real (sin esta bandera corre en modo simulación)}';

    protected $description = 'Elimina artículos de prueba sin mercadolibre_id que no estén vinculados a pedidos existentes';

    public function handle(CatalogService $catalogService): int
    {
        $force = (bool) $this->option('force');

        $referencedIds = DB::table('order_items')
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique()
            ->all();

        $candidatesQuery = Product::query()
            ->whereNull('mercadolibre_id')
            ->when(! empty($referencedIds), fn ($q) => $q->whereNotIn('id', $referencedIds));

        $totalWithoutMeli = Product::query()->whereNull('mercadolibre_id')->count();
        $deletableCount = (clone $candidatesQuery)->count();
        $protectedCount = $totalWithoutMeli - $deletableCount;

        $this->info("Artículos sin mercadolibre_id detectados: {$totalWithoutMeli}");
        $this->info("Protegidos por estar en order_items: {$protectedCount}");
        $this->info("Elegibles para eliminar: {$deletableCount}");

        if ($deletableCount === 0) {
            $this->info('No hay artículos de prueba pendientes de eliminar.');

            return self::SUCCESS;
        }

        if (! $force) {
            $this->warn('Modo simulación (--dry-run). Ejecuta con --force para eliminar estos registros.');

            return self::SUCCESS;
        }

        $deleted = DB::transaction(function () use ($candidatesQuery) {
            $ids = $candidatesQuery->pluck('id')->all();
            if (empty($ids)) {
                return 0;
            }

            DB::table('product_photos')->whereIn('product_id', $ids)->delete();

            return Product::query()->whereIn('id', $ids)->delete();
        });

        $catalogService->clearFilterOptionsCache();

        $this->info("✅ Se eliminaron {$deleted} artículos de prueba y se limpió el caché de filtros.");

        return self::SUCCESS;
    }
}
