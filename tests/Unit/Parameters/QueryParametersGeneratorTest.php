<?php

namespace AutoSwagger\Docs\Tests\Unit\Parameters;

use AutoSwagger\Docs\Parameters\QueryParametersGenerator;
use AutoSwagger\Docs\Tests\TestCase;

class QueryParametersGeneratorTest extends TestCase
{
    public function test_it_returns_query_as_parameter_location(): void
    {
        $this->assertSame('query', (new QueryParametersGenerator([]))->getParameterLocation());
    }

    public function test_it_returns_empty_array_for_empty_rules(): void
    {
        $this->assertSame([], (new QueryParametersGenerator([]))->getParameters());
    }

    public function test_it_generates_required_string_parameter(): void
    {
        $params = (new QueryParametersGenerator(['search' => 'required|string']))->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('search', $params[0]['name']);
        $this->assertSame('query', $params[0]['in']);
        $this->assertTrue($params[0]['required']);
        $this->assertSame('string', $params[0]['schema']['type']);
    }

    public function test_it_marks_optional_parameter_as_not_required(): void
    {
        $params = (new QueryParametersGenerator(['page' => 'integer']))->getParameters();

        $this->assertFalse($params[0]['required']);
    }

    public function test_it_detects_integer_type(): void
    {
        $params = (new QueryParametersGenerator(['page' => 'integer']))->getParameters();

        $this->assertSame('integer', $params[0]['schema']['type']);
    }

    public function test_it_detects_numeric_type(): void
    {
        $params = (new QueryParametersGenerator(['amount' => 'numeric']))->getParameters();

        $this->assertSame('number', $params[0]['schema']['type']);
    }

    public function test_it_detects_boolean_type(): void
    {
        $params = (new QueryParametersGenerator(['active' => 'boolean']))->getParameters();

        $this->assertSame('boolean', $params[0]['schema']['type']);
    }

    public function test_it_generates_enum_from_in_rule(): void
    {
        $params = (new QueryParametersGenerator(['status' => 'in:active,inactive,pending']))->getParameters();

        $this->assertSame(['active', 'inactive', 'pending'], $params[0]['schema']['enum']);
    }

    public function test_it_formats_nested_parameter_with_bracket_notation(): void
    {
        $params = (new QueryParametersGenerator(['filter.name' => 'string']))->getParameters();

        $this->assertSame('filter[name]', $params[0]['name']);
    }

    public function test_it_applies_min_and_max_for_integer_type(): void
    {
        // QueryParametersGenerator reads swagger_min/swagger_max custom rules, not native min/max
        $params = (new QueryParametersGenerator(['age' => 'integer|swagger_min:18|swagger_max:99']))->getParameters();

        $this->assertSame(18, $params[0]['schema']['minimum']);
        $this->assertSame(99, $params[0]['schema']['maximum']);
    }

    public function test_it_accepts_rules_as_array(): void
    {
        $params = (new QueryParametersGenerator(['active' => ['required', 'boolean']]))->getParameters();

        $this->assertSame('boolean', $params[0]['schema']['type']);
        $this->assertTrue($params[0]['required']);
    }

    public function test_it_applies_default_value(): void
    {
        $params = (new QueryParametersGenerator(['per_page' => 'integer|swagger_default:15']))->getParameters();

        $this->assertSame(15, $params[0]['schema']['default']);
    }

    public function test_it_applies_example_value(): void
    {
        $params = (new QueryParametersGenerator(['name' => 'string|swagger_example:John']))->getParameters();

        $this->assertSame('John', $params[0]['schema']['example']);
    }

    // --- description ---

    public function test_description_defaults_to_empty_string(): void
    {
        $params = (new QueryParametersGenerator(['page' => 'integer']))->getParameters();

        $this->assertArrayHasKey('description', $params[0]);
        $this->assertSame('', $params[0]['description']);
    }

