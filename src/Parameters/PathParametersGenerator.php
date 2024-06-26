<?php

namespace AutoSwagger\Docs\Parameters;

use Illuminate\Support\Str;
use AutoSwagger\Docs\Parameters\Interfaces\ParametersGenerator;

/**
 * Class PathParametersGenerator
 * @package AutoSwagger\Docs\Parameters
 */
class PathParametersGenerator implements ParametersGenerator
{

    /**
     * Path URI
     * @var string
     */
    protected string $uri;

    /**
     * Parameters location
     * @var string
     */
    protected string $location = 'path';

    /**
     * PathParametersGenerator constructor.
     * @param string $uri
     */
    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function getParameters(): array
    {
        $parameters = [];
        $pathVariables = $this->getVariablesFromUri();

        foreach ($pathVariables as $variable) {
            $parameters[] = [
                'name'          =>  strip_optional_char($variable),
                'in'            =>  $this->getParameterLocation(),
                'required'      =>  true,
                'description'   =>  '',
                'schema'        =>  [
                    'type'      =>  'string',
                ]
            ];
        }

        return $parameters;
    }

    /**
     * @inheritDoc
     * @return string
     */
    public function getParameterLocation(): string
    {
        return $this->location;
    }

    /**
     * Get path variables from URI
     * @return array
     */
    private function getVariablesFromUri(): array
    {
        preg_match_all('/{(\w+\??)}/', $this->uri, $pathVariables);
        return $pathVariables[1];
    }

    /**
     * Get variable type from string
     * @param string $string
     * @return string
     */
    private function getTypeFromString(string $string): string
    {
        return gettype($this->guessVariableType($string));
    }

    /**
     * Guess variable type
     * @param string $string
     * @return bool|float|int|string
     */
    private function guessVariableType(string $string)
    {
        $string = trim($string);
        if (empty($string)) {
            return '';
        }
        if (!preg_match('/[^0-9.]+/', $string)) {
            if (preg_match('/[.]+/', $string)) {
                return (float) $string;
            }
            return (int) $string;
        }
        if ($string === 'true') {
            return (bool) true;
        }
        if ($string === 'false') {
            return (bool) false;
        }
        return (string) $string;
    }
}
