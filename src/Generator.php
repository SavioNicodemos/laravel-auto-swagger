<?php

namespace AutoSwagger\Docs;

use AutoSwagger\Docs\Helpers\PathParamsHelper;
use AutoSwagger\Docs\Helpers\RouteHelper;
use AutoSwagger\Docs\Helpers\SwaggerSecurityHelper;
use Exception;
use AutoSwagger\Docs\Exceptions\InvalidDefinitionException;
use ReflectionMethod;
use ReflectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Route;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Http\FormRequest;
use phpDocumentor\Reflection\DocBlockFactory;
use AutoSwagger\Docs\Definitions\DefinitionGenerator;
use AutoSwagger\Docs\Exceptions\AnnotationException;
use AutoSwagger\Docs\Exceptions\InvalidAuthenticationFlow;
use AutoSwagger\Docs\Exceptions\SchemaBuilderNotFound;
use AutoSwagger\Docs\Helpers\AnnotationsHelper;
use AutoSwagger\Docs\Helpers\ConfigHelper;
use function count;

/**
 * Class Generator
 * @package AutoSwagger\Docs
 */
class Generator
{
    /**
     * Configuration repository instance
     */
    protected Repository $configuration;

    /**
     * Route filter value
     */
    protected ?string $routeFilter;

    /**
     * Parser instance
     */
    protected DocBlockFactory $parser;

    /**
     * DefinitionGenerator instance
     */
    protected DefinitionGenerator $definitionGenerator;

    /**
     * Indicates whether we have security definitions
     */
    protected bool $hasSecurityDefinitions;

    /**
     * List of ignored routes and methods
     */
    protected array $ignored;

    /**
     * Items to be appended to documentation
     */
    protected array $append;

    protected AnnotationsHelper $annotationsHelper;

    protected array $routeRenaming = [];

    /**
     * Generator constructor.
     */
    public function __construct(Repository $config, ?string $routeFilter = null)
    {
        $this->configuration = $config;
        $this->parser = DocBlockFactory::createInstance();
        $this->hasSecurityDefinitions = false;
        $this->ignored = $this->fromConfig('ignored', []);
        $this->append = $this->fromConfig('append', []);

        $this->annotationsHelper = new AnnotationsHelper();

        $apiBasePath = $routeFilter ?: $this->fromConfig('api_base_path');
        if (is_string($apiBasePath) && Str::endsWith($apiBasePath, '/')) {
            $apiBasePath = substr($apiBasePath, 0, -1);
        }
        $this->routeFilter = $apiBasePath;

        if (!ConfigHelper::shouldIgnoreAllModels()) {
            try {
                DB::connection()
                    ->getDoctrineConnection()
                    ->getDatabasePlatform()
                    ->registerDoctrineTypeMapping('enum', 'string');
            } catch (Exception $e) {
                Log::error('[AutoSwagger\Docs] Could not register enum type as string because of connection error.');
            }
        }
    }

    /**
     * Generate documentation
     *
     * @throws InvalidAuthenticationFlow
     * @throws InvalidDefinitionException
     */
    public function generate(): array
    {
        $documentation = $this->generateBaseInformation();
        $applicationRoutes = $this->getApplicationRoutes();

        $this->definitionGenerator = new DefinitionGenerator(Arr::get($this->ignored, 'models'));
        Arr::set($documentation, 'components.schemas', $this->definitionGenerator->generateSchemas());

        if ($this->fromConfig('parse.security', false) /*&& $this->hasOAuthRoutes($applicationRoutes)*/) {
            Arr::set(
                $documentation,
                'components.securitySchemes',
                SwaggerSecurityHelper::generateSecurityDefinitions()
            );
            $this->hasSecurityDefinitions = true;
        }

        $ignoredRoutes = Arr::get($this->ignored, 'routes', []);
        foreach ($applicationRoutes as $route) {
            if (RouteHelper::isFilteredRoute($route, $this->routeFilter, $ignoredRoutes)) {
                continue;
            }

            $uri = RouteHelper::getRelativePathFromUri($route->uri(), $this->routeFilter);
            if ($uri === null) {
                continue;
            }
            $pathKey = 'paths.'.$uri;

            if (!Arr::has($documentation, $pathKey)) {
                Arr::set($documentation, $pathKey, []);
            }

            $tagFromPrefix = explode('/', $uri)[0] ?: explode('/', $uri)[1] ?? null;

            foreach ($route->methods() as $method) {
                if (in_array($method, Arr::get($this->ignored, 'methods'))) {
                    continue;
                }
                $methodKey = $pathKey.'.'.$method;
                Arr::set($documentation, $methodKey, $this->generatePath($route, $method, $tagFromPrefix));
            }
        }

        PathParamsHelper::renamePaths($documentation, $this->routeRenaming);

        return $documentation;
    }

