<?php

namespace AutoSwagger\Docs\Tests\Unit\Parameters;

use AutoSwagger\Docs\Parameters\BodyParametersGenerator;
use AutoSwagger\Docs\Tests\TestCase;

class BodyParametersGeneratorTest extends TestCase
{
    private function schema(array $rules): array
    {
        return (new BodyParametersGenerator($rules))
            ->getParameters()['content']['application/json']['schema'];
    }

    public function test_it_returns_body_as_parameter_location(): void
    {
        $this->assertSame('body', (new BodyParametersGenerator([]))->getParameterLocation());
    }

    public function test_it_returns_json_content_type_by_default(): void
    {
        $params = (new BodyParametersGenerator(['name' => 'string']))->getParameters();

        $this->assertArrayHasKey('application/json', $params['content']);
    }

    public function test_it_returns_multipart_content_type_for_file_upload(): void
    {
        $params = (new BodyParametersGenerator(['avatar' => 'required|file']))->getParameters();

        $this->assertArrayHasKey('multipart/form-data', $params['content']);
    }

    public function test_it_returns_multipart_content_type_for_image_upload(): void
    {
        $params = (new BodyParametersGenerator(['photo' => 'required|image']))->getParameters();

        $this->assertArrayHasKey('multipart/form-data', $params['content']);
    }

    public function test_it_generates_string_property(): void
    {
        $schema = $this->schema(['name' => 'string']);

        $this->assertSame('string', $schema['properties']['name']['type']);
    }

    public function test_it_generates_integer_property(): void
    {
        $schema = $this->schema(['age' => 'integer']);

        $this->assertSame('integer', $schema['properties']['age']['type']);
    }

    public function test_it_generates_boolean_property(): void
    {
        $schema = $this->schema(['active' => 'boolean']);

        $this->assertSame('boolean', $schema['properties']['active']['type']);
    }

    public function test_it_marks_required_fields(): void
    {
        $schema = $this->schema(['name' => 'required|string', 'bio' => 'string']);

        $this->assertContains('name', $schema['required']);
        $this->assertNotContains('bio', $schema['required'] ?? []);
    }

    public function test_it_omits_required_key_when_no_required_fields(): void
    {
        $schema = $this->schema(['name' => 'string']);

        $this->assertArrayNotHasKey('required', $schema);
    }

    public function test_it_marks_nullable_property(): void
    {
        $schema = $this->schema(['bio' => 'nullable|string']);

        $this->assertTrue($schema['properties']['bio']['nullable']);
    }

    public function test_it_generates_enum_values_from_in_rule(): void
    {
        $schema = $this->schema(['status' => 'required|in:active,inactive']);

        $this->assertSame(['active', 'inactive'], $schema['properties']['status']['enum']);
    }

    public function test_it_generates_nested_object_properties(): void
    {
        $schema = $this->schema([
            'address'        => 'required|array',
            'address.street' => 'required|string',
            'address.city'   => 'required|string',
        ]);

        $this->assertSame('object', $schema['properties']['address']['type']);
        $this->assertArrayHasKey('street', $schema['properties']['address']['properties']);
        $this->assertArrayHasKey('city', $schema['properties']['address']['properties']);
    }

    public function test_it_generates_array_of_primitives(): void
    {
        $schema = $this->schema([
            'tags'   => 'array',
            'tags.*' => 'string',
        ]);

        $this->assertSame('array', $schema['properties']['tags']['type']);
        $this->assertSame('string', $schema['properties']['tags']['items']['type']);
    }

    public function test_it_accepts_rules_as_array(): void
    {
        $schema = $this->schema(['name' => ['required', 'string', 'max:255']]);

        $this->assertSame('string', $schema['properties']['name']['type']);
        $this->assertContains('name', $schema['required']);
    }

    // --- swagger_required ---

    public function test_it_respects_swagger_required_true(): void
    {
        $schema = $this->schema(['field' => 'swagger_required:true']);

        $this->assertContains('field', $schema['required']);
    }

    public function test_it_respects_swagger_required_false(): void
    {
        $schema = $this->schema(['field' => 'swagger_required:false']);

        $this->assertArrayNotHasKey('required', $schema);
    }

    public function test_it_respects_bare_swagger_required_without_colon(): void
    {
        $schema = $this->schema(['field' => ['array', 'swagger_required']]);

        $this->assertContains('field', $schema['required']);
    }

    public function test_swagger_required_overrides_missing_required_rule(): void
    {
        // field has no 'required' rule but swagger_required forces it
        $schema = $this->schema(['field' => ['string', 'swagger_required:true']]);

        $this->assertContains('field', $schema['required']);
    }

    public function test_swagger_required_false_overrides_required_rule(): void
    {
        // field has 'required' but swagger_required:false forces it out
        $schema = $this->schema(['field' => ['required', 'string', 'swagger_required:false']]);

        $this->assertArrayNotHasKey('required', $schema);
    }

