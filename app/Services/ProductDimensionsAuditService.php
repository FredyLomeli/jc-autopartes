<?php

namespace App\Services;

use App\Models\MarketplaceRawItem;
use App\Models\Product;
use App\Services\MercadoLibre\MercadoLibreProductMapper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Servicio de auditoría y corrección administrativa de pesos y medidas para envíos.
 */
class ProductDimensionsAuditService
{
    public const MOTOR_MIN_WEIGHT_KG = 50.0;

    public const TRANSMISSION_MIN_WEIGHT_KG = 20.0;

    public const ALLOWED_SCOPES = ['suspect', 'incomplete', 'heavy_low', 'locked', 'all'];

    /**
     * Métricas resumen para tarjetas del reporte auditor.
     *
     * @return array<string, int>
     */
    public function getSummaryStats(): array
    {
        $row = Product::query()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN dimensions_locked = 1 THEN 1 ELSE 0 END) as locked_count,
                SUM(CASE WHEN dimensions_source_incomplete = 1 OR weight_kg IS NULL OR weight_kg <= 0 OR length_cm IS NULL OR length_cm <= 0 OR width_cm IS NULL OR width_cm <= 0 OR height_cm IS NULL OR height_cm <= 0 THEN 1 ELSE 0 END) as incomplete_count,
                SUM(CASE WHEN dimensions_locked = 0 AND category = ? AND (weight_kg IS NULL OR weight_kg < ?) THEN 1 ELSE 0 END) as motors_suspect_count,
                SUM(CASE WHEN dimensions_locked = 0 AND category = ? AND (weight_kg IS NULL OR weight_kg < ?) THEN 1 ELSE 0 END) as transmissions_suspect_count,
                SUM(CASE WHEN dimensions_locked = 0 AND (
                    dimensions_source_incomplete = 1
                    OR weight_kg IS NULL OR weight_kg <= 0
                    OR length_cm IS NULL OR length_cm <= 0
                    OR width_cm IS NULL OR width_cm <= 0
                    OR height_cm IS NULL OR height_cm <= 0
                    OR (category = ? AND weight_kg < ?)
                    OR (category = ? AND weight_kg < ?)
                ) THEN 1 ELSE 0 END) as suspect_count
            ', [
                'Motores',
                self::MOTOR_MIN_WEIGHT_KG,
                'Transmisiones',
                self::TRANSMISSION_MIN_WEIGHT_KG,
                'Motores',
                self::MOTOR_MIN_WEIGHT_KG,
                'Transmisiones',
                self::TRANSMISSION_MIN_WEIGHT_KG,
            ])
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'locked_count' => (int) ($row->locked_count ?? 0),
            'incomplete_count' => (int) ($row->incomplete_count ?? 0),
            'motors_suspect_count' => (int) ($row->motors_suspect_count ?? 0),
            'transmissions_suspect_count' => (int) ($row->transmissions_suspect_count ?? 0),
            'suspect_count' => (int) ($row->suspect_count ?? 0),
        ];
    }

    /**
     * Lista paginada para edición rápida de medidas/peso en /admin/articulos/auditoria-medidas.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPaginatedAuditItems(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery($filters);

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $product) => $this->formatAuditRow($product));
    }

    /**
     * Actualiza peso y medidas de una refacción activando protección frente a re-sync de ML.
     *
     * @param  array{weight_kg: float|int|string, length_cm: float|int|string, width_cm: float|int|string, height_cm: float|int|string, dimensions_locked?: bool}  $data
     */
    public function updateDimensions(Product $product, array $data): Product
    {
        $locked = array_key_exists('dimensions_locked', $data)
            ? (bool) $data['dimensions_locked']
            : true;

        return DB::transaction(function () use ($product, $data, $locked) {
            $fresh = Product::query()->lockForUpdate()->findOrFail($product->id);

            $fresh->update([
                'weight_kg' => round((float) $data['weight_kg'], 2),
                'length_cm' => round((float) $data['length_cm'], 2),
                'width_cm' => round((float) $data['width_cm'], 2),
                'height_cm' => round((float) $data['height_cm'], 2),
                'dimensions_locked' => $locked,
            ]);

            return $fresh->fresh();
        });
    }

    /**
     * Exporta en CSV (UTF-8 con BOM para Excel) los artículos bajo los filtros activos.
     *
     * @param  array<string, mixed>  $filters
     */
    public function streamCsvExport(array $filters = []): StreamedResponse
    {
        $filename = 'auditoria-pesos-medidas-' . now()->format('Ymd-His') . '.csv';
        $query = $this->buildFilteredQuery($filters);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // BOM UTF-8 para abrir acentos correctamente en Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'SKU',
                'MercadoLibre ID',
                'Titulo',
                'Categoria',
                'Peso (kg)',
                'Largo (cm)',
                'Ancho (cm)',
                'Alto (cm)',
                'Hallazgo Auditoria',
                'Protegido vs ML',
                'Publicacion ML',
            ]);

            $query->chunk(500, function ($products) use ($handle) {
                foreach ($products as $product) {
                    $row = $this->formatAuditRow($product);
                    fputcsv($handle, [
                        $row['id'],
                        $row['sku'],
                        $row['mercadolibre_id'] ?? '',
                        $row['title'],
                        $row['category'],
                        $row['weight_kg'],
                        $row['length_cm'],
                        $row['width_cm'],
                        $row['height_cm'],
                        $row['audit_reason'],
                        $row['dimensions_locked'] ? 'Sí' : 'No',
                        $row['mercadolibre_permalink'] ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Marca en bloque `dimensions_source_incomplete` desde `marketplace_raw_items` sin sobrescribir medidas actuales.
     *
     * @return array{scanned: int, marked_incomplete: int, cleared: int}
     */
    public function backfillIncompleteFlagsFromRaw(int $chunkSize = 500): array
    {
        $mapper = app(MercadoLibreProductMapper::class);
        $scanned = 0;
        $incompleteIds = [];
        $completeIds = [];

        MarketplaceRawItem::query()
            ->where('marketplace', 'mercadolibre')
            ->select(['id', 'external_id', 'raw_payload'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use ($mapper, &$scanned, &$incompleteIds, &$completeIds) {
                foreach ($rows as $row) {
                    $scanned++;
                    $payload = is_array($row->raw_payload) ? $row->raw_payload : [];
                    $rawAttributes = $payload['attributes'] ?? [];
                    $isIncomplete = $mapper->hasIncompleteSourceDimensions($payload, $rawAttributes);

                    if ($isIncomplete) {
                        $incompleteIds[] = (string) $row->external_id;
                    } else {
                        $completeIds[] = (string) $row->external_id;
                    }
                }
            });

        $markedIncomplete = 0;
        foreach (array_chunk($incompleteIds, 500) as $idsChunk) {
            $markedIncomplete += Product::query()
                ->whereIn('mercadolibre_id', $idsChunk)
                ->update(['dimensions_source_incomplete' => true]);
        }

        $cleared = 0;
        foreach (array_chunk($completeIds, 500) as $idsChunk) {
            $cleared += Product::query()
                ->whereIn('mercadolibre_id', $idsChunk)
                ->where('dimensions_source_incomplete', true)
                ->update(['dimensions_source_incomplete' => false]);
        }

        return [
            'scanned' => $scanned,
            'marked_incomplete' => $markedIncomplete,
            'cleared' => $cleared,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        $scope = (string) ($filters['scope'] ?? 'suspect');
        if (! in_array($scope, self::ALLOWED_SCOPES, true)) {
            $scope = 'suspect';
        }

        $query = Product::query()
            ->select([
                'id',
                'mercadolibre_id',
                'mercadolibre_permalink',
                'sku',
                'title',
                'category',
                'weight_kg',
                'length_cm',
                'width_cm',
                'height_cm',
                'dimensions_locked',
                'dimensions_source_incomplete',
                'status',
            ]);

        $this->applyScopeFilter($query, $scope);

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $query->where('category', $category);
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            app(CatalogService::class)->applySearchFilter($query, $search, false);
        }

        return $query
            ->orderByDesc('dimensions_source_incomplete')
            ->orderByRaw('CASE WHEN category = ? THEN 0 WHEN category = ? THEN 1 ELSE 2 END', ['Motores', 'Transmisiones'])
            ->orderBy('weight_kg')
            ->orderByDesc('id');
    }

    private function applyScopeFilter(Builder $query, string $scope): void
    {
        if ($scope === 'all') {
            return;
        }

        if ($scope === 'locked') {
            $query->where('dimensions_locked', true);

            return;
        }

        if ($scope === 'incomplete') {
            $query->where(function (Builder $q) {
                $q->where('dimensions_source_incomplete', true)
                    ->orWhereNull('weight_kg')->orWhere('weight_kg', '<=', 0)
                    ->orWhereNull('length_cm')->orWhere('length_cm', '<=', 0)
                    ->orWhereNull('width_cm')->orWhere('width_cm', '<=', 0)
                    ->orWhereNull('height_cm')->orWhere('height_cm', '<=', 0);
            });

            return;
        }

        if ($scope === 'heavy_low') {
            $query->where('dimensions_locked', false)
                ->where(function (Builder $q) {
                    $q->where(function (Builder $m) {
                        $m->where('category', 'Motores')
                            ->where(function (Builder $mw) {
                                $mw->whereNull('weight_kg')->orWhere('weight_kg', '<', self::MOTOR_MIN_WEIGHT_KG);
                            });
                    })->orWhere(function (Builder $t) {
                        $t->where('category', 'Transmisiones')
                            ->where(function (Builder $tw) {
                                $tw->whereNull('weight_kg')->orWhere('weight_kg', '<', self::TRANSMISSION_MIN_WEIGHT_KG);
                            });
                    });
                });

            return;
        }

        // 'suspect' (por defecto): no verificados que falten en ML, tengan ceros o peso bajo en pesados
        $query->where('dimensions_locked', false)
            ->where(function (Builder $q) {
                $q->where('dimensions_source_incomplete', true)
                    ->orWhereNull('weight_kg')->orWhere('weight_kg', '<=', 0)
                    ->orWhereNull('length_cm')->orWhere('length_cm', '<=', 0)
                    ->orWhereNull('width_cm')->orWhere('width_cm', '<=', 0)
                    ->orWhereNull('height_cm')->orWhere('height_cm', '<=', 0)
                    ->orWhere(function (Builder $m) {
                        $m->where('category', 'Motores')
                            ->where('weight_kg', '<', self::MOTOR_MIN_WEIGHT_KG);
                    })
                    ->orWhere(function (Builder $t) {
                        $t->where('category', 'Transmisiones')
                            ->where('weight_kg', '<', self::TRANSMISSION_MIN_WEIGHT_KG);
                    });
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAuditRow(Product $product): array
    {
        $weight = $product->weight_kg !== null ? (float) $product->weight_kg : 0.0;
        $length = $product->length_cm !== null ? (float) $product->length_cm : 0.0;
        $width = $product->width_cm !== null ? (float) $product->width_cm : 0.0;
        $height = $product->height_cm !== null ? (float) $product->height_cm : 0.0;

        $reason = 'Medidas completas';
        $severity = 'ok';

        if ($product->dimensions_locked) {
            $reason = 'Verificado y protegido vs ML';
            $severity = 'locked';
        } elseif ($weight <= 0 || $length <= 0 || $width <= 0 || $height <= 0) {
            $reason = 'Falta peso o dimensión (>0)';
            $severity = 'critical';
        } elseif ($product->dimensions_source_incomplete) {
            $reason = 'Sin medidas/peso en ML (usó estimado)';
            $severity = 'warning';
        } elseif ($product->category === 'Motores' && $weight < self::MOTOR_MIN_WEIGHT_KG) {
            $reason = 'Motor con peso sospechosamente bajo (<50 kg)';
            $severity = 'critical';
        } elseif ($product->category === 'Transmisiones' && $weight < self::TRANSMISSION_MIN_WEIGHT_KG) {
            $reason = 'Transmisión con peso bajo (<20 kg)';
            $severity = 'warning';
        }

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'mercadolibre_id' => $product->mercadolibre_id,
            'mercadolibre_permalink' => $product->mercadolibre_permalink,
            'title' => $product->title,
            'category' => $product->category,
            'status' => $product->status,
            'weight_kg' => $weight,
            'length_cm' => $length,
            'width_cm' => $width,
            'height_cm' => $height,
            'dimensions_locked' => (bool) $product->dimensions_locked,
            'dimensions_source_incomplete' => (bool) $product->dimensions_source_incomplete,
            'audit_reason' => $reason,
            'severity' => $severity,
        ];
    }
}
