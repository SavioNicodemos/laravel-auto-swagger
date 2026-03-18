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
}
