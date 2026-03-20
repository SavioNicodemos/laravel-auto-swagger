<?php

namespace AutoSwagger\Docs\Tests\Feature\Generator;

use AutoSwagger\Docs\Generator;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\MultiContentTypeController;
use AutoSwagger\Docs\Tests\Fixtures\Traits\ConfigMakerTrait;
use AutoSwagger\Docs\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class MultiContentTypeResponseTest extends TestCase
{
    use ConfigMakerTrait;

    private function generate(): array
    {
        Route::get('/api/reports/export', [MultiContentTypeController::class, 'export']);

        return (new Generator($this->makeConfig()))->generate();
    }

    public function test_both_content_types_are_present_for_same_response_code(): void
    {
        $content = $this->generate()['paths']['/reports/export']['get']['responses']['200']['content'];

        $this->assertArrayHasKey('application/pdf', $content);
        $this->assertArrayHasKey('application/json', $content);
    }

    public function test_pdf_content_type_has_binary_schema(): void
    {
        $schema = $this->generate()['paths']['/reports/export']['get']['responses']['200']['content']['application/pdf']['schema'];

        $this->assertSame('string', $schema['type']);
        $this->assertSame('binary', $schema['format']);
    }

    public function test_json_content_type_has_binary_schema(): void
    {
        $schema = $this->generate()['paths']['/reports/export']['get']['responses']['200']['content']['application/json']['schema'];

        $this->assertSame('string', $schema['type']);
        $this->assertSame('binary', $schema['format']);
    }

    public function test_description_from_first_annotation_is_preserved(): void
    {
        $response = $this->generate()['paths']['/reports/export']['get']['responses']['200'];

        $this->assertSame('PDF export.', $response['description']);
    }

    public function test_other_response_codes_are_unaffected(): void
    {
        $response401 = $this->generate()['paths']['/reports/export']['get']['responses']['401'];

        $this->assertSame('Unauthorized.', $response401['description']);
    }
}
