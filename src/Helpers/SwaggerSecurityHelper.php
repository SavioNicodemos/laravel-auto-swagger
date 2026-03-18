<?php

namespace AutoSwagger\Docs\Helpers;

use AutoSwagger\Docs\DataObjects\Middleware;
use AutoSwagger\Docs\Exceptions\InvalidAuthenticationFlow;
use AutoSwagger\Docs\Exceptions\InvalidDefinitionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

class SwaggerSecurityHelper
{
    /**
     * Build an absolute endpoint URL for OAuth2 flow paths.
     */
    public static function getEndpoint(string $path, string $host): string
    {
        if (!Str::startsWith($host, 'http://') && !Str::startsWith($host, 'https://')) {
            $schema = swagger_is_connection_secure() ? 'https://' : 'http://';
            $host = $schema.$host;
        }
        return rtrim($host, '/').$path;
    }

    /**
     * Check whether the given middleware belongs to the page's security middlewares.
     */
    public static function isSecurityMiddleware(Middleware $middleware, array $securityMiddlewares): bool
    {
        return in_array("$middleware", $securityMiddlewares);
    }

    /**
     * Generate security scheme definitions for the given authentication flows.
     *
     * @throws InvalidAuthenticationFlow|InvalidDefinitionException
     */
    public static function generateSecurityDefinitions(array $authenticationFlows, string $host): array
    {
        $definitions = [];

        foreach ($authenticationFlows as $definition => $flow) {
            self::validateAuthenticationFlow($definition, $flow);
            $definitions[$definition] = self::createSecurityDefinition($definition, $flow, $host);
        }

        return $definitions;
    }

    /**
     * Create a single security definition entry.
     */
    protected static function createSecurityDefinition(string $definition, string $flow, string $host): array
    {
        switch ($definition) {
            case 'OAuth2':
                $definitionBody = [
                    'type' => 'oauth2',
                    'flows' => [$flow => []],
                ];
                $flowKey = 'flows.'.$flow.'.';

                if (in_array($flow, ['implicit', 'authorizationCode'])) {
                    Arr::set(
                        $definitionBody,
                        $flowKey.'authorizationUrl',
                        self::getEndpoint('/oauth/authorize', $host)
                    );
                }

                if (in_array($flow, ['password', 'application', 'authorizationCode'])) {
                    Arr::set(
                        $definitionBody,
                        $flowKey.'tokenUrl',
                        self::getEndpoint('/oauth/token', $host)
                    );
                }

                Arr::set($definitionBody, $flowKey.'scopes', self::generateOAuthScopes());

                return $definitionBody;

            case 'bearerAuth':
                return [
                    'type' => $flow,
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ];
        }

        return [];
    }

    /**
     * Validate the selected authentication flow.
     *
     * @throws InvalidAuthenticationFlow|InvalidDefinitionException
     */
    protected static function validateAuthenticationFlow(string $definition, string $flow): void
    {
        $definitions = [
            'OAuth2' => ['password', 'application', 'implicit', 'authorizationCode'],
            'bearerAuth' => ['http'],
        ];

        if (!Arr::has($definitions, $definition)) {
            throw new InvalidDefinitionException(
                'Invalid Definition, please select from the following: '.
                implode(', ', array_keys($definitions))
            );
        }

        $allowed = $definitions[$definition];
        if (!in_array($flow, $allowed)) {
            throw new InvalidAuthenticationFlow(
                'Invalid Authentication Flow, please select one from the following: '.
                implode(', ', $allowed)
            );
        }
    }

    /**
     * Generate OAuth scopes from Laravel Passport (if installed).
     */
    protected static function generateOAuthScopes(): array
    {
        if (!class_exists(Passport::class)) {
            return [];
        }

        $scopes = Passport::scopes()->toArray();
        return array_combine(array_column($scopes, 'id'), array_column($scopes, 'description'));
    }
}
