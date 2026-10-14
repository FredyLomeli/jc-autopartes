<?php

namespace App\Console\Commands;

use App\Models\MarketplaceRawItem;
use App\Services\MercadoLibre\MercadoLibreInspectorService;
use Illuminate\Console\Command;
use Throwable;

class MercadoLibreFetchRawItemsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meli:fetch-raw
                            {--limit= : Límite de publicaciones a descargar (ej. 10, 50)}
                            {--status=active : Estado de publicaciones en Mercado Libre (active, paused)}
                            {--fixture : Cargar desde el fixture local realista sin consultar la API externa}
                            {--output= : Ruta destino para exportar el JSON de muestra}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Descarga y almacena el JSON crudo de publicaciones de Mercado Libre para inspección de autopartes';

    public function handle(MercadoLibreInspectorService $inspector): int
    {
        $useFixture = (bool) $this->option('fixture');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $status = (string) ($this->option('status') ?? 'active');
        $outputFile = $this->option('output') ? (string) $this->option('output') : null;

        $this->info('===============================================================');
        $this->info(' JC Autopartes — Extracción e Inspección de Catálogo (Fase B) ');
        $this->info('===============================================================');

        try {
            $progressBar = null;

            $onProgress = function (int $current, int $total, string $itemId) use (&$progressBar) {
                if ($progressBar === null) {
                    $progressBar = $this->output->createProgressBar($total);
                    $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
                    $progressBar->setMessage('Iniciando...');
                    $progressBar->start();
                }

                $progressBar->setMessage("Procesando {$itemId}");
                $progressBar->setProgress($current);
            };

            if ($useFixture) {
                $this->comment('Cargando publicaciones desde fixture local realista (Modo Seguro)...');
                $summary = $inspector->loadAndStoreFromFixture(null, $onProgress);
            } else {
                $this->comment("Consultando API oficial de Mercado Libre (Estado: {$status})...");
                $summary = $inspector->fetchAndStoreFromApi($limit, $status, $onProgress);
            }

            if ($progressBar !== null) {
                $progressBar->finish();
                $this->newLine(2);
            }

            // Exportar el JSON consolidado
            $exportedPath = $inspector->exportSampleJson($outputFile);

            $this->info("✅ Extracción finalizada con éxito.");
            $this->newLine();

            // Muestra de publicaciones guardadas
            $sampleRows = [];

            try {
                $sampleRows = MarketplaceRawItem::query()
                    ->where('marketplace', 'mercadolibre')
                    ->latest('id')
                    ->take(8)
                    ->get()
                    ->map(function (MarketplaceRawItem $row) {
                        return [
                            $row->external_id,
                            mb_substr((string) $row->title, 0, 45) . '...',
                            $row->getMeliAttribute('BRAND') ?? 'N/D',
                            $row->getMeliAttribute('PART_NUMBER') ?? 'N/D',
                            $row->status,
                        ];
                    })
                    ->all();
            } catch (Throwable) {
                $sampleRows = [];
            }

            if (empty($sampleRows) && \Illuminate\Support\Facades\File::exists($exportedPath)) {
                $rawList = json_decode(\Illuminate\Support\Facades\File::get($exportedPath), true) ?? [];
                foreach (array_slice($rawList, 0, 8) as $item) {
                    $brand = null;
                    $partNo = null;
                    foreach ($item['attributes'] ?? [] as $attr) {
                        if (($attr['id'] ?? null) === 'BRAND') {
                            $brand = $attr['value_name'] ?? null;
                        }
                        if (($attr['id'] ?? null) === 'PART_NUMBER') {
                            $partNo = $attr['value_name'] ?? null;
                        }
                    }

                    $sampleRows[] = [
                        $item['id'] ?? 'N/D',
                        mb_substr((string) ($item['title'] ?? ''), 0, 45) . '...',
                        $brand ?? 'N/D',
                        $partNo ?? 'N/D',
                        $item['status'] ?? 'active',
                    ];
                }
            }

            $this->table(
                ['ID Publicación', 'Título de la Refacción', 'Marca', 'Núm. Parte', 'Estado'],
                $sampleRows
            );

            // Resumen de la operación
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Total Publicaciones Crudas Guardadas', (string) $summary['total_stored']],
                    ['Marcas de Vehículos Detectadas', implode(', ', $summary['brands']) ?: 'Ninguna'],
                    ['Categorías de Mercado Libre', implode(', ', $summary['categories']) ?: 'Ninguna'],
                    ['Archivo JSON Exportado', $exportedPath],
                ]
            );

            $this->info("Listo para proceder con el Sub-paso B3: Documento de Mapeo de Campos.");

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error("❌ Error durante la extracción de publicaciones: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
