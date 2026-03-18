<?php

namespace AutoSwagger\Docs\Tests\Unit\Helpers;

use AutoSwagger\Docs\Helpers\ConversionHelper;
use AutoSwagger\Docs\Tests\TestCase;

class ConversionHelperTest extends TestCase
{
    /**
     * @dataProvider phpToSwaggerProvider
     */
    public function test_php_type_to_swagger_type(string $phpType, string $expected): void
    {
        $this->assertSame($expected, ConversionHelper::phpTypeToSwaggerType($phpType));
    }

    public static function phpToSwaggerProvider(): array
    {
        return [
            ['int',     'integer'],
            ['integer', 'integer'],
            ['float',   'number'],
            ['double',  'number'],
            ['string',  'string'],
            ['bool',    'boolean'],
            ['boolean', 'boolean'],
            ['array',   'array'],
            ['object',  'object'],
            ['mixed',   'object'],
            ['null',    'null'],
            ['unknown', 'string'],  // unknown type falls back to string
        ];
    }

    public function test_php_type_to_swagger_type_with_null_input(): void
    {
        $this->assertSame('string', ConversionHelper::phpTypeToSwaggerType(null));
    }

    public function test_php_type_to_swagger_type_with_empty_string(): void
    {
        $this->assertSame('string', ConversionHelper::phpTypeToSwaggerType(''));
    }

    /**
     * @dataProvider swaggerToPhpProvider
     */
    public function test_swagger_type_to_php_type(string $swaggerType, string $expected): void
    {
        $this->assertSame($expected, ConversionHelper::swaggerTypeToPhpType($swaggerType));
    }

    public static function swaggerToPhpProvider(): array
    {
        return [
            ['number',  'integer'],
            ['string',  'string'],
            ['boolean', 'boolean'],
            ['array',   'array'],
            ['integer', 'integer'],
            ['null',    'null'],
            ['object',  'object'],
            ['unknown', 'string'],  // unknown type falls back to string
        ];
    }

    public function test_swagger_type_to_php_type_with_null_input(): void
    {
        $this->assertSame('string', ConversionHelper::swaggerTypeToPhpType(null));
    }

    public function test_dbal_type_integer_maps_to_int32(): void
    {
        $result = ConversionHelper::DBalTypeToSwaggerType('integer');

        $this->assertSame('integer', $result['type']);
        $this->assertSame('int32', $result['format']);
        $this->assertSame('integer', $result['description']);
    }

    public function test_dbal_type_bigint_maps_to_int64(): void
    {
        $result = ConversionHelper::DBalTypeToSwaggerType('bigint');

        $this->assertSame('integer', $result['type']);
        $this->assertSame('int64', $result['format']);
    }

    public function test_dbal_type_string_maps_correctly(): void
    {
        $result = ConversionHelper::DBalTypeToSwaggerType('string');

        $this->assertSame('string', $result['type']);
        $this->assertArrayNotHasKey('format', $result);
    }

    public function test_dbal_type_datetime_maps_to_date_time_format(): void
    {
        $result = ConversionHelper::DBalTypeToSwaggerType('datetime');

        $this->assertSame('string', $result['type']);
        $this->assertSame('date-time', $result['format']);
    }

    public function test_dbal_type_boolean_maps_correctly(): void
    {
        $result = ConversionHelper::DBalTypeToSwaggerType('boolean');

        $this->assertSame('boolean', $result['type']);
    }

    public function test_dbal_type_unknown_falls_back_to_string(): void
    {
        $result = ConversionHelper::DBalTypeToSwaggerType('nonexistent_type');

        $this->assertSame('string', $result['type']);
    }

    public function test_dbal_type_is_case_insensitive(): void
    {
        $lower = ConversionHelper::DBalTypeToSwaggerType('integer');
        $upper = ConversionHelper::DBalTypeToSwaggerType('INTEGER');

        $this->assertSame($lower['type'], $upper['type']);
        $this->assertSame($lower['format'], $upper['format']);
    }

    public function test_dbal_type_always_includes_description_key(): void
    {
        $result = ConversionHelper::DBalTypeToSwaggerType('string');

        $this->assertArrayHasKey('description', $result);
        $this->assertSame('string', $result['description']);
    }
}
