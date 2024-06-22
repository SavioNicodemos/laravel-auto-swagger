<?php

namespace AutoSwagger\Docs\Definitions;

use AutoSwagger\Docs\Helpers\AnnotationsHelper;
use AutoSwagger\Docs\Helpers\ConfigHelper;
use AutoSwagger\Docs\Helpers\ConversionHelper;
use AutoSwagger\Docs\Helpers\SwaggerHelper;
use Doctrine\DBAL\Types\Type;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionProperty;

/**
 * Class DefinitionGenerator
 * @package AutoSwagger\Docs\Definitions
 */
class DefinitionGenerator
{
    protected AnnotationsHelper $annotationsHelper;

    /**
     * array of models
     * @var array
     */
    protected array $models = [];

    /**
     * array of custom schemas
     * @var array
     */
    protected array $customSchemas = [];


    /**
     * DefinitionGenerator constructor.
     */
    public function __construct(array $ignoredModels = [])
    {
        if (!ConfigHelper::shouldIgnoreAllModels()) {
            $this->models = collect(File::allFiles(app_path()))
                ->map(function ($item) {
                    /**
                     * @var object
                     */
                    $containerInstance = Container::getInstance();
                    $path = $item->getRelativePathName();
                    $class = sprintf(
                        '\%s%s',
                        $containerInstance->getNamespace(),
                        strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                    );

                    return $class;
                })
                ->filter(function ($class) {
                    $valid = false;

                    if (class_exists($class)) {
                        $reflection = new ReflectionClass($class);
                        $valid = $reflection->isSubclassOf(Model::class) &&
                            !$reflection->isAbstract();
                    }

                    return $valid;
                })
                ->diff($ignoredModels)
                ->values()
                ->toArray();
        }

        if (is_dir(config('swagger.schemas'))) {
            $this->customSchemas = collect(File::allFiles(config('swagger.schemas')))
                ->map(function ($item) {
                    /**
                     * @var object
                     */
                    $containerInstance = Container::getInstance();
                    $path = $item->getRelativePathName();

                    // Get the class namespace
                    $schemasDir = realpath(config('swagger.schemas'));
                    $relativeDir = str_replace(app_path() . DIRECTORY_SEPARATOR, '', $schemasDir);
                    $namespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativeDir);

                    $class = sprintf(
                        '\%s%s\%s',
                        $containerInstance->getNamespace(),
                        $namespace,
                        strtr(substr($path, 0, strrpos($path, '.')), '/', '\\')
                    );

                    return $class;
                })
                ->filter(function ($class) {
                    $valid = false;

                    if (class_exists($class)) {
                        $reflection = new ReflectionClass($class);
                        $valid = !$reflection->isAbstract();
                    }

                    return $valid;
                })
                ->values()
                ->toArray();
        }

