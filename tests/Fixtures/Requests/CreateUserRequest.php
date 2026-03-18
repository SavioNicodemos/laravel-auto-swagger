<?php

namespace AutoSwagger\Docs\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'email' => 'required|string|email',
            'age'   => 'nullable|integer|min:0|max:120',
            'role'  => 'required|in:admin,user,moderator',
        ];
    }
}
