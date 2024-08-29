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
     * Get endpoint
     */
    public static function getEndpoint(string $path): string
    {
        $host = config('swagger.host');
        if (!Str::startsWith($host, 'http://') || !Str::startsWith($host, 'https://')) {
            $schema = swagger_is_connection_secure() ? 'https://' : 'http://';
            $host = $schema.$host;
        }
        return rtrim($host, '/').$path;
    }

    /**
     * Check whether specified middleware belongs to registered security middlewares
     */
    public static function isSecurityMiddleware(Middleware $middleware): bool
    {
        return in_array("$middleware", config('swagger.security_middlewares'));
    }

    /**
     * Generate security definitions
     * @return array[]
     *
     * @throws InvalidAuthenticationFlow|InvalidDefinitionException
     */
    public static function generateSecurityDefinitions(): array
    {
        $authenticationFlows = config('swagger.authentication_flow');

        $definitions = [];

        foreach ($authenticationFlows as $definition => $flow) {
            self::validateAuthenticationFlow($definition, $flow);
            $definitions[$definition] = self::createSecurityDefinition($definition, $flow);
        }

        return $definitions;
    }

    /**
     * Create security definition
     * @return string[]
     */
    protected static function createSecurityDefinition(string $definition, string $flow): array
    {
        switch ($definition) {
            case 'OAuth2':
                $definitionBody = [
                    'type' => 'oauth2',
                    'flows' => [
                        $flow => []
                    ],
                ];
                $flowKey = 'flows.'.$flow.'.';

                if (in_array($flow, ['implicit', 'authorizationCode'])) {
                    Arr::set(
                        $definitionBody,
                        $flowKey.'authorizationUrl',
                        SwaggerSecurityHelper::getEndpoint('/oauth/authorize'));
                }

                if (in_array($flow, ['password', 'application', 'authorizationCode'])) {
                    Arr::set(
                        $definitionBody,
                        $flowKey.'tokenUrl',
                        SwaggerSecurityHelper::getEndpoint('/oauth/token')
                    );
                }
                Arr::set($definitionBody, $flowKey.'scopes', self::generateOAuthScopes());
                return $definitionBody;
            case 'bearerAuth':
                return [
                    'type' => $flow,
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT'
                ];
        }
        return [];
    }

    /**
     * Validate selected authentication flow
     *
     * @throws InvalidAuthenticationFlow|InvalidDefinitionException
     */
    protected static function validateAuthenticationFlow(string $definition, string $flow): void
    {
        $definitions = [
            'OAuth2' => ['password', 'application', 'implicit', 'authorizationCode'],
            'bearerAuth' => ['http']
        ];

        if (!Arr::has($definitions, $definition)) {
            throw new InvalidDefinitionException('Invalid Definition, please select from the following: '.
                implode(', ', array_keys($definitions)));
        }

        $allowed = $definitions[$definition];
        if (!in_array($flow, $allowed)) {
            throw new InvalidAuthenticationFlow(
                'Invalid Authentication Flow, please select one from the following: '.
                implode(', ', $allowed));
        }
    }

    /**
     * Generate OAuth scopes
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