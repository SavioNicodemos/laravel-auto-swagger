<?php

namespace AutoSwagger\Docs\Helpers;

class ConfigHelper
{
    public static function shouldIgnoreAllModels(): bool
    {
        $modelsArray = config('swagger.ignored.models', []);

        return (isset($modelsArray[0]) && $modelsArray[0] === '*');
    }
}
