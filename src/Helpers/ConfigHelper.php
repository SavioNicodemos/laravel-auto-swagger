<?php

namespace AutoSwagger\Docs\Helpers;

use Illuminate\Support\Arr;

class ConfigHelper
{
    public static function shouldIgnoreAllModels(): bool
    {
        $modelsArray = config('swagger.ignored.models', []);

        return (isset($modelsArray[0]) && $modelsArray[0] === '*');
    }

    /**
     * Resolve the fully-merged configuration array for a given page.
     *
     * Merges global swagger config (minus the pages key) with the page-specific
     * overrides. The 'ignored.routes' arrays are combined additively, and all
     * swagger page routes are auto-excluded so they never appear in the spec.
     *
     * @throws \InvalidArgumentException when the page name is not defined
     */
    public static function resolvePageConfig(string $pageName): array
    {
        $global = config('swagger');
        $pages  = Arr::get($global, 'pages', []);

        if (!isset($pages[$pageName])) {
            throw new \InvalidArgumentException(
                "Swagger page '{$pageName}' is not defined in config/swagger.php."
            );
        }

        $pageConfig = $pages[$pageName];

        // Start from global config (without the pages key)
        $merged = Arr::except($global, 'pages');

        // Overlay every page key on top of the global config
        foreach ($pageConfig as $key => $value) {
            if ($key === 'ignored') {
                // Routes: additive merge (global + page-specific)
                $merged['ignored']['routes'] = array_merge(
                    Arr::get($merged, 'ignored.routes', []),
                    Arr::get($value, 'routes', [])
                );
                // methods / models: page can override if explicitly set
                if (array_key_exists('methods', $value)) {
                    $merged['ignored']['methods'] = $value['methods'];
                }
                if (array_key_exists('models', $value)) {
                    $merged['ignored']['models'] = $value['models'];
                }
            } else {
                $merged[$key] = $value;
            }
        }

        // Auto-exclude every swagger page UI route so they are never
        // included in the generated spec, regardless of api_base_path.
        $autoExcluded = [];
        foreach (Arr::get($global, 'pages', []) as $page) {
            $pagePath       = Arr::get($page, 'path', '/docs');
            $autoExcluded[] = $pagePath;
            $autoExcluded[] = $pagePath . '/content';
        }

        $merged['ignored']['routes'] = array_unique(
            array_merge($merged['ignored']['routes'], $autoExcluded)
        );

        return $merged;
    }

    /**
     * Return every configured page name.
     */
    public static function getPageNames(): array
    {
        return array_keys(config('swagger.pages', []));
    }
}
