<?php

namespace App\Console\Commands;

use App\Jobs\SyncMercadoLibreIncrementalJob;
use Illuminate\Console\Command;

class MercadoLibreDispatchSyncJobCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meli:queue-sync
                            {--limit=50 : Cantidad de refacciones por lote (por defecto 50)}
                            {--all : Encadenar automáticamente lotes hasta sincronizar los 12,000 productos}
                            {--fixture : Ejecutar con datos locales de prueba}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha la tarea de sincronización de Mercado Libre a la cola en segundo plano (Queue)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $all = (bool) $this->option('all');
        $fixture = (bool) $this->option('fixture');

        $this->info('===============================================================');
        $this->info(' JC Autopartes — Despacho a Cola en Segundo Plano (Queue)     ');
        $this->info('===============================================================');

        SyncMercadoLibreIncrementalJob::dispatch(
            limit: $limit,
            scrollId: null,
            offset: 0,
            status: 'active',
            autoContinue: $all,
            fixture: $fixture,
        );

        $this->info("✅ Job despachado a la cola de base de datos exitosamente.");
        $this->line("   - Límite por lote: <comment>{$limit}</comment>");
        $this->line("   - Modo continuo (--all): <comment>" . ($all ? 'Activo (encadenará lotes hasta terminar)' : 'Inactivo (solo un lote)') . "</comment>");
        $this->line("   - Driver de cola: <comment>" . config('queue.default') . "</comment>");
        $this->newLine();
        $this->comment("El proceso se ejecutará silenciosamente en segundo plano sin bloquear tu servidor.");

        return Command::SUCCESS;
    }
}
