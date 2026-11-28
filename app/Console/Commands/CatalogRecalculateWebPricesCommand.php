<?php

namespace App\Console\Commands;

use App\Models\MarketplaceRawItem;
use App\Models\Product;
use App\Services\PriceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CatalogRecalculateWebPricesCommand extends Command
{
    protected $signature = 'catalog:recalculate-web-prices
                            {--from-raw : Poblar mercadolibre_price desde marketplace_raw_items antes de recalcular}
                            {--discount= : Porcentaje de descuento respecto a ML sin IVA (omite ajuste guardado)}
                            {--dry-run : Simula el conteo y muestra ejemplos sin modificar la base de datos}
                            {--force : Ejecuta la actualización real en la base de datos}';

    protected $description = 'Calcula y actualiza el precio web sin IVA desde el precio de Mercado Libre';

    public function handle(PriceService $priceService): int
    {
        $fromRaw = (bool) $this->option('from-raw');
        $discountOpt = $this->option('discount');
        $discountPct = $discountOpt !== null && $discountOpt !== ''
            ? max(0.0, min(90.0, (float) $discountOpt))
            : $priceService->getMercadoLibreWebDiscountPercentage();

        $dryRun = (bool) $this->option('dry-run') || ! (bool) $this->option('force');

        $this->info("Fórmula aplicada: (Precio ML / 1.16) * (1 - {$discountPct}%) -> sin IVA");

        if ($dryRun) {
            $sampleQuery = Product::query()->whereNotNull('mercadolibre_id')->limit(5)->get(['sku', 'mercadolibre_id', 'price', 'mercadolibre_price']);
            if ($fromRaw) {
                $rawPrices = MarketplaceRawItem::query()
                    ->where('marketplace', 'mercadolibre')
                    ->whereIn('external_id', $sampleQuery->pluck('mercadolibre_id')->filter())
                    ->get()
                    ->mapWithKeys(fn ($r) => [$r->external_id => (float) ($r->raw_payload['price'] ?? 0)]);

                $rows = $sampleQuery->map(function ($p) use ($rawPrices, $priceService, $discountPct) {
                    $ml = $rawPrices[$p->mercadolibre_id] ?? (float) ($p->mercadolibre_price ?: $p->price);
                    return [
                        $p->sku,
                        '$' . number_format($ml, 2),
                        '$' . number_format((float) $p->price, 2),
                        '$' . number_format($priceService->calculateWebPriceFromMercadoLibre($ml, $discountPct), 2),
                    ];
                })->all();
            } else {
                $rows = $sampleQuery->map(function ($p) use ($priceService, $discountPct) {
                    $ml = (float) ($p->mercadolibre_price ?: $p->price);
                    return [
                        $p->sku,
                        '$' . number_format($ml, 2),
                        '$' . number_format((float) $p->price, 2),
                        '$' . number_format($priceService->calculateWebPriceFromMercadoLibre($ml, $discountPct), 2),
                    ];
                })->all();
            }

            $this->table(['SKU', 'Precio ML', 'Precio Actual BD', 'Nuevo Precio Web (sin IVA)'], $rows);
            $this->warn('Modo simulación (--dry-run). Usa --force para guardar los cambios en BD.');

            return self::SUCCESS;
        }

        $linked = 0;
        if ($fromRaw) {
            MarketplaceRawItem::query()
                ->where('marketplace', 'mercadolibre')
                ->select(['id', 'external_id', 'raw_payload'])
                ->orderBy('id')
                ->chunkById(500, function ($rows) use (&$linked) {
                    DB::transaction(function () use ($rows, &$linked) {
                        foreach ($rows as $row) {
                            $mlPrice = round((float) ($row->raw_payload['price'] ?? 0.0), 2);
                            if ($mlPrice > 0) {
                                $linked += Product::query()
                                    ->where('mercadolibre_id', $row->external_id)
                                    ->update(['mercadolibre_price' => $mlPrice]);
                            }
                        }
                    });
                });

            $this->info("Registros vinculados con precio original ML: {$linked}");
        }

        $result = $priceService->recalculateWebPricesFromMercadoLibre($discountPct);

        $this->info("Precios web recalculados: {$result['updated']} artículos (descuento aplicado: {$result['discount_percentage']}%).");

        return self::SUCCESS;
    }
}
