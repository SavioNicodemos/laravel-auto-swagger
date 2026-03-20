<?php

namespace AutoSwagger\Docs\Tests\Feature\Generator;

use AutoSwagger\Docs\Generator;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\FakeController;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\UnusedSchemaController;
use AutoSwagger\Docs\Tests\Fixtures\Traits\ConfigMakerTrait;
use AutoSwagger\Docs\Tests\SchemaTestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class UnusedSchemaWarningTest extends SchemaTestCase
{
    use ConfigMakerTrait;

    private function schemasPath(): string
    {
        return app_path('Swagger/TestOnlySchemas');
    }

    public function test_it_warns_when_schema_is_defined_but_not_referenced(): void
    {
        Route::get('/api/items', [FakeController::class, 'index']);

        Log::shouldReceive('warning')
            ->once()
            ->with(\Mockery::on(fn(string $msg) => str_contains($msg, 'UnusedSchema')));

        (new Generator($this->makeConfig([
            'schemas' => [$this->schemasPath()],
        ])))->generate();
    }

    public function test_it_does_not_warn_when_schema_is_referenced_in_a_response(): void
    {
        Route::get('/api/items', [UnusedSchemaController::class, 'index']);

        Log::shouldReceive('warning')->never();

        (new Generator($this->makeConfig([
            'schemas' => [$this->schemasPath()],
        ])))->generate();
    }

    public function test_it_does_not_warn_when_no_schemas_are_defined(): void
    {
        Route::get('/api/items', [FakeController::class, 'index']);

        Log::shouldReceive('warning')->never();

        (new Generator($this->makeConfig([
            'schemas' => [],
        ])))->generate();
    }
}
