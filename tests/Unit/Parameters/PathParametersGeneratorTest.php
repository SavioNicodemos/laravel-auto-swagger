<?php

namespace AutoSwagger\Docs\Tests\Unit\Parameters;

use AutoSwagger\Docs\Parameters\PathParametersGenerator;
use AutoSwagger\Docs\Tests\TestCase;

class PathParametersGeneratorTest extends TestCase
{
    public function test_it_returns_path_as_parameter_location(): void
    {
        $generator = new PathParametersGenerator('/api/users/{id}');

        $this->assertSame('path', $generator->getParameterLocation());
    }

    public function test_it_returns_empty_array_for_uri_with_no_placeholders(): void
    {
        $generator = new PathParametersGenerator('/api/users');

        $this->assertSame([], $generator->getParameters());
    }

    public function test_it_extracts_single_path_parameter(): void
    {
        $generator = new PathParametersGenerator('/api/users/{id}');
        $params    = $generator->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]['name']);
        $this->assertSame('path', $params[0]['in']);
        $this->assertTrue($params[0]['required']);
        $this->assertSame('string', $params[0]['schema']['type']);
    }

    public function test_it_extracts_multiple_path_parameters(): void
    {
        $generator = new PathParametersGenerator('/api/users/{userId}/posts/{postId}');
        $params    = $generator->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('userId', $params[0]['name']);
        $this->assertSame('postId', $params[1]['name']);
    }

    public function test_it_strips_optional_marker_from_parameter_name(): void
    {
        $generator = new PathParametersGenerator('/api/users/{id?}');
        $params    = $generator->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]['name']);
    }

    public function test_all_path_parameters_are_marked_required(): void
    {
        $generator = new PathParametersGenerator('/api/a/{x}/b/{y}');

        foreach ($generator->getParameters() as $param) {
            $this->assertTrue($param['required']);
        }
    }

    public function test_each_path_parameter_has_empty_description(): void
    {
        $generator = new PathParametersGenerator('/api/users/{id}');
        $params    = $generator->getParameters();

        $this->assertArrayHasKey('description', $params[0]);
        $this->assertSame('', $params[0]['description']);
    }

    public function test_optional_path_parameter_is_still_marked_required(): void
    {
        $generator = new PathParametersGenerator('/api/users/{id?}');
        $params    = $generator->getParameters();

        $this->assertTrue($params[0]['required']);
    }

    public function test_mixed_optional_and_non_optional_parameters_are_both_required(): void
    {
        $generator = new PathParametersGenerator('/api/users/{userId}/posts/{postId?}');
        $params    = $generator->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('userId', $params[0]['name']);
        $this->assertSame('postId', $params[1]['name']);
        $this->assertTrue($params[0]['required']);
        $this->assertTrue($params[1]['required']);
    }

    public function test_each_path_parameter_schema_type_is_string(): void
    {
        $generator = new PathParametersGenerator('/api/users/{id}/orders/{orderId}');
        $params    = $generator->getParameters();

        foreach ($params as $param) {
            $this->assertSame('string', $param['schema']['type']);
        }
    }

    public function test_uri_with_only_a_path_parameter(): void
    {
        $generator = new PathParametersGenerator('/{id}');
        $params    = $generator->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]['name']);
    }
}
