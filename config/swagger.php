<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Swagger
    |--------------------------------------------------------------------------
    |
    | This option determines whether the Swagger UI and OpenAPI file routes
    | are enabled or disabled. Disabling it hides the documentation UI
    | but you can still use commands to generate the OpenAPI file.
    |
    */

    'enable' => env('SWAGGER_ENABLE', true),

    /*
    |--------------------------------------------------------------------------
    | Swagger Storage Path
    |--------------------------------------------------------------------------
    |
    | Base directory where generated OpenAPI files are stored.
    | Each page generates its own file: {storage}/{page-name}.json
    |
    | Default: storage_path('swagger')
    |
    */

    'storage' => env('SWAGGER_STORAGE', storage_path('swagger')),

    /*
    |--------------------------------------------------------------------------
    | Views Path
    |--------------------------------------------------------------------------
    |
    | Path where the Swagger UI views are stored (used when publishing views).
    |
    | Default: base_path('resources/views/vendor/swagger')
    |
    */

    'views' => base_path('resources/views/vendor/swagger'),

    /*
    |--------------------------------------------------------------------------
    | Schemas Path (global default)
    |--------------------------------------------------------------------------
    |
    | Default path for custom schema classes, inherited by every page that
    | does not define its own 'schemas' key.
    |
    | Each page can override this with a single path (string) or a list of
    | paths (array) — useful for modularized projects where each module owns
    | its own Swagger/Schemas directory.
    |
    | Default: app_path('Swagger/Schemas')
    |
    */

    'schemas' => app_path('Swagger/Schemas'),

    /*
    |--------------------------------------------------------------------------
    | Parse Configurations
    |--------------------------------------------------------------------------
    |
    | 'docBlock': Whether to parse controller method docBlocks for summaries,
    | descriptions, @Request and @Response annotations.
    |
    | 'security': Whether to parse security annotations to determine
    | authentication requirements per route.
    |
    */

    'parse' => [
        'docBlock' => true,
        'security' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Builders
    |--------------------------------------------------------------------------
    |
    | Custom response schema builders shared across all pages.
    | Keys are the type aliases used in @Response annotations.
    | Values must implement AutoSwagger\Docs\Responses\SchemaBuilder.
    |
    */

    'schema_builders' => [
        'P' => \AutoSwagger\Docs\Responses\SchemaBuilders\LaravelPaginateSchemaBuilder::class,
        'SP' => \AutoSwagger\Docs\Responses\SchemaBuilders\LaravelSimplePaginateSchemaBuilder::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Driver Configurations
    |--------------------------------------------------------------------------
    |
    | Framework-specific configuration for each supported UI driver.
    | These apply globally; the driver used per page is set inside each page.
    |
    */

    'ui' => [
        'configs' => [
            /**
             * https://swagger.io/docs/open-source-tools/swagger-ui/usage/configuration/
             */
            'swagger-ui' => [
                'layout' => 'StandaloneLayout',
                'filter' => true,
                'deepLinking' => true,
                'displayRequestDuration' => true,
                'showExtensions' => true,
                'showCommonExtensions' => true,
                'queryConfigEnabled' => true,
                'persistAuthorization' => true,
            ],

            /**
             * https://github.com/scalar/scalar?tab=readme-ov-file#configuration
             */
            'scalar' => [
                'layout' => 'modern',
                'theme' => 'purple',
                'showSidebar' => true,
                'searchHotKey' => 'k',
            ],

            /**
             * https://rapidocweb.com/examples.html
             */
            'rapidoc' => [
                'theme' => 'light',
                'layout' => 'column',
                'render-style' => 'focused',
                'schema-style' => 'tree',
                'logo-url' => '',

                'show-header' => true,
                'allow-spec-file-load' => true,
                'allow-spec-file-download' => true,
                'allow-spec-url-load' => true,

                'colors' => [
                    'primary' => '#0f6ab4',
                    'header' => '', // Only available in dark mode
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Ignored Items
    |--------------------------------------------------------------------------
    |
    | Routes and methods excluded from ALL pages. Each page can additionally
    | exclude its own routes via its own 'ignored.routes' key.
    |
    | Swagger page routes are automatically excluded — no need to list them.
    |
    */

    'ignored' => [
        'methods' => [
            'head',
            'options',
        ],

        'routes' => [
            'passport.authorizations.authorize',
            'passport.authorizations.approve',
            'passport.authorizations.deny',
            'passport.token',
            'passport.tokens.index',
            'passport.tokens.destroy',
            'passport.token.refresh',
            'passport.clients.index',
            'passport.clients.store',
            'passport.clients.update',
            'passport.clients.destroy',
            'passport.scopes.index',
            'passport.personal.tokens.index',
            'passport.personal.tokens.store',
            'passport.personal.tokens.destroy',

            '/_ignition/health-check',
            '/_ignition/execute-solution',
            '/_ignition/share-report',
            '/_ignition/scripts/{script}',
            '/_ignition/styles/{style}',
        ],

        'models' => [
            // '*' // <- Uncomment to ignore all models
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Pages
    |--------------------------------------------------------------------------
    |
    | Each entry registers one Swagger UI page with its own route, middleware,
    | API scope, and documentation settings.
    |
    | Common use-cases:
    |   - 'default'  → public-facing docs, no middleware, /api routes
    |   - 'internal' → team docs, behind auth middleware, all routes
    |
    | Keys available per page:
    |
    |   title       - Page <title> and OpenAPI info.title
    |   description - OpenAPI info.description
    |   version     - OpenAPI info.version
    |   host        - Base URL used to build OAuth2 endpoint URLs (defaults to APP_URL)
    |   path        - URL prefix where this page is served  (e.g. /docs)
    |   middleware   - Laravel middleware applied to the UI and content routes
    |   api_base_path     - Route prefix filter; only routes starting with this are included
    |   servers     - OpenAPI servers list (strings or {url, description} arrays)
    |   generated   - true = regenerate on every request; false = serve cached file
    |   ui_driver   - UI renderer: 'swagger-ui' | 'scalar' | 'rapidoc'
    |   append      - Responses and headers appended to every operation
    |   ignored     - Page-specific ignored routes (merged with global ignored.routes)
    |   tags        - Manual tag definitions with optional descriptions
    |   default_tags_generation_strategy - 'prefix' | 'controller' | any other = default tag
    |   authentication_flow  - Auth schemes: ['bearerAuth' => 'http'] or ['OAuth2' => 'authorizationCode']
    |   security_middlewares - Middleware names that mark a route as secured in the spec
    |
    */

    'pages' => [

        'default' => [
            'title' => env('APP_NAME', 'Application').' — API Documentation',
            'description' => env('APP_DESCRIPTION', 'Documentation for the Application API'),
            'version' => env('APP_VERSION', '1.0.0'),
            'host' => env('APP_URL'),

            'path' => env('SWAGGER_PATH', '/docs'),
            'middleware' => [],

            // Single directory — string:
            'schemas' => app_path('Swagger/Schemas'),
            // Multiple directories — array (useful for modularized projects):
            // 'schemas' => [
            //     app_path('Swagger/Schemas'),
            //     base_path('modules/Orders/Swagger/Schemas'),
            //     base_path('modules/Auth/Swagger/Schemas'),
            // ],

            'api_base_path' => env('SWAGGER_API_BASE_PATH', '/api'),

            'servers' => env('APP_URL', false)
                ? [env('APP_URL').env('SWAGGER_API_BASE_PATH', '/api')]
                : [],

            'generated' => env('SWAGGER_GENERATE_ALWAYS', true),
            'ui_driver' => env('SWAGGER_UI_DRIVER', 'swagger-ui'),

            'append' => [
                'responses' => [
                    '401' => [
                        'description' => '(Unauthorized) Invalid or missing Access Token',
                        //'ref' => 'ExampleErrorSchema'
                    ],
                ],
                'headers' => [
                    // 'Version' => [
                    //     'required'    => true,
                    //     'description' => 'The version of the application',
                    //     'example'     => '1.0.0',
                    //     'type'        => 'string',
                    // ],
                ],
            ],

            'ignored' => [
                'routes' => [],
            ],

            'tags' => [
                // ['name' => 'Authentication', 'description' => 'Routes related to Authentication'],
            ],

            'default_tags_generation_strategy' => env('SWAGGER_DEFAULT_TAGS_GENERATION_STRATEGY', 'prefix'),

            'authentication_flow' => [
                //'OAuth2'     => 'authorizationCode',
                'bearerAuth' => 'http',
            ],

            'security_middlewares' => [
                'auth:api',
                'auth:sanctum',
            ],
        ],

        /*
        | Example: internal team page, protected by auth middleware
        |
        | 'internal' => [
        |     'title'       => env('APP_NAME', 'Application') . ' — Internal Docs',
        |     'description' => 'Full internal API reference for the development team',
        |     'version'     => env('APP_VERSION', '1.0.0'),
        |     'host'        => env('APP_URL'),
        |
        |     'path'       => '/docs/internal',
        |     'middleware' => ['auth'],
        |
        |     'api_base_path' => '/',
        |
        |     'servers'   => [],
        |     'generated' => env('SWAGGER_GENERATE_ALWAYS', true),
        |     'ui_driver' => 'scalar',
        |
        |     'append' => [
        |         'responses' => [],
        |         'headers'   => [],
        |     ],
        |
        |     'ignored' => [
        |         'routes' => [],
        |     ],
        |
        |     'tags'      => [],
        |     'default_tags_generation_strategy' => 'prefix',
        |
        |     'authentication_flow'  => ['bearerAuth' => 'http'],
        |     'security_middlewares' => ['auth:api', 'auth:sanctum'],
        | ],
        */

    ],

];
