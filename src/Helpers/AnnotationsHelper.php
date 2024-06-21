<?php

namespace AutoSwagger\Docs\Helpers;

use AutoSwagger\Docs\Exceptions\SchemaBuilderNotFound;
use AutoSwagger\Docs\Exceptions\AnnotationException;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlockFactory;
use Illuminate\Support\Arr;
use ReflectionException;
use ReflectionClass;

class AnnotationsHelper
{
    /**
     * Parse raw documentation tag
     * @param Generic|Tag $rawTag
     * @return array
     */
    public function parseRawDocumentationTag($rawTag): array
    {
        $comment = trim((string) $rawTag, '()');

        if (!$comment) {
            return [];
        }

        $json = json_decode($comment, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AnnotationException($comment);
        }

        return $json;
    }

    public function getCommentProperties(?string $classComment, string $targetTag): array
    {
        $annotationObj = [
            'deprecated' => false,
            'summary' => '',
            'description' => '',
            'meta' => [],
        ];

        if (empty($classComment)) {
            return $annotationObj;
        }

        $parser = DocBlockFactory::createInstance();

        if (!empty($classComment)) {
            $parsedComment = $parser->create($classComment);

            Arr::set($annotationObj, 'deprecated', $parsedComment->hasTag('deprecated'));
            Arr::set($annotationObj, 'summary', $parsedComment->getSummary());
            Arr::set($annotationObj, 'description', (string) $parsedComment->getDescription());

            if ($parsedComment->hasTag($targetTag)) {
                $newTags = $parsedComment->getTagsByName($targetTag)[0];
                Arr::set($annotationObj, 'meta', $this->parseRawDocumentationTag($newTags));
            }
        }

        return $annotationObj;
    }

    public function parsedSchemas(string $refString, string $uri = ''): array
    {
        $arrayOfSchemas = null;
        $schemaBuilded = null;

        $cleanedString = str_replace(' ', '', $refString);

        [
            $isArrayOfSchemas,
            $usesSchemaBuilder,
        ] = $this->verifyAnnotationRefString($cleanedString);

        if ($isArrayOfSchemas) {
            $modelName = trim(Str::replaceLast('[]', '', $cleanedString));
            $ref = $this->toSwaggerSchemaPath($modelName);
            $items = [
                'type' => 'array',
                'items' => [
                    '$ref' => $ref
                ]
            ];
            $arrayOfSchemas = $items;
        }

        if ($usesSchemaBuilder) {
            $matches = $this->getSchemaBuilderMatches($cleanedString);

            $schema = $this->generateCustomResponseSchema(
                $matches[1],
                $matches[2],
                $uri
            );

            if (\count($schema) > 0) {
                $schemaBuilded = $schema;
            }
        }

        return [$arrayOfSchemas, $schemaBuilded];
    }

    public function verifyAnnotationRefString(string $refString): array
    {
        $cleanedString = str_replace(' ', '', $refString);

        return [
            $this->isAnArrayOfSchemas($cleanedString),
            $this->usesSchemaBuilder($cleanedString)
        ];
    }


    /**
     * Turn a model name to swagger path to that model or
     * return the same string if it's already a valide path
     * @param string $value
     * @return string
     */
    public function toSwaggerSchemaPath(string $value): string
    {
        if (!Str::startsWith($value, '#/components/schemas/')) {
            return "#/components/schemas/$value";
        }

        return $value;
    }

    /**
     * Read schemas builder config and call the matched one
     *
     * @throws SchemaBuilderNotFound
     * @throws ReflectionException
     */
    private function generateCustomResponseSchema(string $operation, string $model, string $uri = '')
    {
        $ref = $this->toSwaggerSchemaPath($model);
        $schemaBuilders = config('swagger.schema_builders');

        if (!Arr::has($schemaBuilders, $operation)) {
            throw new SchemaBuilderNotFound("SchemaBuilder `$operation` not found in `swagger.schema_builders` config file. Problem found trying to generate `$uri`");
        }

        try {
            $actionClass = new ReflectionClass($schemaBuilders[$operation]);
        } catch (Exception $e) {
            throw new ReflectionException(
                "The class for the SchemaBuilder `$operation` could not be reached. Please verify the class provided in `swagger.schema_builders` config file."
            );
        }

        try {
            return $actionClass->newInstanceWithoutConstructor()->build($ref, $uri);
        } catch (Exception $e) {
            throw new Exception("SchemaBuilder must implements AutoSwagger\Docs\Responses\SchemaBuilder interface");
        }
    }

    private function isAnArrayOfSchemas(string $value): bool
    {
        // It will get names like SchemaName[]
        return Str::endsWith($value, '[]');
    }

    private function usesSchemaBuilder(string $value): bool
    {
        // It will get names like SP(SchemaName)
        return preg_match("(([A-Za-z]{1,})\(([A-Za-z]{1,})\))", $value) === 1;
    }

    private function getSchemaBuilderMatches(string $value): array
    {
        $matches = [];

        preg_match("(([A-Za-z]{1,})\(([A-Za-z]{1,})\))", $value, $matches);

        return $matches;
    }
}
