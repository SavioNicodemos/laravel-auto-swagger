<?php

namespace AutoSwagger\Docs\Tests\Feature\Helpers;

use AutoSwagger\Docs\DataObjects\Middleware;
use AutoSwagger\Docs\Exceptions\InvalidAuthenticationFlow;
use AutoSwagger\Docs\Exceptions\InvalidDefinitionException;
use AutoSwagger\Docs\Helpers\SwaggerSecurityHelper;
use AutoSwagger\Docs\Tests\TestCase;

class SwaggerSecurityHelperTest extends TestCase
{
    // --- getEndpoint ---

    public function test_get_endpoint_builds_full_url(): void
    {
        $url = SwaggerSecurityHelper::getEndpoint('/oauth/token', 'http://localhost');

        $this->assertSame('http://localhost/oauth/token', $url);
    }

    public function test_get_endpoint_strips_trailing_slash_from_host(): void
    {
        $url = SwaggerSecurityHelper::getEndpoint('/oauth/token', 'http://localhost/');

        $this->assertSame('http://localhost/oauth/token', $url);
    }

    public function test_get_endpoint_adds_http_scheme_when_missing(): void
    {
        $url = SwaggerSecurityHelper::getEndpoint('/oauth/token', 'localhost');

        $this->assertStringStartsWith('http://', $url);
        $this->assertStringEndsWith('/oauth/token', $url);
    }

    public function test_get_endpoint_preserves_https_scheme(): void
    {
        $url = SwaggerSecurityHelper::getEndpoint('/oauth/authorize', 'https://api.example.com');

        $this->assertStringStartsWith('https://', $url);
    }

    // --- isSecurityMiddleware ---

    public function test_is_security_middleware_returns_true_for_exact_match(): void
    {
        $middleware = new Middleware('auth:api');

        $this->assertTrue(
            SwaggerSecurityHelper::isSecurityMiddleware($middleware, ['auth:api', 'auth:sanctum'])
        );
    }

    public function test_is_security_middleware_returns_false_when_no_match(): void
    {
        $middleware = new Middleware('throttle:60,1');

        $this->assertFalse(
            SwaggerSecurityHelper::isSecurityMiddleware($middleware, ['auth:api', 'auth:sanctum'])
        );
    }

    public function test_is_security_middleware_returns_false_for_empty_list(): void
    {
        $middleware = new Middleware('auth:api');

        $this->assertFalse(
            SwaggerSecurityHelper::isSecurityMiddleware($middleware, [])
        );
    }

    // --- generateSecurityDefinitions ---

    public function test_generate_security_definitions_returns_bearer_auth_definition(): void
    {
        $definitions = SwaggerSecurityHelper::generateSecurityDefinitions(
            ['bearerAuth' => 'http'],
            'http://localhost'
        );

        $this->assertArrayHasKey('bearerAuth', $definitions);
        $this->assertSame('http', $definitions['bearerAuth']['type']);
        $this->assertSame('bearer', $definitions['bearerAuth']['scheme']);
        $this->assertSame('JWT', $definitions['bearerAuth']['bearerFormat']);
    }

    public function test_generate_security_definitions_throws_for_invalid_definition(): void
    {
        $this->expectException(InvalidDefinitionException::class);

        SwaggerSecurityHelper::generateSecurityDefinitions(
            ['InvalidDefinition' => 'http'],
            'http://localhost'
        );
    }

    public function test_generate_security_definitions_throws_for_invalid_flow(): void
    {
        $this->expectException(InvalidAuthenticationFlow::class);

        SwaggerSecurityHelper::generateSecurityDefinitions(
            ['bearerAuth' => 'notAValidFlow'],
            'http://localhost'
        );
    }

    public function test_generate_security_definitions_returns_empty_for_empty_flows(): void
    {
        $definitions = SwaggerSecurityHelper::generateSecurityDefinitions([], 'http://localhost');

        $this->assertSame([], $definitions);
    }
}
