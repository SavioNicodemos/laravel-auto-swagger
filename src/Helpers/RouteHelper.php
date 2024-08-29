<?php

namespace AutoSwagger\Docs\Helpers;

use AutoSwagger\Docs\DataObjects\Route;
use Illuminate\Support\Str;

class RouteHelper
{
    /**
     * Replace URL parameters with new names
     *
     * If you have a route like '/users/{id}' and you want to rename 'id' to 'user_id'
     * just call here replaceUrlParams('/users/{id}', ['user_id']) and you will get /users/{user_id}
     */
    public static function replaceUrlParams(string $url, array $params): string
    {
        $index = 0;
        $count = count($params);

        return preg_replace_callback('/\{[^}]+\}/', function ($match) use (&$index, $count, $params) {
            if ($index < $count) {
                return '{'.$params[$index++].'}';
            }
            return $match[0];
        }, $url);
    }

    /**
     * Check whether this is filtered route
     */
    public static function isFilteredRoute(Route $route, ?string $routeFilter, array $ignoredRoutes): bool
    {
        $routeName = $route->name();
        $routeUri = $route->uri();
        if ($routeName) {
            if (in_array($routeName, $ignoredRoutes)) {
                return true;
            }
        }

        if (in_array($routeUri, $ignoredRoutes)) {
            return true;
        }
        if ($routeFilter) {
            return !preg_match('/^'.preg_quote($routeFilter, '/').'/', $route->uri());
        }
        return false;
    }

    /**
     * Get relative path from URI considering the filter applied and return it in the Swagger format expected
     */
    public static function getRelativePathFromUri(string $uri, ?string $filter = null): ?string
    {
        $basePath = $filter ?: config('swagger.api_base_path');
        $relativePath = Str::replaceFirst($basePath, '', $uri);
        if ($relativePath === '') {
            $relativePath = '/';
        }
        if (!Str::startsWith($uri, '/')) {
            return null;
        }
        return $relativePath;
    }
}