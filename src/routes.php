<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use AutoSwagger\Docs\Http\Controllers\SwaggerController;

if (Config::get('swagger.enable', true)) {
    Route::prefix(Config::get('swagger.path', '/docs'))->group(static function () {
        Route::get('', [SwaggerController::class, 'api']);
        Route::get('content', [SwaggerController::class, 'documentation']);
    });
}
