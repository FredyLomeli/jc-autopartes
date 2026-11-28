<?php

namespace App\Console\Commands;

use App\Services\ProductDimensionsAuditService;
use Illuminate\Console\Command;

class CatalogAuditDimensionsCommand extends Command
{
    protected $signature = 'catalog:audit-dimensions';

    protected $description = 'Marca artículos sin medidas/peso reales en marketplace_raw_items sin alterar valores actuales';

    public function handle(ProductDimensionsAuditService $auditService): int
    {
        $this->info('Analizando medidas y pesos originales en marketplace_raw_items...');

        $result = $auditService->backfillIncompleteFlagsFromRaw();
        $stats = $auditService->getSummaryStats();

        $this->table(
            ['Indicador', 'Cantidad'],
            [
                ['Registros crudos ML revisados', $result['scanned']],
                ['Marcados como incompletos en ML', $result['marked_incomplete']],
                ['Limpiados (completos en ML)', $result['cleared']],
                ['Total sospechosos / por revisar en Admin', $stats['suspect_count']],
                ['Motores < 50 kg sin verificar', $stats['motors_suspect_count']],
                ['Transmisiones < 20 kg sin verificar', $stats['transmissions_suspect_count']],
                ['Ya protegidos manualmente vs ML', $stats['locked_count']],
            ]
        );

        return self::SUCCESS;
    }
}
