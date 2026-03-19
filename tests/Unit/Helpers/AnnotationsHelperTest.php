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

    public function test_schema_builder_with_array_notation_wraps_built_schema_in_array(): void
    {
        [$arrayOfSchemas, $schemaBuilt] = $this->helper->parsedSchemas('D(FlightSearchResult[])');

        $this->assertNull($arrayOfSchemas);
        $this->assertNotNull($schemaBuilt);
        $this->assertSame('array', $schemaBuilt['type']);
        $this->assertSame('object', $schemaBuilt['items']['type']);
        $this->assertSame('#/components/schemas/FlightSearchResult', $schemaBuilt['items']['properties']['data']['$ref']);
    }

    public function test_schema_builder_with_array_strips_brackets_from_ref_passed_to_builder(): void
    {
        [$arrayOfSchemas, $schemaBuilt] = $this->helper->parsedSchemas('D(FlightSearchResult[])');

        $ref = $schemaBuilt['items']['properties']['data']['$ref'];
        $this->assertSame('#/components/schemas/FlightSearchResult', $ref);
        $this->assertStringNotContainsString('[]', $ref);
    }
}
