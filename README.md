# Laravel Auto Swagger Docs

Simple to use OAS3 compatible documentation generator.  
Also includes Swagger UI.

## About

This package is heavily inspired by the [Mezatsong/laravel-swagger-docs](https://github.com/Mezatsong/laravel-swagger-docs).

The set of functions that this package has are included:

1. OAS3 support (with constant verifications to ensure syntax and compatibility)
1. Custom decorators
1. Custom responses
1. Custom Schemas
1. Custom Schema builders
1. Automatic Parameters generation based on path and Form Request classes
1. Inclusion of Swagger UI with option to select between different UI drivers
1. Automatic Schema generations by Models or custom Schema classes.
1. Generate operation tags based on route prefix or controller's name

## Installation

### Install package through composer

```shell
composer require savionicodemos/laravel-auto-swagger --dev
```

### Publish configuration files and views

```shell
php artisan vendor:publish --provider "AutoSwagger\Docs\SwaggerServiceProvider"
```

### Edit the `swagger.php` configuration file for your liking

## Usage

Laravel Auto Swagger Docs works based on recommended practices by Laravel. It will parse your routes and generate a path object for each one. If you inject Form Request classes in your controller's actions as request validation, it will also generate the parameters for each request that has them. For the parameters, it will take into account wether the request is a GET/HEAD/DELETE or a POST/PUT/PATCH request and make its best guess as to the type of parameter object it should generate. It will also generate the path parameters if your route contains them. Finally, this package will also scan any documentation you have in your action methods and add it as summary and description to that path, along with any appropriate annotations such as @deprecated.

One thing to note is this library leans on being explicit. It will choose to include keys even if they have a default. For example it chooses to say a route has a deprecated value of false rather than leaving it out. I believe this makes reading the documentation easier by not leaving important information out. The file can be easily cleaned up afterwards if the user chooses to leave out the defaults.

### Command line

Generating the swagger documentation is easy, simply run `php artisan swagger:generate` in your project root. The output of the command will be stored in your storage path linked in config file.

If you wish to generate docs for a subset of your routes, you can pass a filter using `--filter`, for example: `php artisan swagger:generate --filter="/api"`

You can also configure your swagger.php file to always generate schema when accessing Swagger UI or just by adding this line in your .env: `SWAGGER_GENERATE_ALWAYS=true`

By default, laravel-swagger prints out the documentation in json format, if you want it in YAML format you can override the format using the `--format` flag. Make sure to have the yaml extension installed if you choose to do so.

Format options are:

- `json`
- `yaml`

### Annotations syntax

The annotations are written in the PHPDoc block of your controller methods. The syntax is as follows:

```php
/**
 * @Request({
 *    "summary": "Title of the route",
 *    "description": "This is a longer description for the route which will be visible once the panel is expanded",
 *    "tags": ["Authentication","Users"]
 * })
 * 
 */
```

The syntax is basically a JSON inside a comment block. Just be aware to follow strictly the JSON syntax.

#### Rules

- The `key: value` pairs must be separated by a column `:`
- The `key: value` pairs must be always in the same line. If you want a longer description,
you can write it in the comment itself that this description will be used as the description
of the route. You can see the example in the `@Request()` decorator.
- When you need to use an array, you can use the square brackets `[]` to define it as in JSON.

### @Request() decorator

You can have only one `@Request()` decorator.

```php
/**
* You can also do this, first line will be "summary"
*
* And anything 1 * apart from the "summary" will count as "description"
*
* @Request({
*     "summary": "Title of the route", // <- If you add here, this will overwrite the summary from above.
*     "description": "A short description", // <- If you need a longer one, just use the comment itself
*     "tags": ["Authentication","Users"] // <- This Request will be used in this two tags section
* })
*/
public function someMethod(Request $request) {}
```

### @Response() decorator

You can have multiple `@Response` decorators

- The `code` property is required and must be the first in property
- You can use the optional `description` property to describe your response
- You can use the optional `ref` property to refer a Model or a custom Schema, you can also add an `[]` in the final of the Schema name to refer an array of that Schema or use the full Schema path inside, finally you can use a schema builder

```php
/**
* @Response({
*     "code": 200,
*     "description": "Return user model",
*     "ref": "User"
* })
* @Response({
*     "code": 400,
*     "description": "Bad Request",
*     "ref": "APIError[]" // <- An Array of APIError, can be a custom Schema
* })
* @Response({
*     "code": "302",
*     "description": "Redirect"
* })
* @Response({
*     "code": 500,
*     "description": "Internal Server Error"
* })
*/
public function someMethod(Request $request) {}

/**
 * You can also refer object directly
 * 
 * 
 * @Response({
 *     "code": 200,
 *     "description": "Direct user model reference",
 *     "ref": "#/components/schemas/User" // <- Full Schema path
 * })
 */
public function someMethod2(Request $request) {}

/**
 * Using P schema builder for Laravel Pagination
 * 
 * @Response({
 *     "code": 200,
 *     "description": "A laravel pagination instance with User model",
 *     "ref": "P(User)" // <- Using Schema Builder
 * })
 */
public function someMethod3(Request $request) {}
```

> [!NOTE]
> You can see all available schema builder or create your own schema builder, explore swagger.schema_builders config for more information

### Custom Validators

These validators are made purely for visual purposes, however, some of them can actually do validation. But where to insert it? You can simply insert it in the `rules` array in your Form Request class like a normal Laravel validation.

#### swagger_default

It sets the default value for the parameter in the documentation

```php
$rules = [
    'locale'        =>  'swagger_default:en_GB'
];
```

#### swagger_example

It sets the example value for the parameter in the documentation

```php
$rules = [
    'currency'        =>  'swagger_example:EUR'
];
```

> [!NOTE]
> The difference between `swagger_default` and `swagger_example` is that
> `swagger_default` will inform to the user that this value is the default value
> for this parameter inside the application. `swagger_example` will just illustrate
> an example of how the parameter should be filled.

#### swagger_description

It sets the description for the parameter in the documentation

```php
$rules = [
    'limit'         =>  'swagger_description:Limit the number of items to return'
];
```

#### swagger_required

It sets the required status for the parameter in the documentation without affecting the validation rules.
It's useful if you have a parameter that is conditionally required based on other parameters.

```php
$rules = [
    'limit'         =>  [
        $condition ? 'required' : 'nullable',
        'swagger_required:false'
    ]
];
```

#### swagger_min

```php
$rules = [
    // This will simply display the 'minimum' value in the documentation
    'page'          =>  'swagger_default:1|swagger_min:1', 
    // This will also fail if the `page` parameter will be less than 1
    'page'          =>  'swagger_default:1|swagger_min:1:fail'
];
```

#### swagger_max

```php
$rules = [
    // This will simply display the 'maximum' value in the documentation
    'take'          =>  'swagger_default:1|swagger_min:1|swagger_max:50',
    // This will also fail if the `take` parameter will be greater than 50
    'take'          =>  'swagger_default:1|swagger_min:1|swagger_max:50:fail'
];
```
