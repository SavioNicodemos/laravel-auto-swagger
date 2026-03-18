<?php

namespace AutoSwagger\Docs\Services;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UIDriversService
{
    protected array $availableDrivers = [
        'swagger-ui',
        'scalar',
        'rapidoc',
    ];

    protected string $defaultDriver = 'swagger-ui';

    public function getAvailableDrivers(): array
    {
        return $this->availableDrivers;
    }

    /**
     * Return the Blade view path for the given UI driver.
     * Falls back to the default driver if an invalid name is supplied.
     *
     * @throws InvalidArgumentException when the view file does not exist
     */
    public function getViewPath(string $driver): string
    {
        if (!in_array($driver, $this->availableDrivers)) {
            Log::error(
                'You passed an unsupported driver to AutoSwagger UI config. Falling back to the default UI driver.',
                ['driver' => $driver]
            );
            $driver = $this->defaultDriver;
        }

        $view = 'swagger::' . $driver;

        if (!View::exists($view)) {
            throw new InvalidArgumentException("View file for driver '$driver' does not exist.");
        }

        return $view;
    }
}
