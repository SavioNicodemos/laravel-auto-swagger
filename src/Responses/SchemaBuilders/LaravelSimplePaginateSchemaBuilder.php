<?php

namespace AutoSwagger\Docs\Responses\SchemaBuilders;

use Illuminate\Support\Str;
use AutoSwagger\Docs\Responses\SchemaBuilder;

/**
 * Class
 * @package AutoSwagger\Docs\Responses\SchemaBuilders
 *
 */
class LaravelSimplePaginateSchemaBuilder implements SchemaBuilder
{

    /**
     * Build a schema for Laravel simple pagination
     *
     * @param string $modelRef the swagger reference for model
     * @param string $uri the current parsing uri
     * @return array
     */
    public function build(string $modelRef, string $uri): array
    {
        if (!Str::startsWith($uri, '/')) {
            $uri = '/' . $uri;
        }
        $url = config('app.url') . $uri;
        return [
            'type' => 'object',
            'required' => ['current_page', 'data', 'first_page_url', 'path', 'per_page'],
            'properties' => [
                "current_page" => [
                    'type' => 'integer',
                    'example' => 2
                ],
                "data" => [
                    'type' => 'array',
                    'items' => [
                        '$ref' => $modelRef
                    ]
                ],
                "first_page_url" => [
                    'type' => 'string',
                    'example' => "$url?page=1"
                ],
                "from" => [
                    'type' => 'integer',
                    'example' => 16
                ],
                "next_page_url" => [
                    'type' => 'string',
                    'example' => "$url?page=3"
                ],
                "path" => [
                    'type' => 'string',
                    'example' => "$url"
                ],
                "per_page" => [
                    'type' => 'integer',
                    'example' => 15
                ],
                "prev_page_url" => [
                    'type' => 'string',
                    'example' => "$url?page=1"
                ],
                "to" => [
                    'type' => 'integer',
                    'example' => 30
                ],
            ]
        ];
    }
}
