<?php

namespace AutoSwagger\Docs\Tests\Feature\Controllers;

use AutoSwagger\Docs\Generator;
use AutoSwagger\Docs\Tests\Fixtures\Controllers\InvokableController;
use AutoSwagger\Docs\Tests\Fixtures\Traits\ConfigMakerTrait;
use AutoSwagger\Docs\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class InvokableControllerTest extends TestCase
{
    use ConfigMakerTrait;

    private array $operation;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/reports/{reportId}', InvokableController::class);

        $result = (new Generator($this->makeConfig()))->generate();
        $this->operation = $result['paths']['/reports/{reportId}']['get'];
    }

    public function test_invokable_controller_summary_is_read_from_invoke_docblock(): void
    {
        $this->assertSame('Download report', $this->operation['summary']);
    }

    public function test_invokable_controller_description_is_read_from_invoke_docblock(): void
    {
        $this->assertSame('Returns the generated report file.', $this->operation['description']);
    }

    public function test_invokable_controller_tags_are_read_from_invoke_docblock(): void
    {
        $this->assertContains('reports', $this->operation['tags']);
    }

    public function test_invokable_controller_response_is_read_from_invoke_docblock(): void
    {
        $this->assertArrayHasKey(200, $this->operation['responses']);
        $this->assertSame('Report file.', $this->operation['responses'][200]['description']);
    }

    public function test_invokable_controller_path_param_description_is_read_from_invoke_docblock(): void
    {
        $param = collect($this->operation['parameters'])
            ->firstWhere('name', 'reportId');

        $this->assertNotNull($param);
        $this->assertSame('Report identifier', $param['description']);
    }
}
