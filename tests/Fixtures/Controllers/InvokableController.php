<?php

namespace AutoSwagger\Docs\Tests\Fixtures\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class InvokableController extends Controller
{
    /**
     * Download report
     *
     * Returns the generated report file.
     *
     * @Request({
     *     "tags": ["reports"],
     *     "pathParams": [{
     *         "name": "reportId",
     *         "type": "string",
     *         "description": "Report identifier"
     *     }]
     * })
     * @Response({
     *     "code": 200,
     *     "description": "Report file."
     * })
     */
    public function __invoke(string $reportId): JsonResponse
    {
        return response()->json([]);
    }
}
