<?php

namespace AutoSwagger\Docs\Tests\Feature;

use AutoSwagger\Docs\Tests\Fixtures\Controllers\ComplexNestedController;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\FakeController;
use AutoSwagger\Docs\Tests\SchemaTestCase;
use cebe\openapi\Reader;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

class MultiPageSwaggerTest extends SchemaTestCase
{
    /**
     * Configure both swagger pages before the service provider boots so that
     * SwaggerServiceProvider::registerPageRoutes() picks them up and registers
     * the /docs/default/content and /docs/internal/content HTTP endpoints.
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('swagger.pages', [
            'default' => [
                'title'         => 'Public API',
                'description'   => 'Public-facing routes',
                'version'       => '1.0.0',
                'host'          => 'http://localhost',
                'path'          => '/docs/default',
                'middleware'    => [],
                'api_base_path' => '/api',
                'servers'       => [],
                'schemas'       => [],
                'generated'     => true,
                'parse'         => ['docBlock' => true, 'security' => false],
                'append'        => ['responses' => [], 'headers' => []],
                'tags'          => [],
                'default_tags_generation_strategy' => 'prefix',
                'authentication_flow'  => ['bearerAuth' => 'http'],
                'security_middlewares' => ['auth:api'],
            ],
            'internal' => [
                'title'         => 'Internal API',
                'description'   => 'Internal team routes',
                'version'       => '1.0.0',
                'host'          => 'http://localhost',
                'path'          => '/docs/internal',
                'middleware'    => [],
                'api_base_path' => '/internal',
                'servers'       => [],
                'schemas'       => [app_path('Swagger/Schemas')],
                'generated'     => true,
                'parse'         => ['docBlock' => true, 'security' => false],
                'append'        => ['responses' => [], 'headers' => []],
                'tags'          => [],
                'default_tags_generation_strategy' => 'prefix',
                'authentication_flow'  => ['bearerAuth' => 'http'],
                'security_middlewares' => ['auth:api'],
            ],
        ]);
    }

    public function test_two_pages_serve_isolated_valid_openapi_3_documents_via_http(): void
    {
        Route::get('/api/users', [FakeController::class, 'index']);
        Route::post('/api/users', [FakeController::class, 'store']);
        Route::post('/internal/query', [ComplexNestedController::class, 'query']);

        $defaultResponse  = $this->get('/docs/default/content');
        $internalResponse = $this->get('/docs/internal/content');

        $defaultResponse->assertStatus(200);
        $internalResponse->assertStatus(200);

        $defaultDoc  = json_decode($defaultResponse->getContent(), true);
        $internalDoc = json_decode($internalResponse->getContent(), true);

        // Each page only exposes routes under its own api_base_path
        $this->assertArrayHasKey('/users', $defaultDoc['paths']);
        $this->assertArrayNotHasKey('/query', $defaultDoc['paths']);

        $this->assertArrayHasKey('/query', $internalDoc['paths']);
        $this->assertArrayNotHasKey('/users', $internalDoc['paths']);

        // Schemas registered for the internal page are absent from the public page
        $this->assertArrayHasKey('FlightSearchResult', (array) $internalDoc['components']['schemas']);
        $this->assertArrayNotHasKey('FlightSearchResult', (array) $defaultDoc['components']['schemas']);

        // Both documents are valid OpenAPI 3.0
        $defaultOpenApi = Reader::readFromJson($defaultResponse->getContent());
        $this->assertTrue($defaultOpenApi->validate(), implode("\n", $defaultOpenApi->getErrors()));

        $internalOpenApi = Reader::readFromJson($internalResponse->getContent());
        $this->assertTrue($internalOpenApi->validate(), implode("\n", $internalOpenApi->getErrors()));
    }
}
