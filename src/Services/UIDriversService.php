<?php

namespace AutoSwagger\Docs\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

class UIDriversService
{
    protected $availableDrivers = [
        'swagger-ui',
        'scalar',
    ];

    protected $defaultDriver = 'swagger-ui';

    public function getDefaultDriver(): string
    {
        $defaultRoute = Config::get('swagger.ui.default');

        if (!in_array($defaultRoute, $this->availableDrivers)) {
            Log::error(
                "You passed a wrong driver to AutoSwagger UI config. Please review it, falling back to the default UI driver...",
                ['swagger.ui.default' => $defaultRoute]
            );
            $defaultRoute = $this->defaultDriver;
        }

        return $defaultRoute;
    }

    public function getAvailableDrivers(): array
    {
        return $this->availableDrivers;
    }

    public function getViewPath(): string
    {
        $driver = $this->getDefaultDriver();
        $view = 'swagger::' . $driver;

        if (!View::exists($view)) {
            throw new \InvalidArgumentException("View file for driver '$driver' does not exist.");
        }

        return $view;
    }
}
