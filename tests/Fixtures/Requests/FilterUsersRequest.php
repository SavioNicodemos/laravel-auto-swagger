<?php

namespace AutoSwagger\Docs\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_id'  => 'required|string|swagger_hidden',
            'address'      => 'nullable|array|swagger_hidden',
            'address.city' => 'string',
            'address.zip'  => 'string',
            'tag_ids'      => 'nullable|array',
            'tag_ids.*'    => 'integer',
        ];
    }
}