    /**
     * Generate base information
     */
    private function generateBaseInformation(): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $this->fromConfig('title'),
                'description' => $this->fromConfig('description'),
                'version' => $this->fromConfig('version')
            ],
            'servers' => $this->generateServersList(),
            'paths' => [],
            'tags' => $this->fromConfig('tags'),
        ];
    }

    /**
     * Get list of application routes
     * @return array|DataObjects\Route[]
     */
    private function getApplicationRoutes(): array
    {
        return array_map(function (Route $route): DataObjects\Route {
            return new DataObjects\Route($route);
        }, app('router')->getRoutes()->getRoutes());
    }

    /**
     * Get value from configuration
     * @param  string  $key
     * @param  mixed  $default
     * @return array|mixed
     */
    private function fromConfig(string $key, $default = null)
    {
        return $this->configuration->get('swagger.'.$key, $default);
    }

    /**
     * Generate servers list from configuration
     */
    private function generateServersList(): array
    {
        $rawServers = $this->fromConfig('servers');
        $servers = [];

        foreach ($rawServers as $index => $server) {
            if (is_array($server)) {
                $url = Arr::get($server, 'url');
                $description = Arr::get($server, 'description');
                if ($url) {
                    $servers[] = [
                        'url' => $url,
                        'description' => $description ?: sprintf('%s Server #%d', $this->fromConfig('title'),
                            $index + 1)
                    ];
                }
            } else {
                $servers[] = [
                    'url' => $server,
                    'description' => sprintf('%s Server #%d', $this->fromConfig('title'), $index + 1)
                ];
            }
        }

        if (count($servers) === 0) {
            $servers[] = [
                'url' => config('app.url'),
                'description' => config('app.name').' Main Server'
            ];
        }

        return $servers;
    }

    /**
     * Generate Path information
     */
    private function generatePath(DataObjects\Route $route, string $method, ?string $tagFromPrefix): array
    {
        $actionMethodInstance = $this->getActionMethodInstance($route);
        $documentationBlock = $actionMethodInstance ? ($actionMethodInstance->getDocComment() ?: '') : '';

        $documentation = $this->parseActionDocumentationBlock($documentationBlock, $route->uri());

        $this->addActionsParameters($documentation, $route, $method, $actionMethodInstance);

        $this->addConfigAppendItems($documentation);

        $relativeUri = RouteHelper::getRelativePathFromUri($route->uri(), $this->routeFilter);
        try {
            PathParamsHelper::checkForPathParamsChanges($documentation, $relativeUri, $this->routeRenaming);
        } catch (Exceptions\MultiplePathParamsException $e) {
            $message = sprintf(
                "[AutoSwagger/Docs] Route '%s' has 'pathParams' changes more than once. When editing path params, please change in only one method/place and the changes will be applied to all methods of the route.",
                $relativeUri);
            Log::warning($message, ['route' => $route->uri()]);
            dump($message);
        }

        if ($this->hasSecurityDefinitions) {
            $this->addActionScopes($documentation, $route);
        }

        if (!Arr::has($documentation, 'tags') || count(Arr::get($documentation, 'tags')) == 0) {

            switch ($this->fromConfig('default_tags_generation_strategy')) {
                case 'controller':
                    $this->addTagsFromControllerName($documentation, $actionMethodInstance);
                    break;
                case 'prefix':
                    $tagFromPrefix && Arr::set($documentation, 'tags', [$tagFromPrefix]);
                    break;
                default: // Do nothing, all operation will be in one default tag
            }
        }

        return $documentation;
    }

    private function addTagsFromControllerName(array &$documentation, ?ReflectionMethod $actionMethodInstance): void
    {
        if ($actionMethodInstance == null) {
            return;
        }
        $classInstance = $actionMethodInstance->getDeclaringClass();
        $className = $classInstance ? $classInstance->getShortName() : null;
        if ($className) {
            $tagName = ucwords(implode(' ', preg_split('/(?=[A-Z])/', $className))); // convert camel case to words
            $tagName = str_replace('Controller', '', $tagName);
            Arr::set($documentation, 'tags', [$tagName]);
        }
    }

    /**
     * Get action method instance
     */
    private function getActionMethodInstance(DataObjects\Route $route): ?ReflectionMethod
    {
        [$class, $method] = Str::parseCallback($route->action());
        if (!$class || !$method) {
            return null;
        }
        try {
            return new ReflectionMethod($class, $method);
        } catch (ReflectionException $exception) {
            return null;
        }
    }

    /**
     * Parse action documentation block
     */
    private function parseActionDocumentationBlock(string $documentationBlock, string $uri): array
    {
        $documentation = [
            'summary' => '',
            'description' => '',
            'deprecated' => false,
            'responses' => [],
            'parameters' => [],
        ];

        if (empty($documentationBlock) || !$this->fromConfig('parse.docBlock', false)) {
            return $documentation;
        }

        try {
            $parsedComment = $this->parser->create($documentationBlock);
            Arr::set($documentation, 'deprecated', $parsedComment->hasTag('deprecated'));
            Arr::set($documentation, 'summary', $parsedComment->getSummary());
            Arr::set($documentation, 'description', (string) $parsedComment->getDescription());

            if ($parsedComment->hasTag('Request')) {
                $firstTag = Arr::first($parsedComment->getTagsByName('Request'));
                $tagData = $this->annotationsHelper->parseRawDocumentationTag($firstTag);
                foreach ($tagData as $key => $value) {
                    if (in_array($key, ['operationId', 'pathParams'])) {
                        $documentation[$key] = $value;
                        continue;
                    }
                    Arr::set($documentation, $key, $value);
                }
            }

            if ($parsedComment->hasTag('Response')) {
                $responseTags = $parsedComment->getTagsByName('Response');
                foreach ($responseTags as $rawTag) {
                    $tagData = $this->annotationsHelper->parseRawDocumentationTag($rawTag);
                    $responseCode = '';
                    foreach ($tagData as $key => $value) {
                        if (!in_array($key, ['code', 'description', 'ref'])) {
                            continue;
                        }

                        if ($key === 'code') {
                            $responseCode = $value;
                            $documentation['responses'][$value] = [
                                'description' => '',
                            ];
                            continue;
                        }

                        if ($key === 'description') {
                            $documentation['responses'][$responseCode]['description'] = $value;
                            continue;
                        }

                        if ($key === 'ref') {
                            [$arrayOfSchemas, $schemaBuilt] =
                                $this->annotationsHelper->parsedSchemas($value, $uri);

                            if ($arrayOfSchemas !== null || $schemaBuilt !== null) {
                                $schema = $schemaBuilt ?? $arrayOfSchemas;

                                $documentation['responses'][$responseCode]['content']['application/json']['schema'] = $schema;

                                continue;
                            }

                            $ref = $this->toSwaggerModelPath($value);
                            $documentation['responses'][$responseCode]['content']['application/json']['schema']['$ref'] = $ref;
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            if (
                $exception instanceof SchemaBuilderNotFound ||
                $exception instanceof AnnotationException ||
                $exception instanceof ReflectionException
            ) {
                throw $exception;
            }
        }

        return $documentation;
    }

    /**
     * Turn a model name to swagger path to that model or
     * return the same string if it's already a valid path
     */
    private function toSwaggerModelPath(string $value): string
    {
        if (!Str::startsWith($value, '#/components/schemas/')) {
            foreach ($this->definitionGenerator->getDefinedSchemas() as $item) {
                if (Str::endsWith($item, $value)) {
                    return "#/components/schemas/$value";
                }
            }
        }

        return $value;
    }

    /**
     * Append items from 'swagger.config'
     */
    private function addConfigAppendItems(array &$information): void
    {

        if (count(Arr::get($information, 'responses')) === 0) {
            Arr::set($information, 'responses', [
                '200' => [
                    'description' => 'OK',
                ]
            ]);
        }

        foreach ($this->append['responses'] as $code => $response) {
            if (Arr::has($information, 'responses.'.$code)) {
                continue;
            }

            $this->addNewAppendResponse($information, $code, $response);
        }

        foreach ($this->append['headers'] as $header => $value) {
            $information['parameters'][] = $this->createHeader($header, $value);
        }
    }

    private function createHeader(string $headerName, array $value): array
    {
        $newHeader = $value;
        $newHeader['name'] = $headerName;
        $newHeader['in'] = 'header';
        $newHeader['schema'] = [
            'type' => $value['type'],
            'default' => $value['example']
        ];
        unset($newHeader['type']);

        return $newHeader;
    }

    private function addNewAppendResponse(array &$information, string $code, array $response): void
    {
        $newResponse = [
            'description' => '',
        ];

        if (isset($response['ref'])) {
            $data = [];
            [$arrayOfSchemas, $schemaBuilt] = $this->annotationsHelper
                ->parsedSchemas($response['ref']);

            if ($arrayOfSchemas || $schemaBuilt) {
                $data = $schemaBuilt ?? $arrayOfSchemas;
            } else {
                $data['$ref'] = $this->annotationsHelper
                    ->toSwaggerSchemaPath($response['ref']);
            }

            $newResponse['content']['application/json']['schema'] = $data;
        }

        if (isset($response['description'])) {
            $newResponse['description'] = $response['description'];
        }

        Arr::set($information, 'responses.'.$code, $newResponse);
    }

    /**
     * Append action parameters
     */
    private function addActionsParameters(
        array &$information,
        DataObjects\Route $route,
        string $method,
        ?ReflectionMethod $actionInstance
    ): void {
        $rules = $this->retrieveFormRules($actionInstance) ?: [];
        $parameters = (new Parameters\PathParametersGenerator($route->originalUri()))->getParameters();
        $requestBody = [];

        if (count($rules) > 0) {
            $parametersGenerator = $this->getParametersGenerator($rules, $method);
            if ('body' == $parametersGenerator->getParameterLocation()) {
                $requestBody = $parametersGenerator->getParameters();
            } else {
                $parameters = array_merge($parameters, $parametersGenerator->getParameters());
            }
        }

        if (count($parameters) > 0) {
            Arr::set($information, 'parameters', $parameters);
        }

        if (count($requestBody) > 0) {
            Arr::set($information, 'requestBody', $requestBody);
        }
    }

    /**
     * Add action scopes
     */
    private function addActionScopes(array &$information, DataObjects\Route $route)
    {
        foreach ($route->middleware() as $middleware) {
            if (SwaggerSecurityHelper::isSecurityMiddleware($middleware)) {
                continue;
            }

            $security = [];

            foreach ($this->fromConfig('authentication_flow') as $definition => $value) {
                $parameters = ($definition === 'OAuth2') ? $middleware->parameters() : [];
                $security[$definition] = $parameters;
            }

            Arr::set($information, 'security', [$security]);
        }
    }

    /**
     * Retrieve form rules
     */
    private function retrieveFormRules(?ReflectionMethod $actionInstance): array
    {
        if (!$actionInstance) {
            return [];
        }
        $parameters = $actionInstance->getParameters();

        foreach ($parameters as $parameter) {
            $class = $parameter->getType();
            if (!$class) {
                continue;
            }
            $className = $class->getName();
            if (is_subclass_of($className, FormRequest::class)) {
                return (new $className)->rules();
            }
        }
        return [];
    }

    /**
     * Get appropriate parameters generator
     */
    private function getParametersGenerator(array $rules, string $method): Parameters\Interfaces\ParametersGenerator
    {
        switch ($method) {
            case 'post':
            case 'put':
            case 'patch':
                return new Parameters\BodyParametersGenerator($rules);
            default:
                return new Parameters\QueryParametersGenerator($rules);
        }
    }
}
