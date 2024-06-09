<?php

namespace AutoSwagger\Docs\Helpers;

class ConversionHelper
{
    public static function phpTypeToSwaggerType(?string $type): string
    {
        if (empty($type)) {
            return 'string';
        }

        $types = [
            'int' => 'integer',
            'integer' => 'integer',
            'float' => 'number',
            'double' => 'number',
            'string' => 'string',
            'bool' => 'boolean',
            'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'object',
            'mixed' => 'object',
            'null' => 'null',
        ];

        return $types[$type] ?? 'string';
    }

    /**
     * @return array array with 'type' and 'format' as keys
     */
    public static function DBalTypeToSwaggerType(string $type): array
    {
        $lowerType = strtolower($type);

        $typeFormatMap = [
            'smallint' => ['type' => 'integer', 'format' => 'int32'],
            'integer' => ['type' => 'integer', 'format' => 'int32'],
            'decimal' => ['type' => 'number', 'format' => 'float'],
            'string' => ['type' => 'string'],
            'text' => ['type' => 'string'],
            'guid' => ['type' => 'string'],
            'binary' => ['type' => 'object', 'format' => 'binary'],
            'datetime' => ['type' => 'string', 'format' => 'date-time'],
            'datetimetz' => ['type' => 'string', 'format' => 'date-time'],
            'time' => ['type' => 'string', 'format' => 'time'],
            'array' => ['type' => 'array'],
            'simple_array' => ['type' => 'array'],
            'json' => ['type' => 'object'],
            'object' => ['type' => 'object'],
            'bigint' => ['type' => 'integer', 'format' => 'int64'],
            'year' => ['type' => 'integer'],
            'float' => ['type' => 'number', 'format' => 'float'],
            'real' => ['type' => 'number', 'format' => 'double'],
            'boolean' => ['type' => 'boolean'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
            'blob' => ['type' => 'object', 'format' => 'binary'],
        ];

        $property = $typeFormatMap[$lowerType] ?? ['type' => 'string'];
        $property['description'] = $type;

        return $property;
    }
}
