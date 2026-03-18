<?php

namespace AutoSwagger\Docs\Tests\Fixtures\Traits;

use Illuminate\Config\Repository;

trait ConfigMakerTrait
{
    protected function makeConfig(array $overrides = []): Repository
    {
        return new Repository(['swagger' => array_merge([
            'title'       => 'Test API',
            'description' => 'Test API Description',
            'version'     => '1.0.0',
            'host'        => 'http://localhost',
            'api_base_path' => '/api',
            'servers'     => [],
            'schemas'     => [],
            'tags'        => [],
            'default_tags_generation_strategy' => 'prefix',
            'authentication_flow'  => ['bearerAuth' => 'http'],
            'security_middlewares' => ['auth:api', 'auth:sanctum'],
            'parse'       => ['docBlock' => true, 'security' => true],
            'ignored'     => [
                'methods' => ['head', 'options'],
                'routes'  => [],
                'models'  => ['*'],
            ],
            'append' => [
                'responses' => [],
                'headers'   => [],
            ],
        ], $overrides)]);
    }
}