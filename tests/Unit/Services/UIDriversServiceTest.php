<?php

namespace AutoSwagger\Docs\Tests\Unit\Services;

use AutoSwagger\Docs\Services\UIDriversService;
use AutoSwagger\Docs\Tests\TestCase;

class UIDriversServiceTest extends TestCase
{
    private UIDriversService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UIDriversService();
    }

    public function test_it_lists_all_available_drivers(): void
    {
        $drivers = $this->service->getAvailableDrivers();

        $this->assertContains('swagger-ui', $drivers);
        $this->assertContains('scalar', $drivers);
        $this->assertContains('rapidoc', $drivers);
    }

    public function test_it_returns_view_path_for_swagger_ui(): void
    {
        $this->assertSame('swagger::swagger-ui', $this->service->getViewPath('swagger-ui'));
    }

    public function test_it_returns_view_path_for_scalar(): void
    {
        $this->assertSame('swagger::scalar', $this->service->getViewPath('scalar'));
    }

    public function test_it_returns_view_path_for_rapidoc(): void
    {
        $this->assertSame('swagger::rapidoc', $this->service->getViewPath('rapidoc'));
    }

    public function test_it_falls_back_to_swagger_ui_for_unknown_driver(): void
    {
        // Invalid driver logs an error but falls back to the default
        $this->assertSame('swagger::swagger-ui', $this->service->getViewPath('nonexistent'));
    }
}
