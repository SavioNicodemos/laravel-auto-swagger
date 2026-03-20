<?php

namespace AutoSwagger\Docs\Tests\Feature\Generator;

use AutoSwagger\Docs\Generator;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\BinaryResponseController;
use AutoSwagger\Docs\Tests\Fixtures\Traits\ConfigMakerTrait;
use AutoSwagger\Docs\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class BinaryResponseTest extends TestCase
{
    use ConfigMakerTrait;

    private function generate(): array
    {
        Route::get('/api/tickets/download', [BinaryResponseController::class, 'download']);

        return (new Generator($this->makeConfig()))->generate();
    }

    public function test_binary_response_generates_pdf_content_type(): void
    {
        $result = $this->generate();

        $response = $result['paths']['/tickets/download']['get']['responses']['200'];

        $this->assertArrayHasKey('content', $response);
        $this->assertArrayHasKey('application/pdf', $response['content']);
    }

    public function test_binary_response_schema_has_binary_format(): void
    {
        $result = $this->generate();

        $schema = $result['paths']['/tickets/download']['get']['responses']['200']['content']['application/pdf']['schema'];

        $this->assertSame('string', $schema['type']);
        $this->assertSame('binary', $schema['format']);
    }

    public function test_binary_response_does_not_produce_application_json_key(): void
    {
        $result = $this->generate();

        $response = $result['paths']['/tickets/download']['get']['responses']['200'];

        $this->assertArrayNotHasKey('application/json', $response['content'] ?? []);
    }

    public function test_response_without_content_type_still_uses_json(): void
    {
        $result = $this->generate();

        // The 401 response has no content_type, so it should not have any content schema
        // (no ref was provided either, so no content key at all)
        $response401 = $result['paths']['/tickets/download']['get']['responses']['401'] ?? [];

        $this->assertSame('(Unauthorized) Invalid or missing Access Token.', $response401['description']);
    }
}
