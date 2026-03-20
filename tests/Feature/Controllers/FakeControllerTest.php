<?php

namespace AutoSwagger\Docs\Tests\Feature\Controllers;

use AutoSwagger\Docs\Generator;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\FakeController;
use AutoSwagger\Docs\Tests\Fixtures\Traits\ConfigMakerTrait;
use AutoSwagger\Docs\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class FakeControllerTest extends TestCase
{
    use ConfigMakerTrait;

    public function test_generate_includes_registered_api_route(): void
    {
        Route::get('/api/users', [FakeController::class, 'index']);

        $result = (new Generator($this->makeConfig()))->generate();

        $this->assertArrayHasKey('/users', $result['paths']);
    }

    public function test_generate_includes_route_with_path_parameter(): void
    {
        Route::get('/api/users/{id}', [FakeController::class, 'show']);

        $result = (new Generator($this->makeConfig()))->generate();

        $this->assertArrayHasKey('/users/{id}', $result['paths']);
    }

    public function test_generate_adds_request_body_for_post_route_with_form_request(): void
    {
        Route::post('/api/users', [FakeController::class, 'store']);

        $result = (new Generator($this->makeConfig()))->generate();

        $this->assertArrayHasKey('/users', $result['paths']);
        $this->assertArrayHasKey('post', $result['paths']['/users']);
        $this->assertArrayHasKey('requestBody', $result['paths']['/users']['post']);
    }

    public function test_generate_returns_expected_parameters(): void
    {
        Route::post('/api/users', [FakeController::class, 'store']);

        $result = (new Generator($this->makeConfig()))->generate();

        $schema = $result['paths']['/users']['post']['requestBody']['content']['application/json']['schema'];
        $this->assertArrayHasKey('required', $schema);
        $this->assertCount(3, $schema['required']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('email', $schema['properties']);
        $this->assertArrayHasKey('age', $schema['properties']);
        $this->assertArrayHasKey('role', $schema['properties']);

        // Assert Datapoints typings
        $this->assertSame('string', $schema['properties']['name']['type']);
        $this->assertSame(255, $schema['properties']['name']['maxLength']);

        $this->assertSame('string', $schema['properties']['email']['type']);
        $this->assertSame('email', $schema['properties']['email']['format']);

        $this->assertSame('integer', $schema['properties']['age']['type']);
        $this->assertSame(true, $schema['properties']['age']['nullable']);
        $this->assertSame(0, $schema['properties']['age']['minimum']);
        $this->assertSame(120, $schema['properties']['age']['maximum']);

        $this->assertSame('string', $schema['properties']['role']['type']);
        $this->assertArrayHasKey('enum', $schema['properties']['role']);
        $this->assertCount(3, $schema['properties']['role']['enum']);
        $this->assertContains('admin', $schema['properties']['role']['enum']);
        $this->assertContains('user', $schema['properties']['role']['enum']);
        $this->assertContains('moderator', $schema['properties']['role']['enum']);
    }

    // --- swagger_hidden ---

    private function filterSchema(): array
    {
        Route::post('/api/users/filter', [FakeController::class, 'filter']);
        $result = (new Generator($this->makeConfig()))->generate();
        return $result['paths']['/users/filter']['post']['requestBody']['content']['application/json']['schema'];
    }

    public function test_swagger_hidden_scalar_field_is_excluded_from_request_body(): void
    {
        $schema = $this->filterSchema();

        $this->assertArrayNotHasKey('tracking_id', $schema['properties']);
    }

    public function test_swagger_hidden_field_is_not_in_required_list(): void
    {
        $schema = $this->filterSchema();

        $this->assertNotContains('tracking_id', $schema['required'] ?? []);
    }

    public function test_swagger_hidden_array_parent_is_excluded_from_request_body(): void
    {
        $schema = $this->filterSchema();

        $this->assertArrayNotHasKey('address', $schema['properties']);
    }

    public function test_child_rules_of_hidden_parent_are_also_excluded(): void
    {
        $schema = $this->filterSchema();

        // address.city and address.zip should not appear as top-level or nested keys
        $this->assertArrayNotHasKey('address', $schema['properties']);
        $this->assertArrayNotHasKey('city', $schema['properties']);
        $this->assertArrayNotHasKey('zip', $schema['properties']);
    }

    public function test_non_hidden_fields_remain_in_request_body(): void
    {
        $schema = $this->filterSchema();

        $this->assertArrayHasKey('tag_ids', $schema['properties']);
    }

    public function test_children_of_visible_array_field_are_not_affected(): void
    {
        $schema = $this->filterSchema();

        $this->assertArrayHasKey('tag_ids', $schema['properties']);
        $this->assertSame('array', $schema['properties']['tag_ids']['type']);
    }
}