    public function test_it_applies_swagger_description(): void
    {
        $params = (new QueryParametersGenerator(['search' => 'string|swagger_description:Filter by name']))->getParameters();

        $this->assertSame('Filter by name', $params[0]['description']);
    }

    // --- swagger_required ---

    public function test_swagger_required_true_marks_query_param_required(): void
    {
        $params = (new QueryParametersGenerator(['field' => 'string|swagger_required:true']))->getParameters();

        $this->assertTrue($params[0]['required']);
    }

    public function test_bare_swagger_required_marks_query_param_required(): void
    {
        $params = (new QueryParametersGenerator(['field' => ['string', 'swagger_required']]))->getParameters();

        $this->assertTrue($params[0]['required']);
    }

    public function test_swagger_required_false_overrides_required_rule(): void
    {
        $params = (new QueryParametersGenerator(['field' => ['required', 'string', 'swagger_required:false']]))->getParameters();

        $this->assertFalse($params[0]['required']);
    }

    // --- array parameters ---

    public function test_array_type_parameter_appends_bracket_suffix_to_name(): void
    {
        $params = (new QueryParametersGenerator(['tags' => 'array']))->getParameters();

        $this->assertSame('tags[]', $params[0]['name']);
    }

    public function test_array_type_parameter_has_items_inside_schema(): void
    {
        $params = (new QueryParametersGenerator(['tags' => 'array']))->getParameters();

        $this->assertSame('array', $params[0]['schema']['type']);
        $this->assertSame('string', $params[0]['schema']['items']['type']);
    }

    public function test_array_wildcard_child_sets_items_type_in_schema(): void
    {
        $params = (new QueryParametersGenerator([
            'ids'   => 'array',
            'ids.*' => 'integer',
        ]))->getParameters();

        $param = $params[0];
        $this->assertSame('ids[]', $param['name']);
        $this->assertSame('array', $param['schema']['type']);
        $this->assertSame('integer', $param['schema']['items']['type']);
    }

    public function test_array_wildcard_without_parent_rule_auto_creates_parameter(): void
    {
        $params = (new QueryParametersGenerator(['tag_ids.*' => 'integer']))->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('tag_ids', $params[0]['name']);
        $this->assertSame('integer', $params[0]['items']['type']);
    }

    // --- nested dot notation ---

    public function test_deeply_nested_dot_notation_converts_to_bracket_notation(): void
    {
        $params = (new QueryParametersGenerator(['filter.status.code' => 'string']))->getParameters();

        $this->assertSame('filter[status][code]', $params[0]['name']);
    }

    // --- object rules ---

    public function test_object_rules_in_array_do_not_crash_generation(): void
    {
        $objectRule = new class {
            public function __toString(): string { return 'custom_rule'; }
        };

        $params = (new QueryParametersGenerator(['field' => ['required', 'string', $objectRule]]))->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('string', $params[0]['schema']['type']);
        $this->assertTrue($params[0]['required']);
    }

    // --- zero-value edge cases ---

    public function test_swagger_min_zero_sets_minimum(): void
    {
        $params = (new QueryParametersGenerator(['age' => 'integer|swagger_min:0']))->getParameters();

        $this->assertSame(0, $params[0]['schema']['minimum']);
    }

    public function test_swagger_max_zero_sets_maximum(): void
    {
        $params = (new QueryParametersGenerator(['offset' => 'integer|swagger_max:0']))->getParameters();

        $this->assertSame(0, $params[0]['schema']['maximum']);
    }

    public function test_swagger_default_zero_sets_default(): void
    {
        $params = (new QueryParametersGenerator(['page' => 'integer|swagger_default:0']))->getParameters();

        $this->assertSame(0, $params[0]['schema']['default']);
    }

    public function test_swagger_example_zero_sets_example(): void
    {
        $params = (new QueryParametersGenerator(['count' => 'integer|swagger_example:0']))->getParameters();

        $this->assertSame(0, $params[0]['schema']['example']);
    }
}
