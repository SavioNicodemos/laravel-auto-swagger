<?php

namespace AutoSwagger\Docs\Tests\Feature\Definitions;

use AutoSwagger\Docs\Definitions\DefinitionGenerator;
use AutoSwagger\Docs\Tests\SchemaTestCase;

class DefinitionGeneratorTest extends SchemaTestCase
{
    private array $schemas;
    private array $flightSearchResult;
    private array $flightSegment;

    protected function setUp(): void
    {
        parent::setUp();

        $generator = new DefinitionGenerator([], [app_path('Swagger/Schemas')]);
        $this->schemas = (array) $generator->generateSchemas();
        $this->flightSearchResult = (array) $this->schemas['FlightSearchResult']['properties'];
        $this->flightSegment = (array) $this->schemas['FlightSegment']['properties'];
    }

    // --- plain ref (no extras) → bare $ref ---

    public function test_plain_ref_produces_bare_ref(): void
    {
        $links = $this->flightSearchResult['links'];

        $this->assertArrayHasKey('$ref', $links);
        $this->assertSame('#/components/schemas/FlightLinks', $links['$ref']);
        $this->assertArrayNotHasKey('allOf', $links);
        $this->assertArrayNotHasKey('nullable', $links);
    }

    // --- ref + nullable → allOf with nullable preserved ---

    public function test_ref_with_nullable_produces_allOf_and_preserves_nullable(): void
    {
        $flightInfo = $this->flightSearchResult['flight_info'];

        $this->assertTrue($flightInfo['nullable']);
        $this->assertArrayHasKey('allOf', $flightInfo);
        $this->assertSame('#/components/schemas/FlightDetails', $flightInfo['allOf'][0]['$ref']);
        $this->assertArrayNotHasKey('$ref', $flightInfo);
    }

    public function test_ref_with_nullable_does_not_include_type(): void
    {
        $flightInfo = $this->flightSearchResult['flight_info'];

        $this->assertArrayNotHasKey('type', $flightInfo);
    }

    // --- ref + nullable + description → allOf with both preserved ---

    public function test_ref_with_nullable_and_description_produces_allOf_and_preserves_both(): void
    {
        $alt = $this->flightSearchResult['alternative_flight'];

        $this->assertTrue($alt['nullable']);
        $this->assertSame('Alternative flight option', $alt['description']);
        $this->assertArrayHasKey('allOf', $alt);
        $this->assertSame('#/components/schemas/FlightDetails', $alt['allOf'][0]['$ref']);
        $this->assertArrayNotHasKey('$ref', $alt);
    }

    // --- enum ---

    public function test_enum_values_are_set_on_property(): void
    {
        $cabinClass = $this->flightSegment['cabin_class'];

        $this->assertSame(['economy', 'business', 'first'], $cabinClass['enum']);
    }

    public function test_enum_preserves_auto_detected_type_and_example(): void
    {
        $cabinClass = $this->flightSegment['cabin_class'];

        $this->assertSame('string', $cabinClass['type']);
        $this->assertSame('economy', $cabinClass['example']);
    }

    public function test_enum_without_raw_produces_same_output_as_raw_escape_hatch(): void
    {
        $cabinClass = $this->flightSegment['cabin_class'];

        $this->assertArrayNotHasKey('raw', $cabinClass);
        $this->assertSame('string', $cabinClass['type']);
        $this->assertSame(['economy', 'business', 'first'], $cabinClass['enum']);
        $this->assertSame('economy', $cabinClass['example']);
    }

    // --- deprecated ---

    public function test_deprecated_flag_is_set_on_property(): void
    {
        $this->assertTrue($this->flightSegment['class_of_service']['deprecated']);
    }

    public function test_deprecated_preserves_other_fields(): void
    {
        $prop = $this->flightSegment['class_of_service'];

        $this->assertSame('string', $prop['type']);
        $this->assertSame('Use cabin_class instead', $prop['description']);
    }

    // --- ref[] (array) → type + items, no allOf ---

    public function test_array_ref_produces_type_array_with_items_ref(): void
    {
        $segments = $this->flightSearchResult['segments'];

        $this->assertSame('array', $segments['type']);
        $this->assertSame('#/components/schemas/FlightSegment', $segments['items']['$ref']);
        $this->assertArrayNotHasKey('allOf', $segments);
        $this->assertArrayNotHasKey('$ref', $segments);
    }
}
