<?php

namespace App\Swagger\Schemas;

/**
 * @Schema({
 *     "required": ["origin_code", "destination_code", "carrier_name"]
 * })
 */
class FlightDetails
{
    static string $origin_code = 'GRU';

    static string $destination_code = 'LHR';

    static string $carrier_name = 'LATAM Airlines';

    static ?string $alliance = 'oneworld';

    static int $stops = 0;
}
