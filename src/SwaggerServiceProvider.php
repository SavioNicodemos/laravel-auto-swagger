<?php

namespace AutoSwagger\Docs;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use AutoSwagger\Docs\Commands\GenerateSwaggerDocumentation;
use AutoSwagger\Docs\Commands\MakeSwaggerSchemaBuilder;
use AutoSwagger\Docs\Http\Controllers\SwaggerController;

/**
 * Class SwaggerServiceProvider
 * @package AutoSwagger\Docs
 */
class SwaggerServiceProvider extends ServiceProvider
{

    /**
     * @inheritDoc
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSwaggerDocumentation::class,
                MakeSwaggerSchemaBuilder::class
            ]);
        }

        $source = __DIR__ . '/../config/swagger.php';

        $this->publishes([
            $source => config_path('swagger.php')
        ]);

        $viewsPath = __DIR__ . '/../resources/views';
        $this->loadViewsFrom($viewsPath, 'swagger');

        $this->publishes([
            $viewsPath => Config::get('swagger.views', base_path('resources/views/vendor/swagger')),
        ]);

        $this->mergeConfigFrom($source, 'swagger');

        $this->registerPageRoutes();

        $this->loadValidationRules();

        if (file_exists($file = __DIR__ . '/helpers.php')) {
            require $file;
        }
    }

    /**
     * Register a UI route pair (GET /path and GET /path/content) for each
     * configured swagger page. The page name is passed to the controller via
     * a route default so the URL structure stays clean.
     */
    private function registerPageRoutes(): void
    {
        if (!Config::get('swagger.enable', true)) {
            return;
        }

        $pages = Config::get('swagger.pages', []);

        foreach ($pages as $name => $page) {
            $path       = Arr::get($page, 'path', '/docs/' . $name);
            $middleware = Arr::get($page, 'middleware', []);

            Route::middleware($middleware)
                ->prefix($path)
                ->group(function () use ($name) {
                    Route::get('', [SwaggerController::class, 'api'])
                        ->defaults('_swagger_page', $name);

                    Route::get('content', [SwaggerController::class, 'documentation'])
                        ->defaults('_swagger_page', $name);
                });
        }
    }

    /**
     * Load custom validation rules
     * @return void
     */
    private function loadValidationRules(): void
    {
        Validator::extend('swagger_default', function () {
            return true;
        });
        Validator::extend('swagger_example', function () {
            return true;
        });
        Validator::extend('swagger_description', function () {
            return true;
        });
        Validator::extend('swagger_required', function () {
            return true;
        });
        Validator::extend('swagger_min', function ($_, $value, array $parameters) {
            [$min, $fail] = $this->parseParameters($parameters);
            $valueType = $this->getTypeFromString((string) $value);
            settype($min, $valueType);
            if ($fail) {
                return $value >= $min;
            }
            return true;
        });
        Validator::extend('swagger_max', function ($_, $value, array $parameters) {
            [$max, $fail] = $this->parseParameters($parameters);
            $valueType = $this->getTypeFromString((string) $value);
            settype($max, $valueType);
            if ($fail) {
                return $value <= $max;
            }
            return true;
        });
    }

    /**
     * Get variable type from string
     * @param string $string
     * @return string
     */
    private function getTypeFromString(string $string): string
    {
        return gettype($this->guessVariableType($string));
    }

    /**
     * Guess variable type
     * @param string $string
     * @return bool|float|int|string
     */
    private function guessVariableType(string $string)
    {
        $string = trim($string);
        if (empty($string)) {
            return '';
        }
        if (!preg_match('/[^0-9.]+/', $string)) {
            if (preg_match('/[.]+/', $string)) {
                return (float) $string;
            }
            return (int) $string;
        }
        if ($string === 'true') {
            return (bool) true;
        }
        if ($string === 'false') {
            return (bool) false;
        }
        return (string) $string;
    }

    /**
     * Parse parameter
     * @param array $parameters
     * @return array
     */
    private function parseParameters(array $parameters): array
    {
        $parameter = Arr::first($parameters);
        $exploded = explode(':', $parameter);
        $value = $exploded[0];
        if (\count($exploded) === 2) {
            return [
                $value,
                isset($exploded[1]) ? $exploded[1] === 'fail' : false
            ];
        }
        return [$value, false];
    }
}