        $this->annotationsHelper = new AnnotationsHelper();
    }

    /**
     * Get array of all schemas that was detected in the application
     */
    function getDefinedSchemas(): array
    {
        return array_merge(
            $this->models,
            $this->customSchemas
        );
    }

    /**
     * Generate definitions information
     * @return array
     */
    function generateSchemas(): object
    {
        // TODO: Add support for Laravel Resources
        $modelSchemas = $this->generateSchemasFromModels();

        $customSchemas = $this->generateSchemasFromCustomClasses();

        return (object) array_merge(
            $modelSchemas,
            $customSchemas
        );
    }

    function generateSchemasFromCustomClasses(): array
    {
        $schemas = [];

        foreach ($this->customSchemas as $schema) {
            $obj = new $schema();

            $reflection = new ReflectionClass($obj);

            $properties = [];
            $required = [];

            $classComment = $reflection->getDocComment();

            $schemaAnnotation = $this->annotationsHelper->getCommentProperties(
                $classComment,
                'Schema'
            );

            $properties = collect($reflection->getProperties())
                ->mapWithKeys(function (ReflectionProperty $property) {
                    $propertyAnnotation = $this->annotationsHelper->getCommentProperties(
                        $property->getDocComment(),
                        'Property'
                    );
                    $meta = $propertyAnnotation['meta'];

                    if (isset($meta['raw'])) {
                        return [$property->getName() => $meta['raw']];
                    }

                    $data = $this->createBaseData($property, $propertyAnnotation['summary']);

                    $this->handleSwaggerNativeKeys($data, $meta);
                    $this->handleArrayType($data, $meta);
                    $this->handleRef($data, $meta);

                    SwaggerHelper::addExampleKey($data);

                    return [$property->getName() => $data];
                })
                ->toArray();

            $required = collect($schemaAnnotation['meta']['required'] ?? [])
                ->filter(function ($item) use ($properties) {
                    return array_key_exists($item, $properties);
                })
                ->values()
                ->toArray();

            $definition = [
                'type' => 'object',
                'properties' => (object) $properties,
            ];

            if (!empty($required)) {
                $definition['required'] = $required;
            }

            $schemas[$this->getClassName($obj)] = $definition;
        }

        return $schemas;
    }

    private function createBaseData(ReflectionProperty $property, string $description): array
    {
        $propertyType = $property->getType();
        $typeName = $propertyType ? $propertyType->getName() : 'string';

        $data = [
            'type' => ConversionHelper::phpTypeToSwaggerType($typeName),
            'description' => $description,
        ];

        try {
            $propertyValue = $property->isStatic() ? $property->getValue() : U_UNDEFINED_VARIABLE;
            if ($propertyValue !== U_UNDEFINED_VARIABLE) {
                $data['example'] = $propertyValue;
            }
        } catch (\Error $e) {
            // We are ignoring this exception because it is not relevant. 
            // The only thing we need to know is that 'example' key will just
            // not be set and this is expected in case of this error.
        }

        if ($propertyType && $propertyType->allowsNull()) {
            $data['nullable'] = true;
        }

        return $data;
    }

    private function handleRef(array &$data, array $meta): void
    {
        if (!isset($meta['ref'])) return;

        [$arrayOfSchemas, $schemaBuilded] =
            $this->annotationsHelper->parsedSchemas($meta['ref']);

        if ($arrayOfSchemas) {
            $data['type'] = $arrayOfSchemas['type'];
            $data['items'] = $arrayOfSchemas['items'];
        } elseif ($schemaBuilded) {
            $data['type'] = $schemaBuilded['type'];
            $data['properties'] = $schemaBuilded['properties'];
            $data['required'] = $schemaBuilded['required'];
        } else {
            $data = []; // We need to reset the data when add $ref
            $data['$ref'] = '#/components/schemas/' . $meta['ref'];
        }
    }

    private function handleArrayType(array &$data, array $meta): void
    {
        if ($data['type'] !== 'array') return;

        $items = [
            'type' => isset($meta['arrayOf']) ? $meta['arrayOf'] : 'string',
        ];
        SwaggerHelper::addExampleKey($items);

        $data['items'] = $items;
    }

    private function handleSwaggerNativeKeys(array &$data, array $meta): void
    {
        $nativeKeys = [
            'type',
            'description',
            'example',
            'nullable',
            'format',
        ];

        foreach ($nativeKeys as $key) {
            if (isset($meta[$key])) {
                $data[$key] = $meta[$key];
            }
        }
    }

    function generateSchemasFromModels(): array
    {
        $modelSchemas = [];

        foreach ($this->models as $model) {
            /** @var Model $model */
            $obj = new $model();

            if (!($obj instanceof Model)) {
                continue;
            }

            $reflection = new ReflectionClass($obj);

            $appends = $reflection->getProperty('appends');
            $appends->setAccessible(true);

            $relations = collect($reflection->getMethods())
                ->filter(
                    fn ($method) => !empty($method->getReturnType()) &&
                        str_contains(
                            $method->getReturnType(),
                            \Illuminate\Database\Eloquent\Relations::class
                        )
                )
                ->pluck('name')
                ->all();

            $table = $obj->getTable();
            try {
                $list = Schema::connection($obj->getConnectionName())
                    ->getColumnListing($table);
            } catch (\Exception $e) {
                $message = "[AutoSwagger\Docs] Table $table not found while parsing $model";
                Log::warning($message);
                dump($message);
                continue;
            }
            $list = array_diff($list, $obj->getHidden());

            $properties = [];
            $required = [];

            /**
             * @var \Illuminate\Database\Connection
             */
            $conn = $obj->getConnection();
            $prefix = $conn->getTablePrefix();

            if ($prefix !== '') {
                $table = $prefix . $table;
            }

            foreach ($list as $item) {
                try {
                    /**
                     * @var \Doctrine\DBAL\Schema\Column
                     */
                    $column = $conn->getDoctrineColumn($table, $item);
                } catch (\Exception $e) {
                    $message = "[AutoSwagger\Docs] Column $item not found while parsing $model";
                    Log::warning($message);
                    dump($message);
                    continue;
                }

                $data = ConversionHelper::DBalTypeToSwaggerType(
                    Type::getTypeRegistry()->lookupName($column->getType())
                );

                if ($data['type'] == 'string' && ($len = $column->getLength())) {
                    $data['description'] .= "($len)";
                }

                $description = $column->getComment();
                if (!is_null($description)) {
                    $data['description'] .= ": $description";
                }

                $default = $column->getDefault();
                if (!is_null($default)) {
                    $data['default'] = $default;
                }

                $data['nullable'] = !$column->getNotNull();

                SwaggerHelper::addExampleKey($data);

                $properties[$item] = $data;

                if ($column->getNotNull()) {
                    $required[] = $item;
                }
            }

            foreach ($relations as $relationName) {
                $relatedClass = get_class($obj->{$relationName}()->getRelated());
                $refObject = [
                    '$ref' => '#/components/schemas/' . last(explode('\\', $relatedClass)),
                ];

                $resultsClass = get_class((object) ($obj->{$relationName}()->getResults()));

                if (str_contains($resultsClass, \Illuminate\Database\Eloquent\Collection::class)) {
                    $properties[$relationName] = [
                        'type' => 'array',
                        'items' => $refObject
                    ];
                } else {
                    $properties[$relationName] = $refObject;
                }
            }

            foreach ($appends->getValue($obj) as $item) {
                $methodName = 'get' . ucfirst(Str::camel($item)) . 'Attribute';
                if (!$reflection->hasMethod($methodName)) {
                    Log::warning("[AutoSwagger\Docs] Method $model::$methodName not found while parsing '$item' attribute");
                    continue;
                }
                $reflectionMethod = $reflection->getMethod($methodName);
                $returnType = $reflectionMethod->getReturnType();

                $data = [];

                // A schema without a type matches any data type – numbers, strings, objects, and so on.
                if ($reflectionMethod->hasReturnType()) {
                    $type = $returnType->getName();

                    if (Str::contains($type, '\\')) {
                        $data = [
                            '$ref' => '#/components/schemas/' . last(explode('\\', $type)),
                        ];
                    } else {
                        $data['type'] = $type;
                        SwaggerHelper::addExampleKey($data);
                    }
                }

                $properties[$item] = $data;

                if ($returnType && false == $returnType->allowsNull()) {
                    $required[] = $item;
                }
            }

            $definition = [
                'type' => 'object',
                'properties' => (object) $properties,
            ];

            if (!empty($required)) {
                $definition['required'] = $required;
            }

            $modelSchemas[$this->getClassName($obj)] = $definition;
        }

        return $modelSchemas;
    }

    /**
     * Get model name
     * @param object $class
     * @return string The real class name without namespace
     */
    private function getClassName(object $class): string
    {
        return last(explode('\\', get_class($class)));
    }
}
