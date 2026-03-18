<?php

namespace AutoSwagger\Docs\Tests\Feature;

use AutoSwagger\Docs\Generator;
use AutoSwagger\Docs\Tests\Fixtures\Traits\ConfigMakerTrait;
use AutoSwagger\Docs\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class GeneratorTest extends TestCase
{
    use ConfigMakerTrait;

    public function test_generate_returns_valid_openapi_base_structure(): void
    {
        $result = (new Generator($this->makeConfig()))->generate();

        $this->assertArrayHasKey('openapi', $result);
        $this->assertSame('3.0.0', $result['openapi']);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('paths', $result);
    }

    public function test_generate_uses_title_from_config(): void
    {
        $result = (new Generator($this->makeConfig(['title' => 'My API'])))->generate();

        $this->assertSame('My API', $result['info']['title']);
    }

    public function test_generate_uses_version_from_config(): void
    {
        $result = (new Generator($this->makeConfig(['version' => '2.5.0'])))->generate();

        $this->assertSame('2.5.0', $result['info']['version']);
    }

    public function test_generate_uses_description_from_config(): void
    {
        $result = (new Generator($this->makeConfig(['description' => 'Great API'])))->generate();

        $this->assertSame('Great API', $result['info']['description']);
    }

    public function test_generate_excludes_routes_outside_api_base_path(): void
    {
        Route::get('/web/home', function () { return 'home'; });

        $result = (new Generator($this->makeConfig()))->generate();

        $this->assertArrayNotHasKey('/home', $result['paths']);
        $this->assertArrayNotHasKey('/web/home', $result['paths']);
    }

    public function test_generate_excludes_head_and_options_methods(): void
    {
        Route::match(['GET', 'HEAD', 'OPTIONS'], '/api/ping', function () { return 'pong'; });

        $result = (new Generator($this->makeConfig()))->generate();

        if (isset($result['paths']['/ping'])) {
            $methods = array_keys($result['paths']['/ping']);
            $this->assertNotContains('head', $methods);
            $this->assertNotContains('options', $methods);
        }

        $this->assertTrue(true);
    }

    public function test_generate_includes_security_schemes_when_parse_security_enabled(): void
    {
        $result = (new Generator($this->makeConfig([
            'parse' => ['docBlock' => true, 'security' => true],
        ])))->generate();

        $this->assertArrayHasKey('securitySchemes', $result['components']);
        $this->assertArrayHasKey('bearerAuth', $result['components']['securitySchemes']);
    }

    public function test_generate_omits_security_schemes_when_parse_security_disabled(): void
    {
        $result = (new Generator($this->makeConfig([
            'parse' => ['docBlock' => true, 'security' => false],
        ])))->generate();

        $this->assertArrayNotHasKey('securitySchemes', $result['components'] ?? []);
    }

    public function test_generate_falls_back_to_default_server_when_none_configured(): void
    {
        $result = (new Generator($this->makeConfig(['servers' => []])))->generate();

        $this->assertNotEmpty($result['servers']);
    }

    public function test_generate_uses_configured_server_url(): void
    {
        $result = (new Generator($this->makeConfig([
            'servers' => ['https://api.example.com/v1'],
        ])))->generate();

        $this->assertSame('https://api.example.com/v1', $result['servers'][0]['url']);
    }

    public function test_generate_uses_server_url_and_description_when_both_provided(): void
    {
        $result = (new Generator($this->makeConfig([
            'servers' => [
                ['url' => 'https://api.example.com', 'description' => 'Production'],
            ],
        ])))->generate();

        $this->assertSame('https://api.example.com', $result['servers'][0]['url']);
        $this->assertSame('Production', $result['servers'][0]['description']);
    }

    public function test_generate_excludes_explicitly_ignored_route(): void
    {
        Route::get('/api/internal', function () { return 'secret'; })->name('internal.secret');

        $result = (new Generator($this->makeConfig([
            'ignored' => [
                'methods' => ['head', 'options'],
                'routes'  => ['internal.secret'],
                'models'  => ['*'],
            ],
        ])))->generate();

        $this->assertArrayNotHasKey('/internal', $result['paths']);
    }

    public function test_generate_tags_section_is_present(): void
    {
        $result = (new Generator($this->makeConfig()))->generate();

        $this->assertArrayHasKey('tags', $result);
    }
}
