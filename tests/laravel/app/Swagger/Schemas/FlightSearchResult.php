<?php

namespace App\Swagger\Schemas;

/**
 * @Schema({
 *     "required": ["flight_id", "flight_number", "is_available", "scheduled_at", "is_cancelled", "segments", "links"]
 * })
 */
class FlightSearchResult
{
    static int $flight_id = 1;

    static string $flight_number = 'LA4050';

    static bool $is_available = true;

    static ?string $session_token = 'TOKEN123';

    static ?string $currency_code = 'USD';

    static string $scheduled_at = '2024-06-15T10:00:00Z';

    static bool $is_cancelled = false;

    static string $cancelled_from = '2024-06-15';

    static ?int $display_priority = 1;

    /**
     * @Property({
     *   "ref": "FlightDetails",
     *   "nullable": true
     * })
     */
    static $flight_info;

    /**
     * @Property({
     *   "ref": "FlightSegment[]"
     * })
     */
    public $segments;

    /**
     * @Property({
     *   "ref": "LowestFare",
     *   "nullable": true
     * })
     */
    public $lowest_fare;

    /**
     * @Property({
     *   "ref": "FlightLinks"
     * })
     */
    public $links;
}
