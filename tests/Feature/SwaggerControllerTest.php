<?php

namespace AutoSwagger\Docs\Tests\Feature;

use AutoSwagger\Docs\Tests\TestCase;

class SwaggerControllerTest extends TestCase
{
    public function test_swagger_ui_page_returns_200(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
    }

    public function test_swagger_ui_page_returns_html(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $this->assertStringContainsString('<html', $response->getContent());
    }

    public function test_swagger_content_endpoint_returns_200(): void
    {
        $response = $this->get('/docs/content');

        $response->assertStatus(200);
    }

    public function test_swagger_content_endpoint_returns_json_content_type(): void
    {
        $response = $this->get('/docs/content');

        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function test_swagger_content_returns_valid_openapi_structure(): void
    {
        $response = $this->get('/docs/content');
        $body     = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('openapi', $body);
        $this->assertSame('3.0.0', $body['openapi']);
        $this->assertArrayHasKey('info', $body);
        $this->assertArrayHasKey('paths', $body);
        $this->assertArrayHasKey('components', $body);
    }

    public function test_swagger_content_info_uses_page_config_title(): void
    {
        config(['swagger.pages.default.title' => 'My Custom API']);

        $response = $this->get('/docs/content');
        $body     = json_decode($response->getContent(), true);

        $this->assertSame('My Custom API', $body['info']['title']);
    }

    public function test_swagger_content_info_uses_page_config_version(): void
    {
        config(['swagger.pages.default.version' => '5.0.0']);

        $response = $this->get('/docs/content');
        $body     = json_decode($response->getContent(), true);

        $this->assertSame('5.0.0', $body['info']['version']);
    }

    public function test_swagger_content_json_is_decodable(): void
    {
        $response = $this->get('/docs/content');

        $body = json_decode($response->getContent(), true);

        $this->assertNotNull($body);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }
}
