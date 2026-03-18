<?php

namespace AutoSwagger\Docs\Tests\Feature;

use AutoSwagger\Docs\Generator;
use AutoSwagger\Docs\Helpers\ConfigHelper;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\ComplexNestedController;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\FakeController;
use AutoSwagger\Docs\Tests\Fixtures\Traits\ConfigMakerTrait;
use AutoSwagger\Docs\Tests\SchemaTestCase;
use cebe\openapi\Reader;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Route;

class OpenApiValidationTest extends SchemaTestCase
{
    use ConfigMakerTrait;

    public function test_generated_output_is_valid_openapi_3(): void
    {
        Route::get('/api/users', [FakeController::class, 'index']);
        Route::get('/api/users/{id}', [FakeController::class, 'show']);
        Route::post('/api/users', [FakeController::class, 'store']);

        $result = (new Generator($this->makeConfig()))->generate();

        $openapi = Reader::readFromJson(json_encode($result));
        $valid = $openapi->validate();

        $this->assertTrue($valid, implode("\n", $openapi->getErrors()));
    }

    public function test_complex_nested_request_with_schemas_produces_valid_openapi_3(): void
    {
        Route::post('/api/query', [ComplexNestedController::class, 'query']);

        $result = (new Generator($this->makeConfig([
            'schemas' => [app_path('Swagger/Schemas')],
        ])))->generate();

        $post = $result['paths']['/query']['post'];

        // Request body with all top-level fields is present
        $schema = $post['requestBody']['content']['application/json']['schema'];
        $this->assertArrayHasKey('departure_date', $schema['properties']);
        $this->assertArrayHasKey('passengers', $schema['properties']);
        $this->assertArrayHasKey('filters', $schema['properties']);

        // Schemas from custom classes are present
        $schemas = (array) $result['components']['schemas'];
        $this->assertArrayHasKey('FlightSearchResult', $schemas);
        $this->assertArrayHasKey('FlightSegment', $schemas);
        $this->assertArrayHasKey('LowestFare', $schemas);
        $this->assertArrayHasKey('FlightLinks', $schemas);

        $openapi = Reader::readFromJson(json_encode($result));
        $valid = $openapi->validate();

        $this->assertTrue($valid, implode("\n", $openapi->getErrors()));
    }

    public function test_two_pages_generate_two_isolated_valid_openapi_3_documents(): void
    {
        Route::get('/api/users', [FakeController::class, 'index']);
        Route::post('/api/users', [FakeController::class, 'store']);
        Route::post('/internal/query', [ComplexNestedController::class, 'query']);

        $base = [
            'title'       => 'Test API',
            'description' => 'Test',
            'version'     => '1.0.0',
            'host'        => 'http://localhost',
            'servers'     => [],
            'schemas'     => [],
            'tags'        => [],
            'default_tags_generation_strategy' => 'prefix',
            'authentication_flow'  => ['bearerAuth' => 'http'],
            'security_middlewares' => ['auth:api'],
            'parse'   => ['docBlock' => true, 'security' => false],
            'ignored' => ['methods' => ['head', 'options'], 'routes' => [], 'models' => ['*']],
            'append'  => ['responses' => [], 'headers' => []],
            'pages'   => [
                'default' => [
                    'title'         => 'Public API',
                    'api_base_path' => '/api',
                ],
                'internal' => [
                    'title'         => 'Internal API',
                    'api_base_path' => '/internal',
                    'schemas'       => [app_path('Swagger/Schemas')],
                ],
            ],
        ];

        config(['swagger' => $base]);

        $defaultResult  = (new Generator(new Repository(['swagger' => ConfigHelper::resolvePageConfig('default')])))->generate();
        $internalResult = (new Generator(new Repository(['swagger' => ConfigHelper::resolvePageConfig('internal')])))->generate();

        // Each page only contains its own routes
        $this->assertArrayHasKey('/users', $defaultResult['paths']);
        $this->assertArrayNotHasKey('/query', $defaultResult['paths']);

        $this->assertArrayHasKey('/query', $internalResult['paths']);
        $this->assertArrayNotHasKey('/users', $internalResult['paths']);

        // Internal page carries the flight schemas; public page does not
        $this->assertArrayHasKey('FlightSearchResult', (array) $internalResult['components']['schemas']);
        $this->assertArrayNotHasKey('FlightSearchResult', (array) $defaultResult['components']['schemas']);

        // Both documents are valid OpenAPI 3.0
        $defaultOpenApi = Reader::readFromJson(json_encode($defaultResult));
        $this->assertTrue($defaultOpenApi->validate(), implode("\n", $defaultOpenApi->getErrors()));

        $internalOpenApi = Reader::readFromJson(json_encode($internalResult));
        $this->assertTrue($internalOpenApi->validate(), implode("\n", $internalOpenApi->getErrors()));
    }
}
