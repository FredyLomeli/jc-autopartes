<?php

namespace Tests\Unit;

use App\Jobs\SyncMercadoLibreIncrementalJob;
use App\Services\MercadoLibre\MercadoLibreCatalogSyncService;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncMercadoLibreIncrementalJobTest extends TestCase
{
    public function test_job_aborts_silently_if_unauthenticated_and_not_fixture(): void
    {
        $syncServiceMock = $this->createMock(MercadoLibreCatalogSyncService::class);
        $syncServiceMock->expects($this->never())->method('syncBatch');

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);
        $oauthMock->method('getActiveToken')->willReturn(null);

        $job = new SyncMercadoLibreIncrementalJob(fixture: false);
        $job->handle($syncServiceMock, $oauthMock);
    }

    public function test_job_executes_batch_successfully_with_fixture(): void
    {
        $syncServiceMock = $this->createMock(MercadoLibreCatalogSyncService::class);
        $syncServiceMock->expects($this->once())
            ->method('syncBatch')
            ->willReturn([
                'processed_count' => 2,
                'created_count' => 2,
                'updated_count' => 0,
                'has_more' => false,
                'next_scroll_id' => null,
                'next_offset' => 2,
            ]);

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);

        $job = new SyncMercadoLibreIncrementalJob(limit: 2, fixture: true, autoContinue: false);
        $job->handle($syncServiceMock, $oauthMock);
    }

    public function test_job_auto_chains_next_batch_when_auto_continue_is_true(): void
    {
        Queue::fake();

        $syncServiceMock = $this->createMock(MercadoLibreCatalogSyncService::class);
        $syncServiceMock->method('syncBatch')
            ->willReturn([
                'processed_count' => 50,
                'created_count' => 45,
                'updated_count' => 5,
                'has_more' => true,
                'next_scroll_id' => 'next_scroll_token_999',
                'next_offset' => 50,
            ]);

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);

        $job = new SyncMercadoLibreIncrementalJob(
            limit: 50,
            scrollId: 'initial_token',
            offset: 0,
            autoContinue: true,
            fixture: true
        );

        $job->handle($syncServiceMock, $oauthMock);

        Queue::assertPushed(SyncMercadoLibreIncrementalJob::class, function ($pushedJob) {
            return $pushedJob->scrollId === 'next_scroll_token_999'
                && $pushedJob->offset === 50
                && $pushedJob->autoContinue === true;
        });
    }

    public function test_command_dispatches_job_to_queue(): void
    {
        Queue::fake();

        $this->artisan('meli:queue-sync', [
            '--limit' => 25,
            '--all' => true,
            '--fixture' => true,
        ])->assertSuccessful();

        Queue::assertPushed(SyncMercadoLibreIncrementalJob::class, function ($job) {
            return $job->limit === 25
                && $job->autoContinue === true
                && $job->fixture === true;
        });
    }
}
