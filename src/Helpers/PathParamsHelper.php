<?php

namespace AutoSwagger\Docs\Helpers;

use AutoSwagger\Docs\Exceptions\MultiplePathParamsException;
use Illuminate\Support\Arr;

class PathParamsHelper
{
    /**
     * Check for path params changes, if was requested some renaming or type change
     * @throws MultiplePathParamsException
     */
    public static function checkForPathParamsChanges(
        array &$documentation,
        string $routeName,
        array &$routeRenaming
    ): void {
        if (!Arr::has($documentation, 'pathParams')) {
            return;
        }

        if (isset($routeRenaming[$routeName])) {
            Arr::forget($documentation, 'pathParams');
            throw new MultiplePathParamsException();
        } else {
            $routeRenaming[$routeName] = Arr::get($documentation, 'pathParams');
        }

        Arr::forget($documentation, 'pathParams');
    }

    public static function renamePaths(array &$documentation, array $routeRenaming): void
    {
        foreach ($routeRenaming as $route => $changes) {
            $originalPathKey = 'paths.'.$route;
            $path = Arr::get($documentation, $originalPathKey);
            if ($path) {
                $paramNames = collect($changes)->pluck('name')->all();
                $relativePath = RouteHelper::replaceUrlParams($route, $paramNames);

                self::renameParamsInAllMethods($path, $changes);

                Arr::set($documentation, 'paths.'.$relativePath, $path);
                Arr::forget($documentation, $originalPathKey);
            }
        }
    }

    protected static function renameParamsInAllMethods(array &$path, array $changes): void
    {
        foreach ($path as $method => $methodData) {
            if (!Arr::has($methodData, 'parameters')) {
                continue;
            }

            $path[$method]['parameters'] = self::processPathParams(
                Arr::get($methodData, 'parameters'),
                $changes
            );
        }
    }

    protected static function processPathParams(array $parameters, array $changes): array
    {
        $parameters = collect($parameters);
        $pathParamsExcluded = $parameters->where('in', '!==', 'path')->all();
        $pathParams = $parameters->where('in', 'path')->all();

        foreach ($pathParams as $key => $param) {
            if (isset($changes[$key])) {
                $pathParams[$key] = self::updatePathParam($param, $changes[$key]);
            }
        }

        return array_merge($pathParams, $pathParamsExcluded);
    }

    /**
     * Update path param with new values
     */
    protected static function updatePathParam(array $pathParam, array $updatedPathParam): array
    {
        if ($type = Arr::get($updatedPathParam, 'type')) {
            $pathParam['schema']['type'] = $type;
            Arr::forget($updatedPathParam, 'type');
        }
        return array_merge($pathParam, $updatedPathParam);
    }
}