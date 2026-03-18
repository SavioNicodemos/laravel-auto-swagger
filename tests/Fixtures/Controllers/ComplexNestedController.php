<?php

namespace AutoSwagger\Docs\Tests\Fixtures\Controllers;

use AutoSwagger\Docs\Tests\Fixtures\Requests\ComplexNestedRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ComplexNestedController extends Controller
{
    /**
     * Search for available flights within a date range.
     *
     * Returns a list of flights matching the given criteria. You can scope the
     * search to a specific flight, route, or carrier — but only one at a time.
     * Each result includes nested segments and, when available, the lowest fare.
     *
     * @Request({
     *     "tags": ["flights"]
     * })
     * @Response({
     *     "code": 200,
     *     "ref": "FlightSearchResult[]"
     * })
     * @Response({
     *     "code": 422,
     *     "description": "Validation error"
     * })
     */
    public function query(ComplexNestedRequest $request): JsonResponse
    {
        return response()->json([]);
    }
}
