<?php

namespace AutoSwagger\Docs\Tests\Unit;

use AutoSwagger\Docs\Exceptions\InvalidFormatException;
use AutoSwagger\Docs\Formatter;
use AutoSwagger\Docs\Tests\TestCase;

class FormatterTest extends TestCase
{
    private array $sampleDoc = [
        'openapi' => '3.0.0',
        'info'    => ['title' => 'Test API', 'version' => '1.0.0'],
        'paths'   => [],
    ];

    public function test_it_defaults_to_json_format(): void
    {
        $result = (new Formatter($this->sampleDoc))->format();

        $this->assertNotNull(json_decode($result, true));
    }

    public function test_json_format_produces_valid_json(): void
    {
        $result = (new Formatter($this->sampleDoc))->setFormat('json')->format();

        $decoded = json_decode($result, true);
        $this->assertSame('3.0.0', $decoded['openapi']);
        $this->assertSame('Test API', $decoded['info']['title']);
    }

    public function test_json_output_is_pretty_printed(): void
    {
        $result = (new Formatter($this->sampleDoc))->setFormat('json')->format();

        $this->assertStringContainsString("\n", $result);
    }

    public function test_json_output_does_not_escape_forward_slashes(): void
    {
        $doc    = ['url' => 'http://localhost/api/v1'];
        $result = (new Formatter($doc))->setFormat('json')->format();

        $this->assertStringContainsString('http://localhost/api/v1', $result);
        $this->assertStringNotContainsString('http:\/\/localhost\/api\/v1', $result);
    }

    public function test_set_format_is_case_insensitive(): void
    {
        $result = (new Formatter($this->sampleDoc))->setFormat('JSON')->format();

        $this->assertNotNull(json_decode($result, true));
    }

    public function test_it_throws_for_unsupported_format(): void
    {
        $this->expectException(InvalidFormatException::class);

        (new Formatter($this->sampleDoc))->setFormat('xml');
    }

    public function test_it_throws_for_unknown_format(): void
    {
        $this->expectException(InvalidFormatException::class);

        (new Formatter($this->sampleDoc))->setFormat('csv');
    }

    public function test_set_format_returns_self_for_chaining(): void
    {
        $formatter = new Formatter($this->sampleDoc);
        $result    = $formatter->setFormat('json');

        $this->assertSame($formatter, $result);
    }
}
