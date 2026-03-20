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

    /**
     * @Property({
     *   "enum": ["economy", "business", "first"]
     * })
     */
    static string $cabin_class = 'economy';

    /**
     * @Property({
     *   "deprecated": true,
     *   "description": "Use cabin_class instead"
     * })
     */
    static string $class_of_service = 'economy';

    /**
     * @Property({
     *   "format": "date"
     * })
     */
    static string $scheduled_date = '2024-06-15';

    static ?bool $is_codeshare = false;

    /**
     * @Property({
     *   "arrayOf": "integer"
     * })
     */
    static array $stop_numbers;

    /**
     * @Property({
     *   "nullable": true
     * })
     */
    static string $booking_class = 'Y';

    /**
     * @Property({
     *   "example": "LA"
     * })
     */
    static string $carrier_code = 'XX';

    /**
     * @Property({
     *   "type": "string"
     * })
     */
    static int $seat_number = 15;

    /**
     * @Property({
     *   "raw": {"type": "string", "format": "uri"}
     * })
     */
    static string $booking_url = 'https://example.com';
}
