<?php

namespace App\Swagger\Schemas;

/**
 * @Schema({
 *     "required": ["origin_code", "destination_code", "departure_at", "arrival_at", "duration_minutes"]
 * })
 */
class FlightSegment
{
    static string $origin_code = 'GRU';

    static string $destination_code = 'MIA';

    static string $departure_at = '2024-06-15T10:00:00Z';

    static string $arrival_at = '2024-06-15T16:30:00Z';

    static int $duration_minutes = 390;

    static ?string $aircraft_model = 'Boeing 787';
}
