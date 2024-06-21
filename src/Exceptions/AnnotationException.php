<?php

namespace AutoSwagger\Docs\Exceptions;

/**
 * Class AnnotationException
 * @package AutoSwagger\Docs\Exceptions
 */
class AnnotationException extends AutoSwaggerException
{
    public function __construct(
        string $wrongNotation = "",
        int $code = 0,
        \Throwable $previous = null
    ) {
        $message = "Failed to parse annotation:" . PHP_EOL . $wrongNotation;
        parent::__construct($message, $code, $previous);
    }
}
