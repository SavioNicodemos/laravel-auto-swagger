<?php

namespace AutoSwagger\Docs\Tests;

use AutoSwagger\Docs\SwaggerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [SwaggerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.url', 'http://localhost');
        $app['config']->set('app.name', 'TestApp');

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // After all providers have booted and merged their defaults,
        // override to ignore all models so DB introspection is skipped.
        config(['swagger.ignored.models' => ['*']]);
    }
}
