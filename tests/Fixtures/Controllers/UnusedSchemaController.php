<?php

namespace AutoSwagger\Docs\Tests\Fixtures\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class UnusedSchemaController extends Controller
{
    /**
     * Get an item.
     *
     * @Response({"code": 200, "description": "OK", "ref": "UnusedSchema"})
     */
    public function index(): JsonResponse
    {
        return response()->json([]);
    }
}
