<?php

namespace AutoSwagger\Docs\Tests\Unit\Helpers;

use AutoSwagger\Docs\Helpers\PathParamsHelper;
use AutoSwagger\Docs\Tests\TestCase;

class PathParamsHelperTest extends TestCase
{
    private function makeDoc(string $path, array $methods): array
    {
        $doc = ['paths' => []];
        foreach ($methods as $method => $params) {
            $doc['paths'][$path][$method] = ['parameters' => $params];
        }
        return $doc;
    }

    private function pathParam(string $name): array
    {
        return ['name' => $name, 'in' => 'path', 'required' => true, 'description' => '', 'schema' => ['type' => 'string']];
    }

    public function test_path_is_preserved_when_param_name_is_unchanged(): void
    {
        $routeRenaming = [
            '/bookings/{eurostarBooking}/cancel' => [
                ['name' => 'eurostarBooking', 'type' => 'string', 'description' => 'Booking reference'],
            ],
        ];

        $doc = $this->makeDoc('/bookings/{eurostarBooking}/cancel', [
            'post' => [$this->pathParam('eurostarBooking')],
        ]);

        PathParamsHelper::renamePaths($doc, $routeRenaming);

        $this->assertArrayHasKey('/bookings/{eurostarBooking}/cancel', $doc['paths']);
    }

    public function test_description_is_applied_when_param_name_is_unchanged(): void
    {
        $routeRenaming = [
            '/bookings/{eurostarBooking}/cancel' => [
                ['name' => 'eurostarBooking', 'type' => 'string', 'description' => 'Booking reference'],
            ],
        ];

        $doc = $this->makeDoc('/bookings/{eurostarBooking}/cancel', [
            'post' => [$this->pathParam('eurostarBooking')],
        ]);

        PathParamsHelper::renamePaths($doc, $routeRenaming);

        $param = $doc['paths']['/bookings/{eurostarBooking}/cancel']['post']['parameters'][0];
        $this->assertSame('Booking reference', $param['description']);
    }

    public function test_path_key_is_renamed_when_param_name_changes(): void
    {
        $routeRenaming = [
            '/bookings/{id}/cancel' => [
                ['name' => 'eurostarBooking', 'type' => 'string', 'description' => 'Booking reference'],
            ],
        ];

        $doc = $this->makeDoc('/bookings/{id}/cancel', [
            'post' => [$this->pathParam('id')],
        ]);

        PathParamsHelper::renamePaths($doc, $routeRenaming);

        $this->assertArrayHasKey('/bookings/{eurostarBooking}/cancel', $doc['paths']);
        $this->assertArrayNotHasKey('/bookings/{id}/cancel', $doc['paths']);
    }

    public function test_param_name_and_description_are_updated_on_rename(): void
    {
        $routeRenaming = [
            '/bookings/{id}/cancel' => [
                ['name' => 'eurostarBooking', 'type' => 'string', 'description' => 'Booking reference'],
            ],
        ];

        $doc = $this->makeDoc('/bookings/{id}/cancel', [
            'post' => [$this->pathParam('id')],
        ]);

        PathParamsHelper::renamePaths($doc, $routeRenaming);

        $param = $doc['paths']['/bookings/{eurostarBooking}/cancel']['post']['parameters'][0];
        $this->assertSame('eurostarBooking', $param['name']);
        $this->assertSame('Booking reference', $param['description']);
    }
}
