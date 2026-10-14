<?php

namespace Tests\Feature;

use Tests\TestCase;

class MercadoLibreSyncCatalogTest extends TestCase
{
    public function test_command_signature_and_help_are_available(): void
    {
        $this->artisan('meli:sync-catalog --help')
            ->assertExitCode(0)
            ->expectsOutputToContain('--fixture')
            ->expectsOutputToContain('--from-raw')
            ->expectsOutputToContain('--limit');
    }

    public function test_command_runs_with_fixture_mode_and_displays_summary(): void
    {
        $this->artisan('meli:sync-catalog --fixture')
            ->assertExitCode(0)
            ->expectsOutputToContain('Catálogo sincronizado con éxito')
            ->expectsOutputToContain('StockService (lockForUpdate)')
            ->expectsOutputToContain('PriceService');
    }

    public function test_command_respects_limit_option(): void
    {
        $this->artisan('meli:sync-catalog --fixture --limit=3')
            ->assertExitCode(0)
            ->expectsOutputToContain('Catálogo sincronizado con éxito');
    }
}
