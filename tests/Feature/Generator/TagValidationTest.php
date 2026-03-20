<?php

namespace AutoSwagger\Docs\Tests\Feature\Generator;

use AutoSwagger\Docs\Generator;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\FakeController;
use AutoSwagger\Docs\Tests\Fixtures\Traits\ConfigMakerTrait;
use AutoSwagger\Docs\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;

class TagValidationTest extends TestCase
{
    use ConfigMakerTrait;

    public function test_it_logs_warning_when_operation_tag_is_not_in_global_tags(): void
    {
        Route::get('/api/users', [FakeController::class, 'index']);

        // 'users' comes from the prefix strategy but is not in the configured tags list
        Log::shouldReceive('warning')
            ->once()
            ->with(\Mockery::on(fn(string $msg) => str_contains($msg, 'users')));

        (new Generator($this->makeConfig([
            'tags' => [
                ['name' => 'hotels', 'description' => 'Hotel operations'],
            ],
            'default_tags_generation_strategy' => 'prefix',
        ])))->generate();
    }

    public function test_it_does_not_warn_when_all_operation_tags_are_defined(): void
    {
        Route::get('/api/users', [FakeController::class, 'index']);

        Log::shouldReceive('warning')->never();

        (new Generator($this->makeConfig([
            'tags' => [
                ['name' => 'users', 'description' => 'User operations'],
            ],
            'default_tags_generation_strategy' => 'prefix',
        ])))->generate();
    }

    public function test_it_does_not_warn_when_no_global_tags_are_configured(): void
    {
        Route::get('/api/users', [FakeController::class, 'index']);

        Log::shouldReceive('warning')->never();

        (new Generator($this->makeConfig([
            'tags' => [],
            'default_tags_generation_strategy' => 'prefix',
        ])))->generate();
    }
}
