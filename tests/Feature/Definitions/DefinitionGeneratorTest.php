<?php

namespace AutoSwagger\Docs\Tests\Feature\Definitions;

use AutoSwagger\Docs\Definitions\DefinitionGenerator;
use AutoSwagger\Docs\Tests\SchemaTestCase;

class DefinitionGeneratorTest extends SchemaTestCase
{
    private array $schemas;
    private array $flightSearchResult;
    private array $flightSegment;
    private array $flightDetails;

    protected function setUp(): void
    {
        parent::setUp();

        $generator = new DefinitionGenerator([], [app_path('Swagger/Schemas')]);
        $this->schemas = (array) $generator->generateSchemas();
        $this->flightSearchResult = (array) $this->schemas['FlightSearchResult']['properties'];
        $this->flightSegment = (array) $this->schemas['FlightSegment']['properties'];
        $this->flightDetails = (array) $this->schemas['FlightDetails']['properties'];
    }

    // --- schema structure ---

    public function test_schema_has_type_object(): void
    {
        $this->assertSame('object', $this->schemas['FlightSearchResult']['type']);
    }

    public function test_schema_required_array_is_present_and_correct(): void
    {
        $required = $this->schemas['FlightSearchResult']['required'] ?? [];

        $this->assertContains('flight_id', $required);
        $this->assertContains('flight_number', $required);
        $this->assertContains('segments', $required);
        $this->assertContains('links', $required);
    }

    public function test_schema_required_does_not_include_optional_properties(): void
    {
        $required = $this->schemas['FlightSearchResult']['required'] ?? [];

        $this->assertNotContains('session_token', $required);
        $this->assertNotContains('currency_code', $required);
        $this->assertNotContains('connecting_flight', $required);
    }

    public function test_schema_without_required_annotation_omits_required_key(): void
    {
        $this->assertArrayNotHasKey('required', $this->schemas['FlightStatus']);
    }

    // --- PHP type → swagger type mapping ---

    public function test_php_int_maps_to_swagger_integer(): void
    {
        $this->assertSame('integer', $this->flightDetails['stops']['type']);
    }

    public function test_php_bool_maps_to_swagger_boolean(): void
    {
        $this->assertSame('boolean', $this->flightSearchResult['is_available']['type']);
    }

    public function test_php_string_maps_to_swagger_string(): void
    {
        $this->assertSame('string', $this->flightDetails['origin_code']['type']);
    }

    // --- static property value → auto example ---

    public function test_static_integer_value_is_auto_populated_as_example(): void
    {
        $this->assertSame(0, $this->flightDetails['stops']['example']);
    }

    public function test_static_string_value_is_auto_populated_as_example(): void
    {
        $this->assertSame('GRU', $this->flightDetails['origin_code']['example']);
    }

    public function test_static_bool_value_is_auto_populated_as_example(): void
    {
        $this->assertSame(true, $this->flightSearchResult['is_available']['example']);
    }

    // --- nullable PHP type auto-detection ---

    public function test_nullable_php_type_sets_nullable_true(): void
    {
        $this->assertTrue($this->flightSearchResult['session_token']['nullable']);
    }

    public function test_non_nullable_php_type_does_not_set_nullable(): void
    {
        $this->assertArrayNotHasKey('nullable', $this->flightDetails['stops']);
    }

    // --- @Property format ---

    public function test_property_format_annotation_is_applied(): void
    {
        $this->assertSame('date', $this->flightSegment['scheduled_date']['format']);
    }

    public function test_property_format_preserves_base_type(): void
    {
        $this->assertSame('string', $this->flightSegment['scheduled_date']['type']);
    }

    // --- @Property example override ---

    public function test_property_example_annotation_overrides_static_value(): void
    {
        // static value is 'XX', @Property example is 'LA'
        $this->assertSame('LA', $this->flightSegment['carrier_code']['example']);
    }

    // --- @Property type override ---

    public function test_property_type_annotation_overrides_php_inferred_type(): void
    {
        // PHP type is int, @Property says "type": "string"
        $this->assertSame('string', $this->flightSegment['seat_number']['type']);
    }

    // --- @Property nullable on non-nullable PHP property ---

    public function test_property_nullable_annotation_sets_nullable_on_non_nullable_php_type(): void
    {
        $this->assertTrue($this->flightSegment['booking_class']['nullable']);
    }

    // --- @Property nullable via PHP nullable type (no annotation) ---

    public function test_nullable_php_type_in_schema_sets_nullable(): void
    {
        $this->assertTrue($this->flightSegment['is_codeshare']['nullable']);
    }

    // --- @Property arrayOf ---

    public function test_property_array_of_sets_items_type(): void
    {
        $this->assertSame('array', $this->flightSegment['stop_numbers']['type']);
        $this->assertSame('integer', $this->flightSegment['stop_numbers']['items']['type']);
    }

    // --- @Property raw passthrough ---

    public function test_property_raw_returns_value_without_any_processing(): void
    {
        $raw = $this->flightSegment['booking_url'];

        $this->assertSame('string', $raw['type']);
        $this->assertSame('uri', $raw['format']);
        // raw skips createBaseData, so no description or nullable keys
        $this->assertArrayNotHasKey('description', $raw);
        $this->assertArrayNotHasKey('nullable', $raw);
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

    // --- ref + description only (no nullable) → allOf with description ---

    public function test_ref_with_description_only_produces_allOf_and_preserves_description(): void
    {
        $conn = $this->flightSearchResult['connecting_flight'];

        $this->assertSame('Connecting flight option', $conn['description']);
        $this->assertArrayHasKey('allOf', $conn);
        $this->assertSame('#/components/schemas/FlightDetails', $conn['allOf'][0]['$ref']);
        $this->assertArrayNotHasKey('$ref', $conn);
        $this->assertArrayNotHasKey('nullable', $conn);
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
