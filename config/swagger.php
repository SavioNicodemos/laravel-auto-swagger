<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Swagger Configuration
    |--------------------------------------------------------------------------
    |
    | This option determines whether the Swagger UI and OpenAPI file routes
    | are enabled or disabled. Disable it basically don't show the documentation
    | but you can still use commands to generate the OpenAPI file.
    |
    */

    'enable' => env('SWAGGER_ENABLE', true),

    /*
    |--------------------------------------------------------------------------
    | API Title
    |--------------------------------------------------------------------------
    |
    | This option determines the title of the API documentation. It is used
    | in the OpenAPI file and in the `head>title` HTML tag of the UI.
    |
    */

    'title' => env('APP_NAME', 'Application API Documentation'),

    /*
    |--------------------------------------------------------------------------
    | API Description
    |--------------------------------------------------------------------------
    |
    | This option determines the description of the API documentation. It is
    | used in the OpenAPI file only.
    |
    */

    'description' => env('APP_DESCRIPTION', 'Documentation for the Application API'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | This option determines the version of the API documentation. It is used
    | in the OpenAPI file only.
    |
    */

    'version' => env('APP_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | API Host
    |--------------------------------------------------------------------------
    |
    | This option determines the host of the API documentation. Used to generate
    | the proper URL for the authentication flow.
    |
    */

    'host' => env('APP_URL'),

    /*
    |--------------------------------------------------------------------------
    | API Base Path
    |--------------------------------------------------------------------------
    |
    | This setting specifies the base path for generating the API documentation.
    | You can set it to the root path you prefer for your API documentation.
    | Like for example "/api/v1" if you use api versioning.
    | 
    | One signal that you may need to change this config is when you notice 
    | that all your routes are prefixed with "/api/v1" or "/v1" for example.
    |
    | Default: /api
    |
    */

    'api_base_path' => env('SWAGGER_API_BASE_PATH', '/api'),

    /*
    |--------------------------------------------------------------------------
    | Swagger Routes Path
    |--------------------------------------------------------------------------
    |
    | This setting specifies the path for accessing the Swagger UI.
    | You can set it to the root path you prefer for your API documentation.
    | Like for example "/docs" if you want to access the documentation at
    | "http://yourapp.com/docs".
    |
    | Default: /docs
    |
    */

    'path' => env('SWAGGER_PATH', '/docs'),

    /*
    |--------------------------------------------------------------------------
    | Swagger Storage Path
    |--------------------------------------------------------------------------
    |
    | This setting specifies the path where the OpenAPI file will be stored.
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
    | This setting specifies the path where the Swagger UI views are stored.
    |
    | Default: base_path('resources/views/vendor/swagger')
    |
    */

    'views' => base_path('resources/views/vendor/swagger'),

    /*
    |--------------------------------------------------------------------------
    | Schemas Path
    |--------------------------------------------------------------------------
    |
    | This setting specifies the path where the custom schemas are stored. The
    | schemas are used to generate custom responses in the documentation.
    |
    | The library will look for all PHP files in this directory, generate the
    | schema and use it in the documentation.
    |
    | Default: app_path('Swagger/Schemas')
    |
    */

    'schemas' => app_path('Swagger/Schemas'),

    /*
    |--------------------------------------------------------------------------
    | Servers
    |--------------------------------------------------------------------------
    |
    | This setting specifies the list of servers where the API can be accessed.
    | It is used in the OpenAPI file only.
    |
    | By default will set only the local app url with the API base path. But you
    | can set it to an array of strings or an array of arrays with the keys
    | "url" and "description".
    |
    | Example of a mixed array with two servers:
    | [
    |     'https://staging.example.com/api',
    |
    |     [
    |         'url' => 'https://api.example.com/api',
    |         'description' => 'Production Server'
    |     ]
    | ]
    |
    */

    'servers' => env('APP_URL', false) ? [env('APP_URL') . env('SWAGGER_API_BASE_PATH', '/api')] : [],

    /*
    |--------------------------------------------------------------------------
    | Always Generate
    |--------------------------------------------------------------------------
    |
    | This setting specifies whether the OpenAPI file should be generated
    | every time the Swagger UI is accessed. If set to false, the OpenAPI
    | file will be generated only if it does not exist.
    |
    | In production, it is recommended to set it to false, but it can be useful 
    | to set it to true in development environments.
    |
    | Default: true
    |
    */

    'generated' => env('SWAGGER_GENERATE_ALWAYS', true),

    /*
    |--------------------------------------------------------------------------
    | UI Driver
    |--------------------------------------------------------------------------
    |
    | The UI driver configurations to use for rendering the documentation.
    |
    | Supported: "swagger-ui", "scalar", "rapidoc"
    |
    */
    'ui' => [
        'default' => env('SWAGGER_UI_DRIVER', 'swagger-ui'),

        'configs' => [
            /**
             * For more information about the configuration options, see:
             * https://swagger.io/docs/open-source-tools/swagger-ui/usage/configuration/
             */
            'swagger-ui' => [
                'layout' => "StandaloneLayout",
                'filter' => true,
                'deepLinking' => true,
                'displayRequestDuration' => true,
                'showExtensions' => true,
                'showCommonExtensions' => true,
                'queryConfigEnabled' => true,
                'persistAuthorization' => true,
            ],

            /**
             * For more information about the configuration options, see:
             * https://github.com/scalar/scalar?tab=readme-ov-file#configuration
             */
            'scalar' => [
                'layout' => 'modern',
                'theme' => 'purple',
                'showSidebar' => true,
                'searchHotKey' => 'k',
            ],

            /**
             * For more information about the configuration options, see:
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
                ]
            ]
        ]
    ],

    /*
    |---------------------------------------------------------------------------
    | Append
    |--------------------------------------------------------------------------
    |
    | This setting specifies the data that will be appended to all routes.
    | It is used to add common responses to all routes.
    |
    */

    'append' => [
        'responses' => [
            '401' => [
                'description' => '(Unauthorized) Invalid or missing Access Token',
                //'ref' => 'ExampleErrorSchema' <- You can use a schema reference
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored
    |--------------------------------------------------------------------------
    |
    | This setting specifies the list of ignored items (routes and methods).
    | They will be hidden from the documentation.
    |
    */
    'ignored' => [
        'methods' => [
            'head',
            'options'
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
            env('SWAGGER_PATH', '/docs'),
            env('SWAGGER_PATH', '/docs') . '/content'
        ],

        'models' => []
    ],

    /*
    |--------------------------------------------------------------------------
    | Tags
    |--------------------------------------------------------------------------
    |
    | This setting specifies the tags that will be used in the documentation.
    | It is used to group the routes in the UI.
    |
    | All the required tags are automatically generated from the routes, but
    | this section is useful to add more tags or to add descriptions to them.
    |
    */
    'tags' => [
        // [
        //     'name' => 'Authentication',
        //     'description' => 'Routes related to Authentication'
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Tag Generation Strategy
    |--------------------------------------------------------------------------
    |
    | This configuration option defines the default strategy for generating tags.
    |
    | There are three available strategies:
    | 
    | 'prefix': This strategy uses the first non-null segment of the URI 
    | (split by '/') as the tag.
    |
    | 'controller': This strategy uses the controller name as the tag,
    | converting from camel case to words.
    |
    | Any other value: This will group all operations under a single tag named
    | 'default'.
    |
    */

    'default_tags_generation_strategy' => env('SWAGGER_DEFAULT_TAGS_GENERATION_STRATEGY', 'prefix'),

    /*
    |--------------------------------------------------------------------------
    | Parse configurations
    |--------------------------------------------------------------------------
    |
    | This setting specifies the configurations for parsing the routes.
    |
    | 'docBlock': This setting specifies whether the library should parse the
    | docBlock of the controller methods to get the description and the tags.
    |
    | 'security': This setting specifies whether the library should parse the
    | security annotations to get the authentication flow.
    |
    */

    'parse' => [
        'docBlock' => true,
        'security' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Flow
    |--------------------------------------------------------------------------
    |
    | This setting specifies the authentication flow for the API.
    |
    | 'OAuth2': This setting configures the API to use the OAuth2 authentication.
    |
    | 'bearerAuth': This setting configures the API to use the Bearer
    | authentication where you only pass the token and no other information.
    |
    */

    'authentication_flow' => [
        //'OAuth2' => 'authorizationCode',
        'bearerAuth' => 'http',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Middlewares
    |--------------------------------------------------------------------------
    |
    | This setting specifies the list of security middlewares that will be used
    | to protect the routes.
    |
    | The paths under these middlewares will be automatically marked as secured
    | in the OpenAPI file.
    |
    */
    'security_middlewares' => [
        'auth:api',
        'auth:sanctum',
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Builders
    |--------------------------------------------------------------------------
    |
    | This setting specifies the list of schema builders that will be used
    | to generate the custom responses in the documentation.
    |
    | The key is the type of the response and the value is the class that will
    | generate the schema.
    |
    | If you can implement your own schema builder, see example in this existing
    | implementation. But note that the custom Schema builders must implement
    | "AutoSwagger\Docs\Responses\SchemaBuilder" interface.
    |
    */
    'schema_builders' => [
        'P' => \AutoSwagger\Docs\Responses\SchemaBuilders\LaravelPaginateSchemaBuilder::class,
        'SP' => \AutoSwagger\Docs\Responses\SchemaBuilders\LaravelSimplePaginateSchemaBuilder::class,
    ]

];
