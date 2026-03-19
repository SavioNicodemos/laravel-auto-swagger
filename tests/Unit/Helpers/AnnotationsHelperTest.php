<?php

namespace AutoSwagger\Docs\Tests\Unit\Helpers;

use App\Swagger\SchemaBuilders\DataWrapper;
use AutoSwagger\Docs\Helpers\AnnotationsHelper;
use AutoSwagger\Docs\Tests\SchemaTestCase;

class AnnotationsHelperTest extends SchemaTestCase
{
    private AnnotationsHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new AnnotationsHelper();
        config(['swagger.schema_builders' => ['D' => DataWrapper::class]]);
    }

    // --- plain array notation (SchemaName[]) ---

    public function test_plain_array_notation_returns_array_of_schemas(): void
    {
        [$arrayOfSchemas, $schemaBuilt] = $this->helper->parsedSchemas('FlightSearchResult[]');

        $this->assertNotNull($arrayOfSchemas);
        $this->assertNull($schemaBuilt);
        $this->assertSame('array', $arrayOfSchemas['type']);
        $this->assertSame('#/components/schemas/FlightSearchResult', $arrayOfSchemas['items']['$ref']);
    }

    public function test_plain_ref_returns_neither_array_nor_built_schema(): void
    {
        [$arrayOfSchemas, $schemaBuilt] = $this->helper->parsedSchemas('FlightSearchResult');

        $this->assertNull($arrayOfSchemas);
        $this->assertNull($schemaBuilt);
    }

    // --- schema builder without array notation: D(SchemaName) ---

    public function test_schema_builder_without_array_returns_built_schema_directly(): void
    {
        [$arrayOfSchemas, $schemaBuilt] = $this->helper->parsedSchemas('D(FlightSearchResult)');

        $this->assertNull($arrayOfSchemas);
        $this->assertNotNull($schemaBuilt);
        $this->assertSame('object', $schemaBuilt['type']);
        $this->assertSame('#/components/schemas/FlightSearchResult', $schemaBuilt['properties']['data']['$ref']);
    }

    // --- schema builder with array notation: D(SchemaName[]) ---

    public function test_schema_builder_with_array_notation_expands_ref_inside_builder_result_to_array(): void
    {
        [$arrayOfSchemas, $schemaBuilt] = $this->helper->parsedSchemas('D(FlightSearchResult[])');

        $this->assertNull($arrayOfSchemas);
        $this->assertNotNull($schemaBuilt);
        // The outer wrapper shape is preserved (object with data property)
        $this->assertSame('object', $schemaBuilt['type']);
        // The inner ref is expanded to an array schema
        $this->assertSame('array', $schemaBuilt['properties']['data']['type']);
        $this->assertSame('#/components/schemas/FlightSearchResult', $schemaBuilt['properties']['data']['items']['$ref']);
    }

    public function test_schema_builder_with_array_does_not_include_brackets_in_ref(): void
    {
        [$arrayOfSchemas, $schemaBuilt] = $this->helper->parsedSchemas('D(FlightSearchResult[])');

        $ref = $schemaBuilt['properties']['data']['items']['$ref'];
        $this->assertSame('#/components/schemas/FlightSearchResult', $ref);
        $this->assertStringNotContainsString('[]', $ref);
    }
}
