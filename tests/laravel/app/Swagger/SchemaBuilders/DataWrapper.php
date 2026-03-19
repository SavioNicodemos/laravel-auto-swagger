<?php

namespace App\Swagger\SchemaBuilders;

use AutoSwagger\Docs\Responses\SchemaBuilder;

class DataWrapper implements SchemaBuilder
{
    public function build(string $modelRef, string $uri): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'data' => [
                    '$ref' => $modelRef,
                ],
            ],
        ];
    }
}
