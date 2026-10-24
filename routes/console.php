<?php

use App\Jobs\SyncMercadoLibreIncrementalJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas Programadas para Hosting Compartido (DirectAdmin / cPanel)
|--------------------------------------------------------------------------
| Estas tareas se ejecutan mediante un único Cron Job en DirectAdmin:
| * * * * * cd /home/usuario/domains/tudominio.com && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
*/

// 1. Procesamiento continuo de tareas encoladas (sin requerir daemon permanente)
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

// 2. Sincronización incremental periódica con Mercado Libre (cada 2 horas)
Schedule::job(new SyncMercadoLibreIncrementalJob(limit: 50, autoContinue: false))
    ->everyTwoHours()
    ->withoutOverlapping();
