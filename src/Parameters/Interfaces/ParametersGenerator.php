<?php

namespace AutoSwagger\Docs\Parameters\Interfaces;

/**
 * Interface ParametersGenerator
 * @package AutoSwagger\Docs\Parameters\Interfaces
 */
interface ParametersGenerator
{

    /**
     * Get list of parameters
     * @return array
     */
    public function getParameters(): array;

    /**
     * Get parameter location
     * @return string
     */
    public function getParameterLocation(): string;
}