    public function test_swagger_required_does_not_crash_with_object_rules(): void
    {
        $objectRule = new class {
            public function __toString(): string { return 'custom_rule'; }
        };

        $schema = $this->schema(['field' => ['required', 'string', $objectRule]]);

        $this->assertContains('field', $schema['required']);
    }

    public function test_it_applies_swagger_description(): void
    {
        $schema = $this->schema(['name' => 'string|swagger_description:The user full name']);

        $this->assertSame('The user full name', $schema['properties']['name']['description']);
    }

    public function test_it_applies_swagger_example(): void
    {
        $schema = $this->schema(['email' => 'string|swagger_example:user@example.com']);

        $this->assertSame('user@example.com', $schema['properties']['email']['example']);
    }

    public function test_it_applies_swagger_default(): void
    {
        $schema = $this->schema(['per_page' => 'integer|swagger_default:20']);

        $this->assertSame(20, $schema['properties']['per_page']['default']);
    }

    public function test_it_applies_min_length_for_string(): void
    {
        $schema = $this->schema(['password' => 'string|min:8']);

        $this->assertSame(8, $schema['properties']['password']['minLength']);
    }

    public function test_it_applies_max_length_for_string(): void
    {
        $schema = $this->schema(['name' => 'string|max:255']);

        $this->assertSame(255, $schema['properties']['name']['maxLength']);
    }

    public function test_it_sets_email_format(): void
    {
        $schema = $this->schema(['email' => 'string|email']);

        $this->assertSame('email', $schema['properties']['email']['format']);
    }

    public function test_it_sets_uuid_format(): void
    {
        $schema = $this->schema(['id' => 'string|uuid']);

        $this->assertSame('uuid', $schema['properties']['id']['format']);
    }

    public function test_it_sets_minimum_for_integer(): void
    {
        $schema = $this->schema(['age' => 'integer|min:0']);

        $this->assertSame(0, $schema['properties']['age']['minimum']);
    }

    public function test_it_sets_maximum_for_integer(): void
    {
        $schema = $this->schema(['age' => 'integer|max:120']);

        $this->assertSame(120, $schema['properties']['age']['maximum']);
    }

    // --- numeric type ---

    public function test_it_generates_number_type_for_numeric_rule(): void
    {
        $schema = $this->schema(['price' => 'numeric']);

        $this->assertSame('number', $schema['properties']['price']['type']);
    }

    // --- integer constraints ---

    public function test_it_applies_multiple_of_for_integer(): void
    {
        $schema = $this->schema(['quantity' => 'integer|multiple_of:5']);

        $this->assertSame(5, $schema['properties']['quantity']['multipleOf']);
    }

    public function test_it_applies_swagger_min_override_for_integer(): void
    {
        $schema = $this->schema(['score' => 'integer|swagger_min:10']);

        $this->assertSame('10', $schema['properties']['score']['minimum']);
    }

    public function test_it_applies_swagger_max_override_for_integer(): void
    {
        $schema = $this->schema(['score' => 'integer|swagger_max:100']);

        $this->assertSame('100', $schema['properties']['score']['maximum']);
    }

    // --- string constraints ---

    public function test_it_applies_swagger_min_override_for_string(): void
    {
        $schema = $this->schema(['code' => 'string|swagger_min:3']);

        $this->assertSame('3', $schema['properties']['code']['minLength']);
    }

    public function test_it_applies_swagger_max_override_for_string(): void
    {
        $schema = $this->schema(['code' => 'string|swagger_max:10']);

        $this->assertSame('10', $schema['properties']['code']['maxLength']);
    }

    // --- string formats ---

    public function test_it_sets_uri_format_for_url_rule(): void
    {
        $schema = $this->schema(['website' => 'string|url']);

        $this->assertSame('uri', $schema['properties']['website']['format']);
    }

    public function test_it_sets_ip_format(): void
    {
        $schema = $this->schema(['address' => 'string|ip']);

        $this->assertSame('ip', $schema['properties']['address']['format']);
    }

    public function test_it_sets_ipv4_format(): void
    {
        $schema = $this->schema(['address' => 'string|ipv4']);

        $this->assertSame('ipv4', $schema['properties']['address']['format']);
    }

    public function test_it_sets_ipv6_format(): void
    {
        $schema = $this->schema(['address' => 'string|ipv6']);

        $this->assertSame('ipv6', $schema['properties']['address']['format']);
    }

    public function test_it_sets_json_format(): void
    {
        $schema = $this->schema(['payload' => 'string|json']);

        $this->assertSame('json', $schema['properties']['payload']['format']);
    }

    public function test_it_sets_password_format(): void
    {
        $schema = $this->schema(['secret' => 'string|password']);

        $this->assertSame('password', $schema['properties']['secret']['format']);
    }

    public function test_it_sets_date_format_for_date_rule(): void
    {
        $schema = $this->schema(['dob' => 'string|date']);

        $this->assertSame('date', $schema['properties']['dob']['format']);
    }

