<?php

namespace AutoSwagger\Docs\Tests\Unit\Helpers;

use App\Swagger\SchemaBuilders\DataWrapper;
use AutoSwagger\Docs\Exceptions\AnnotationException;
use AutoSwagger\Docs\Exceptions\SchemaBuilderNotFound;
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

    // --- toSwaggerSchemaPath ---

    public function test_to_swagger_schema_path_adds_components_prefix(): void
    {
        $this->assertSame(
            '#/components/schemas/FlightSegment',
            $this->helper->toSwaggerSchemaPath('FlightSegment')
        );
    }

    public function test_to_swagger_schema_path_leaves_already_prefixed_value_unchanged(): void
    {
        $path = '#/components/schemas/FlightSegment';

        $this->assertSame($path, $this->helper->toSwaggerSchemaPath($path));
    }

    // --- verifyAnnotationRefString ---

    public function test_verify_plain_name_returns_false_false(): void
    {
        [$isArray, $isBuilder] = $this->helper->verifyAnnotationRefString('FlightSearchResult');

        $this->assertFalse($isArray);
        $this->assertFalse($isBuilder);
    }

    public function test_verify_array_notation_returns_true_false(): void
    {
        [$isArray, $isBuilder] = $this->helper->verifyAnnotationRefString('FlightSearchResult[]');

        $this->assertTrue($isArray);
        $this->assertFalse($isBuilder);
    }

    public function test_verify_schema_builder_returns_false_true(): void
    {
        [$isArray, $isBuilder] = $this->helper->verifyAnnotationRefString('D(FlightSearchResult)');

        $this->assertFalse($isArray);
        $this->assertTrue($isBuilder);
    }

    public function test_verify_schema_builder_with_array_returns_false_true(): void
    {
        [$isArray, $isBuilder] = $this->helper->verifyAnnotationRefString('D(FlightSearchResult[])');

        $this->assertFalse($isArray);
        $this->assertTrue($isBuilder);
    }

    // --- getCommentProperties ---

    public function test_get_comment_properties_with_null_returns_empty_structure(): void
    {
        $result = $this->helper->getCommentProperties(null, 'Property');

        $this->assertSame('', $result['summary']);
        $this->assertSame([], $result['meta']);
        $this->assertFalse($result['deprecated']);
    }

    public function test_get_comment_properties_with_no_matching_tag_returns_empty_meta(): void
    {
        $comment = '/** Simple description without any tags */';

        $result = $this->helper->getCommentProperties($comment, 'Property');

        $this->assertSame([], $result['meta']);
    }

    // --- parseRawDocumentationTag ---

    public function test_parse_raw_documentation_tag_with_empty_string_returns_empty_array(): void
    {
        $tag = new class {
            public function __toString(): string { return ''; }
        };

        $result = $this->helper->parseRawDocumentationTag($tag);

        $this->assertSame([], $result);
    }

    public function test_parse_raw_documentation_tag_with_invalid_json_throws_annotation_exception(): void
    {
        $this->expectException(AnnotationException::class);

        $tag = new class {
            public function __toString(): string { return '({invalid json})'; }
        };

        $this->helper->parseRawDocumentationTag($tag);
    }

    // --- schema builder not found ---

    public function test_unknown_schema_builder_throws_schema_builder_not_found(): void
    {
        $this->expectException(SchemaBuilderNotFound::class);

        $this->helper->parsedSchemas('X(FlightSearchResult)');
    }
}
