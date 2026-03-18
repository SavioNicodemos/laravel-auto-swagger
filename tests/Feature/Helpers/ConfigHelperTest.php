<?php

namespace AutoSwagger\Docs\Tests\Feature\Helpers;

use AutoSwagger\Docs\Helpers\ConfigHelper;
use AutoSwagger\Docs\Tests\TestCase;

class ConfigHelperTest extends TestCase
{
    public function test_should_ignore_all_models_returns_false_when_models_array_is_empty(): void
    {
        config(['swagger.ignored.models' => []]);

        $this->assertFalse(ConfigHelper::shouldIgnoreAllModels());
    }

    public function test_should_ignore_all_models_returns_true_when_wildcard_is_set(): void
    {
        config(['swagger.ignored.models' => ['*']]);

        $this->assertTrue(ConfigHelper::shouldIgnoreAllModels());
    }

    public function test_should_ignore_all_models_returns_false_for_specific_model_names(): void
    {
        config(['swagger.ignored.models' => ['App\\Models\\User', 'App\\Models\\Post']]);

        $this->assertFalse(ConfigHelper::shouldIgnoreAllModels());
    }

    public function test_get_page_names_returns_all_configured_page_keys(): void
    {
        config([
            'swagger.pages' => [
                'default'  => ['path' => '/docs'],
                'internal' => ['path' => '/docs/internal'],
            ],
        ]);

        $names = ConfigHelper::getPageNames();

        $this->assertContains('default', $names);
        $this->assertContains('internal', $names);
        $this->assertCount(2, $names);
    }

    public function test_get_page_names_returns_empty_array_when_no_pages(): void
    {
        config(['swagger.pages' => []]);

        $this->assertSame([], ConfigHelper::getPageNames());
    }

    public function test_resolve_page_config_throws_for_undefined_page(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/nonexistent/");

        ConfigHelper::resolvePageConfig('nonexistent');
    }

    public function test_resolve_page_config_page_values_override_global(): void
    {
        config([
            'swagger.version' => '1.0.0',
            'swagger.pages.default.version' => '2.0.0',
        ]);

        $config = ConfigHelper::resolvePageConfig('default');

        $this->assertSame('2.0.0', $config['version']);
    }

    public function test_resolve_page_config_global_values_are_preserved_when_not_overridden(): void
    {
        // 'parse' is a global key that page configs don't override — changing it globally
        // should be reflected in the resolved config.
        config(['swagger.parse.docBlock' => false]);

        $config = ConfigHelper::resolvePageConfig('default');

        $this->assertFalse($config['parse']['docBlock']);
    }

    public function test_resolve_page_config_merges_ignored_routes_additively(): void
    {
        config([
            'swagger.ignored.routes' => ['global/route'],
            'swagger.pages.default.ignored' => ['routes' => ['page/route']],
        ]);

        $config = ConfigHelper::resolvePageConfig('default');

        $this->assertContains('global/route', $config['ignored']['routes']);
        $this->assertContains('page/route', $config['ignored']['routes']);
    }

    public function test_resolve_page_config_auto_excludes_page_ui_routes(): void
    {
        config(['swagger.pages.default.path' => '/api-docs']);

        $config = ConfigHelper::resolvePageConfig('default');

        $this->assertContains('/api-docs', $config['ignored']['routes']);
        $this->assertContains('/api-docs/content', $config['ignored']['routes']);
    }

    public function test_resolve_page_config_does_not_include_pages_key(): void
    {
        $config = ConfigHelper::resolvePageConfig('default');

        $this->assertArrayNotHasKey('pages', $config);
    }
}
