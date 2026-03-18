<?php

namespace AutoSwagger\Docs\Tests\Unit\DataObjects;

use AutoSwagger\Docs\DataObjects\Middleware;
use AutoSwagger\Docs\Tests\TestCase;

class MiddlewareTest extends TestCase
{
    public function test_it_parses_simple_middleware(): void
    {
        $middleware = new Middleware('auth');

        $this->assertSame('auth', $middleware->name());
        $this->assertSame([], $middleware->parameters());
        $this->assertSame('auth', (string) $middleware);
    }

    public function test_it_parses_middleware_with_single_parameter(): void
    {
        $middleware = new Middleware('auth:api');

        $this->assertSame('auth', $middleware->name());
        $this->assertSame(['api'], $middleware->parameters());
        $this->assertSame('auth:api', (string) $middleware);
    }

    public function test_it_parses_middleware_with_multiple_parameters(): void
    {
        $middleware = new Middleware('throttle:60,1');

        $this->assertSame('throttle', $middleware->name());
        $this->assertSame(['60', '1'], $middleware->parameters());
        $this->assertSame('throttle:60,1', (string) $middleware);
    }

    public function test_it_parses_middleware_with_colon_in_second_parameter(): void
    {
        $middleware = new Middleware('auth:sanctum');

        $this->assertSame('auth', $middleware->name());
        $this->assertSame(['sanctum'], $middleware->parameters());
    }

    public function test_to_string_returns_original_middleware_string(): void
    {
        $raw = 'role:admin,superadmin';
        $middleware = new Middleware($raw);

        $this->assertSame($raw, (string) $middleware);
    }
}
