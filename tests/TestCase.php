<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (
            $this->app->configurationIsCached()
            || ! $this->app->environment('testing')
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new RuntimeException(
                'BLOQUEO DE SEGURIDAD: las pruebas solo pueden ejecutarse sin config cacheada de producción, en entorno testing y con sqlite :memory:.'
            );
        }
    }
}