    public function test_it_sets_date_format_for_after_rule(): void
    {
        $schema = $this->schema(['start_date' => 'string|after:today']);

        $this->assertSame('date', $schema['properties']['start_date']['format']);
    }

    public function test_it_sets_date_format_for_before_rule(): void
    {
        $schema = $this->schema(['end_date' => 'string|before:2030-01-01']);

        $this->assertSame('date', $schema['properties']['end_date']['format']);
    }

    public function test_it_sets_pattern_for_regex_rule(): void
    {
        $schema = $this->schema(['code' => 'string|regex:/^[A-Z]{3}$/']);

        $this->assertSame('/^[A-Z]{3}$/', $schema['properties']['code']['pattern']);
    }

    // --- array defaults ---

    public function test_array_with_no_child_rule_defaults_items_to_string(): void
    {
        $schema = $this->schema(['tags' => 'array']);

        $this->assertSame('array', $schema['properties']['tags']['type']);
        $this->assertSame('string', $schema['properties']['tags']['items']['type']);
    }

    // --- swagger_hidden ---

    public function test_swagger_hidden_excludes_field_from_properties(): void
    {
        $schema = $this->schema([
            'name'       => 'required|string',
            'secret_key' => 'required|string|swagger_hidden',
        ]);

        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayNotHasKey('secret_key', $schema['properties']);
    }

    public function test_swagger_hidden_field_is_not_in_required_list(): void
    {
        $schema = $this->schema([
            'name'       => 'required|string',
            'secret_key' => 'required|string|swagger_hidden',
        ]);

        $this->assertContains('name', $schema['required']);
        $this->assertNotContains('secret_key', $schema['required'] ?? []);
    }

    public function test_swagger_hidden_on_parent_excludes_children_too(): void
    {
        $schema = $this->schema([
            'meta'        => 'array|swagger_hidden',
            'meta.source' => 'string',
            'name'        => 'required|string',
        ]);

        $this->assertArrayNotHasKey('meta', $schema['properties']);
        $this->assertArrayHasKey('name', $schema['properties']);
    }

    // --- nested structures ---

    public function test_it_generates_deeply_nested_object(): void
    {
        $schema = $this->schema([
            'order'                  => 'required|array',
            'order.address'          => 'required|array',
            'order.address.street'   => 'required|string',
        ]);

        $street = $schema['properties']['order']['properties']['address']['properties']['street'] ?? null;

        $this->assertNotNull($street);
        $this->assertSame('string', $street['type']);
    }

    public function test_required_fields_inside_nested_object_are_tracked(): void
    {
        $schema = $this->schema([
            'contact'            => 'required|array',
            'contact.first_name' => 'required|string',
            'contact.last_name'  => 'required|string',
            'contact.bio'        => 'string',
        ]);

        $required = $schema['properties']['contact']['required'] ?? [];

        $this->assertContains('first_name', $required);
        $this->assertContains('last_name', $required);
        $this->assertNotContains('bio', $required);
    }

    public function test_swagger_description_is_applied_to_nested_child(): void
    {
        $schema = $this->schema([
            'contact'       => 'required|array',
            'contact.email' => ['required', 'email', 'swagger_description:Primary contact email'],
        ]);

        $description = $schema['properties']['contact']['properties']['email']['description'] ?? null;

        $this->assertSame('Primary contact email', $description);
    }

    // --- nullable does not leak to intermediate array items wrapper ---

    public function test_nullable_child_does_not_make_items_wrapper_nullable(): void
    {
        $schema = $this->schema([
            'passengers'           => ['required', 'array'],
            'passengers.*.guest_id' => ['nullable', 'integer'],
        ]);

        $items = $schema['properties']['passengers']['items'];

        $this->assertArrayNotHasKey('nullable', $items);
    }

    public function test_nullable_is_still_applied_to_the_leaf_field(): void
    {
        $schema = $this->schema([
            'passengers'            => ['required', 'array'],
            'passengers.*.guest_id' => ['nullable', 'integer'],
        ]);

        $guestId = $schema['properties']['passengers']['items']['properties']['guest_id'];

        $this->assertTrue($guestId['nullable']);
    }

    public function test_nullable_on_the_array_itself_is_preserved(): void
    {
        $schema = $this->schema([
            'passengers' => ['nullable', 'array'],
        ]);

        $this->assertTrue($schema['properties']['passengers']['nullable']);
    }

    public function test_multiple_nullable_children_do_not_pollute_items_wrapper(): void
    {
        $schema = $this->schema([
            'passengers'            => ['required', 'array'],
            'passengers.*.guest_id' => ['nullable', 'integer'],
            'passengers.*.child_id' => ['nullable', 'integer'],
        ]);

        $items = $schema['properties']['passengers']['items'];

        $this->assertArrayNotHasKey('nullable', $items);
        $this->assertTrue($items['properties']['guest_id']['nullable']);
        $this->assertTrue($items['properties']['child_id']['nullable']);
    }
}
