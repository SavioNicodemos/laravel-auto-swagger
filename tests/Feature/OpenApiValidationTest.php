<?php

namespace AutoSwagger\Docs\Tests\Feature;

use AutoSwagger\Docs\Generator;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\ComplexNestedController;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\FakeController;
use AutoSwagger\Docs\Tests\Fixtures\Traits\ConfigMakerTrait;
use AutoSwagger\Docs\Tests\SchemaTestCase;
use cebe\openapi\Reader;
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
}
