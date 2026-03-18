<?php

namespace AutoSwagger\Docs\Tests\Unit\Helpers;

use AutoSwagger\Docs\DataObjects\Route as SwaggerRoute;
use AutoSwagger\Docs\Helpers\RouteHelper;
use AutoSwagger\Docs\Tests\TestCase;
use Illuminate\Routing\Route;

class RouteHelperTest extends TestCase
{
    // --- replaceUrlParams ---

    public function test_replace_url_params_replaces_single_placeholder(): void
    {
        $result = RouteHelper::replaceUrlParams('/users/{id}', ['user_id']);

        $this->assertSame('/users/{user_id}', $result);
    }

    public function test_replace_url_params_replaces_multiple_placeholders(): void
    {
        $result = RouteHelper::replaceUrlParams('/users/{userId}/posts/{postId}', ['user_id', 'post_id']);

        $this->assertSame('/users/{user_id}/posts/{post_id}', $result);
    }

    public function test_replace_url_params_leaves_extra_placeholders_unchanged(): void
    {
        $result = RouteHelper::replaceUrlParams('/users/{userId}/posts/{postId}', ['user_id']);

        $this->assertSame('/users/{user_id}/posts/{postId}', $result);
    }

    public function test_replace_url_params_returns_same_url_when_no_placeholders(): void
    {
        $result = RouteHelper::replaceUrlParams('/users', ['anything']);

        $this->assertSame('/users', $result);
    }

    // --- getRelativePathFromUri ---

    public function test_get_relative_path_strips_filter_prefix(): void
    {
        $this->assertSame('/users', RouteHelper::getRelativePathFromUri('/api/users', '/api'));
    }

    public function test_get_relative_path_returns_slash_when_uri_equals_filter(): void
    {
        $this->assertSame('/', RouteHelper::getRelativePathFromUri('/api', '/api'));
    }

    public function test_get_relative_path_returns_null_for_relative_uri(): void
    {
        $this->assertNull(RouteHelper::getRelativePathFromUri('api/users', '/api'));
    }

    public function test_get_relative_path_with_no_filter_returns_full_uri(): void
    {
        $this->assertSame('/api/users', RouteHelper::getRelativePathFromUri('/api/users', null));
    }

    // --- isFilteredRoute ---

    public function test_is_filtered_route_returns_true_for_ignored_route_name(): void
    {
        $laravelRoute = new Route(['GET'], '/api/users', function () {});
        $laravelRoute->name('users.index');
        $swaggerRoute = new SwaggerRoute($laravelRoute);

        $this->assertTrue(RouteHelper::isFilteredRoute($swaggerRoute, null, ['users.index']));
    }

    public function test_is_filtered_route_returns_true_for_ignored_route_uri(): void
    {
        $laravelRoute = new Route(['GET'], '/api/users', function () {});
        $swaggerRoute = new SwaggerRoute($laravelRoute);

        $this->assertTrue(RouteHelper::isFilteredRoute($swaggerRoute, null, ['/api/users']));
    }

    public function test_is_filtered_route_returns_false_when_route_passes_filter(): void
    {
        $laravelRoute = new Route(['GET'], '/api/users', function () {});
        $swaggerRoute = new SwaggerRoute($laravelRoute);

        $this->assertFalse(RouteHelper::isFilteredRoute($swaggerRoute, '/api', []));
    }

    public function test_is_filtered_route_returns_true_when_uri_does_not_match_filter(): void
    {
        $laravelRoute = new Route(['GET'], '/web/home', function () {});
        $swaggerRoute = new SwaggerRoute($laravelRoute);

        $this->assertTrue(RouteHelper::isFilteredRoute($swaggerRoute, '/api', []));
    }
}
