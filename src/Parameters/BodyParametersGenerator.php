<?php

namespace AutoSwagger\Docs\Parameters;

use Exception;
use Illuminate\Support\Arr;
use AutoSwagger\Docs\Parameters\Traits\GeneratesFromRules;
use AutoSwagger\Docs\Parameters\Interfaces\ParametersGenerator;
use TypeError;

/**
 * Class BodyParametersGenerator
 * @package AutoSwagger\Docs\Parameters
 */
class BodyParametersGenerator implements ParametersGenerator
{
    use GeneratesFromRules;

    /**
     * Rules array
     * @var array
     */
    protected array $rules;

    /**
     * Parameters location
     * @var string
     */
    protected string $location = 'body';

    /**
     * BodyParametersGenerator constructor.
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Get parameters
     * @return array[]
     */
    public function getParameters(): array
    {
        $required = [];
        $properties = [];

        $schema = [];

        foreach ($this->rules as $parameter => $rule) {
            try {
                $parameterRules = $this->splitRules($rule);
                $nameTokens = explode('.', $parameter);
                $this->addToProperties($properties,  $nameTokens, $parameterRules);

                if ($this->isParameterRequired($parameterRules)) {
                    $required[] = $parameter;
                }
            } catch (TypeError $e) {
                $ruleStr = json_encode($rule);
                throw new Exception("Rule `$parameter => $ruleStr` is not well formatted", 0, $e);
            }
        }

        if (\count($required) > 0) {
            Arr::set($schema, 'required', $required);
        }

        $this->cleanUpProperties($properties);
        $this->ensureNotUselessArrayOfArrays($properties);

        Arr::set($schema, 'properties', $properties);

        $mediaType = 'application/json'; // or  "application/x-www-form-urlencoded"
        foreach ($properties as $propKey => $prop) {
            if (isset($prop['format']) && $prop['format'] == 'binary') {
                $mediaType = 'multipart/form-data';
                $schema['type'] = 'object';
            }
            if (isset($prop['items']) && is_array($prop['items'])) {
                foreach ($prop['items'] as $item) {
                    if (isset($item['format']) && $item['format'] == 'binary') {
                        $mediaType = 'multipart/form-data';
                        $schema['properties'][$propKey]['items'] = $item;
                        $schema['type'] = 'object';
                    }
                }
            }
        }


        return [
            'content' =>  [
                $mediaType  =>  [
                    'schema' =>  $schema
                ]
            ]
        ];
    }

    /**
     * Clean up properties
     * @param array $properties
     */
    protected function cleanUpProperties(array &$properties): void
    {
        foreach ($properties as $key => $property) {
            if (!isset($property['items'])) continue;

            if (empty($property['items'])) {
                $properties[$key]['items'] = [
                    'type' => 'string'
                ];
                continue;
            }

            if (!Arr::isAssoc($property['items'])) {
                $properties[$key]['items'] = $property['items'][0];
                $property['items'] = $property['items'][0];
            }

            if (isset($property['items']['type']) && $property['items']['type'] === 'object') {
                $this->cleanUpProperties($properties[$key]['items']['properties']);
            }
        }
    }

    protected function ensureNotUselessArrayOfArrays(array &$properties): void
    {
        foreach ($properties as $key => &$value) {
            if (is_array($value)) {
                // Check if this is the specific pattern we want to fix:
                // 'items' => [['type' => '...']]
                if ($key === 'items' && $this->isUselessArrayOfArrays($value)) {
                    // Replace the array of arrays with just the inner object
                    $value = $value[0];
                }

                // Recursively process nested arrays
                $this->ensureNotUselessArrayOfArrays($value);
            }
        }
    }

