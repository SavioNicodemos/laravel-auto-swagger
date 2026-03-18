<?php

namespace AutoSwagger\Docs\Tests\Fixtures\Controllers;

use AutoSwagger\Docs\Tests\Fixtures\Requests\CreateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class FakeController extends Controller
{
    /**
     * List all users.
     */
    public function index(): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * Show a user.
     *
     * @param int $id
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(['id' => $id]);
    }

    /**
     * Create a new user.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        return response()->json([], 201);
    }
}
