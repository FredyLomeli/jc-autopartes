<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MercadoLibreFetchRawTest extends TestCase
{
    private string $testOutputFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testOutputFile = storage_path('app/test_meli_sample_output.json');
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testOutputFile)) {
            File::delete($this->testOutputFile);
        }
        parent::tearDown();
    }

    public function test_command_signature_and_help_are_available(): void
    {
        $this->artisan('meli:fetch-raw --help')
            ->assertExitCode(0)
            ->expectsOutputToContain('--fixture')
            ->expectsOutputToContain('--limit')
            ->expectsOutputToContain('--status');
    }

    public function test_command_runs_with_fixture_mode_and_generates_json(): void
    {
        $this->artisan("meli:fetch-raw --fixture --output={$this->testOutputFile}")
            ->assertExitCode(0)
            ->expectsOutputToContain('Cargando publicaciones desde fixture local realista')
            ->expectsOutputToContain('Extracción finalizada con éxito')
            ->expectsOutputToContain('Total Publicaciones Crudas Guardadas');

        $this->assertFileExists($this->testOutputFile);
        $content = File::get($this->testOutputFile);
        $decoded = json_decode($content, true);

        $this->assertIsArray($decoded);
        $this->assertGreaterThanOrEqual(8, count($decoded));

        // Verificar que contenga atributos de autopartes
        $first = $decoded[0];
        $this->assertEquals('MLM3001001', $first['id']);
        $this->assertArrayHasKey('attributes', $first);
        $this->assertArrayHasKey('pictures', $first);
    }

    public function test_command_without_fixture_and_without_credentials_fails_cleanly(): void
    {
        $this->artisan('meli:fetch-raw')
            ->assertExitCode(1)
            ->expectsOutputToContain('Error durante la extracción de publicaciones');
    }
}
