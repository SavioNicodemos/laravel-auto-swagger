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
