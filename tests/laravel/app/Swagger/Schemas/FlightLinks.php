<?php

namespace App\Swagger\Schemas;

/**
 * @Schema({
 *     "required": ["self", "book"]
 * })
 */
class FlightLinks
{
    static string $self = 'https://api.example.com/v1/flights/1';

    static string $book = 'https://api.example.com/v1/bookings';
}
