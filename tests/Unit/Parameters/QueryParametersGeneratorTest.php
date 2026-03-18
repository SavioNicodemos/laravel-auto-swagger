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
}
