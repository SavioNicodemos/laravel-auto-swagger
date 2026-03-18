<?php

namespace App\Swagger\Schemas;

/**
 * @Schema({
 *     "required": ["amount", "currency", "is_refundable"]
 * })
 */
class LowestFare
{
    static int $amount = 89900;

    static string $currency = 'USD';

    static bool $is_refundable = false;
}