    /**
     * Check if the value matches the specific useless array-of-arrays pattern:
     * - Must be an indexed array with exactly one element
     * - That element must be an associative array
     * - That associative array must contain only a 'type' key
     */
    private function isUselessArrayOfArrays(array $value): bool
    {
        // Must be an indexed array with exactly one element
        if (!Arr::isList($value) || count($value) !== 1) {
            return false;
        }

        $firstElement = $value[0];

        // First element must be an associative array
        if (!is_array($firstElement) || Arr::isList($firstElement)) {
            return false;
        }

        // Must contain only a 'type' key
        return count($firstElement) === 1 && array_key_exists('type', $firstElement);
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
     * Add data to properties array
     * @param array $properties
     * @param array $nameTokens
     * @param array $rules
     * @param array $requiredArray
     */
    protected function addToProperties(
        array &$properties,
        array $nameTokens,
        array $rules,
        array &$requiredArray = []
    ): void {
        if (\count($nameTokens) === 0) {
            return;
        }

        $name = array_shift($nameTokens);

        if ($this->isParameterRequired($rules)) {
            $requiredArray[] = $name;
        }

        if (!empty($nameTokens)) {
            $type = $this->getNestedParameterType($nameTokens);
        } else {
            $type = $this->getParameterType($rules);
        }

        if ($name === '*') {
            $name = 0;
        }

        if (!Arr::has($properties, $name)) {
            $propertyObject = $this->createNewPropertyObject($type, $rules);
            if ($type === 'object' && !empty($nameTokens)) {
                $propertyObject['properties'] = [];
                unset($propertyObject['items']);
            }
            Arr::set($properties, $name, $propertyObject);
            $extra = $this->getParameterExtra($type, $rules);

            $this->getCustomSwaggerRules($extra, $rules, $type);

            foreach ($extra as $key => $value) {
                Arr::set($properties, $name . '.' . $key, $value);
            }
        } else {
            Arr::set($properties, $name . '.type', $type);
            if ($type === 'object' && !empty($nameTokens)) {
                if (!isset($properties[$name]['properties'])) {
                    Arr::set($properties, $name . '.properties', []);
                }
                Arr::forget($properties[$name], 'items');
            }
        }

        if ($type === 'array') {
            $this->addToProperties($properties[$name]['items'], $nameTokens, $rules);
        } else if ($type === 'object' && isset($properties[$name]['properties'])) {
            $localRequired = [];
            $this->addToProperties($properties[$name]['properties'], $nameTokens, $rules, $localRequired);
            if (count($localRequired) > 0) {
                $properties[$name]['required'] = array_merge(
                    $properties[$name]['required'] ?? [],
                    $localRequired
                );
            }
        }
    }

    /**
     * Add extra params from swagger_ rules
     */
    protected function getCustomSwaggerRules(array &$extra, array $rules, string $type)
    {
        if (in_array($type, ['object', 'array']) && !in_array($type, $rules)) {
            return;
        }

        $processValue = function ($value) use ($type) {
            if ($value !== null) {
                if ($type === 'boolean' && $value === 'false') {
                    $value = '';
                }
                settype($value, $type);
            }
            return $value;
        };

        $universalParams = array_filter([
            'default' => $processValue($this->getDefaultValue($rules) ?: null),
            'example' => $processValue($this->getExampleValue($rules) ?: null),
            'description' => $this->getDescription($rules) ?: null,
        ], fn($value) => $value !== null);

        $extra = array_merge($extra, $universalParams);
    }

    /**
     * Get nested parameter type
     * @param array $nameTokens
     * @return string
     */
    protected function getNestedParameterType(array $nameTokens): string
    {
        if (current($nameTokens) === '*') {
            return 'array';
        }
        return 'object';
    }

    /**
     * Create new property object
     * @param string $type
     * @param array $rules
     * @return string[]
     */
    protected function createNewPropertyObject(string $type, array $rules): array
    {
        $propertyObject = [
            'type' =>  $type,
        ];

        if ($enums = $this->getEnumValues($rules)) {
            Arr::set($propertyObject, 'enum', $enums);
        }

        if ($type === 'array') {
            Arr::set($propertyObject, 'items', []);
        } else if ($type === 'object') {
            Arr::set($propertyObject, 'properties', []);
        }

        return $propertyObject;
    }
}
