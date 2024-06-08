<?php

namespace AutoSwagger\Docs\Helpers;

use Illuminate\Support\Arr;

class SwaggerHelper
{
    /*
    * Add example key to property based on its type
    */
    public static function addExampleKey(array &$property): void
    {
        if (!Arr::has($property, 'type') || Arr::has($property, 'example')) {
            return;
        }

        $typeExampleMap = [
            'bigint' => rand(1000000000000000000, 9200000000000000000),
            'integer' => rand(1000000000, 2000000000),
            'mediumint' => rand(1000000, 8000000),
            'smallint' => rand(10000, 32767),
            'tinyint' => rand(100, 127),
            'real' => 0.5,
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'datetime' => date('Y-m-d H:i:s'),
            'timestamp' => date('Y-m-d H:i:s'),
            'string' => 'string',
            'text' => 'a long text',
            'boolean' => rand(0, 1) == 0,
            'bigserial' => rand(1000000000000000000, 9200000000000000000),
            'serial' => rand(1000000000, 2000000000),
            'decimal' => 0.5,
            'float' => 0.5,
            'double' => 0.5,
        ];

        if (array_key_exists($property['type'], $typeExampleMap)) {
            Arr::set($property, 'example', $typeExampleMap[$property['type']]);
        }
    }
}
