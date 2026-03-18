<?php

namespace AutoSwagger\Docs\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComplexNestedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'departure_date' => [
                'required',
                'date',
                'after_or_equal:today',
                'swagger_description:Outbound flight date. Format: yyyy-mm-dd',
                'swagger_example:2024-06-15',
            ],
            'return_date' => [
                'required',
                'date',
                'after:departure_date',
                'swagger_description:Return flight date. Format: yyyy-mm-dd',
                'swagger_example:2024-06-22',
            ],
            'cabin_class' => [
                'required',
                'string',
                'in:economy,premium_economy,business,first',
                'swagger_description:Desired cabin class for all passengers.',
                'swagger_example:economy',
            ],

            'flight_id' => [
                'integer',
                'nullable',
                'swagger_description:Search for a specific flight by its ID.',
                'swagger_example:101',
            ],
            'route_id' => [
                'integer',
                'nullable',
                'swagger_description:Search all flights on a given route.',
                'swagger_example:55',
            ],
            'carrier_id' => [
                'integer',
                'nullable',
                'swagger_description:Search all flights operated by a specific carrier.',
                'swagger_example:9',
            ],

            'passengers' => [
                'required',
                'array',
            ],
            'passengers.*.count' => [
                'integer',
                'required',
                'min:1',
                'swagger_description:Number of passengers in this group.',
                'swagger_example:2',
            ],
            'passengers.*.baggage' => [
                'array',
                'nullable',
            ],
            'passengers.*.baggage.*.weight' => [
                'integer',
                'required_with:passengers.*.baggage',
                'swagger_description:Weight in kg for each checked baggage piece.',
                'swagger_example:23',
            ],

            'filters.airline_ids' => [
                'array',
                'swagger_description:Restrict results to flights from these airline IDs.',
            ],
            'filters.airline_ids.*' => [
                'integer',
                'required',
            ],
            'filters.airport_ids' => [
                'array',
                'swagger_description:Restrict results to flights through these airport IDs.',
            ],
            'filters.airport_ids.*' => [
                'integer',
                'required',
            ],
            'filters.alliance_ids' => [
                'array',
                'swagger_description:Restrict results to airlines belonging to these alliance IDs.',
            ],
            'filters.alliance_ids.*' => [
                'integer',
                'required',
            ],
            'filters.stopover_ids' => [
                'array',
                'swagger_description:Restrict results to flights with stopovers at these location IDs.',
            ],
            'filters.stopover_ids.*' => [
                'integer',
                'required',
            ],
            'filters.aircraft_ids' => [
                'array',
                'swagger_description:Restrict results to flights operated by these aircraft type IDs.',
            ],
            'filters.aircraft_ids.*' => [
                'integer',
                'required',
            ],
        ];
    }
}
