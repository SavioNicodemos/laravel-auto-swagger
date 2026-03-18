<?php

namespace AutoSwagger\Docs\Tests;

abstract class SchemaTestCase extends TestCase
{
    /**
     * Point the Testbench Laravel skeleton to the project root so that
     * app_path() resolves to <project_root>/app — the directory where
     * our Swagger schema fixtures live. DefinitionGenerator builds class
     * names relative to app_path(), so this is required for fixture schemas
     * under app/Swagger/Schemas/ to be discovered with namespace App\.
     */
    protected function getApplicationBasePath(): string
    {
        return __DIR__ . '/laravel';
    }
}
