<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if (file_exists(dirname(__DIR__) . '/bootstrap/cache/config.php')) {
            throw new RuntimeException(
                'BLOQUEO DE SEGURIDAD: existe bootstrap/cache/config.php. Ejecuta "php artisan optimize:clear" antes de correr las pruebas para no afectar la base de datos configurada en caché.'
            );
        }

        parent::setUp();

        if (
            ! $this->app->environment('testing')
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new RuntimeException(
                'BLOQUEO DE SEGURIDAD: las pruebas solo pueden ejecutarse en entorno testing con sqlite :memory:.'
            );
        }
    }
}
