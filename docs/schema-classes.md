---
sidebar_position: 5
---

# Schema Classes

PHP Swagger Test provides two main schema classes for working with different versions of the OpenAPI specification:

- `SwaggerSchema` - For OpenAPI 2.0 (formerly known as Swagger)
- `OpenApiSchema` - For OpenAPI 3.0

Both classes extend the abstract `Schema` class and provide specific implementations for their respective specification
versions.

## Creating Schema Instances

You can create a schema instance using factory methods, which automatically determine the schema type based on the
provided data:

```php
<?php
// From a file (recommended)
$schema = \ByJG\ApiTools\Base\Schema::fromFile('/path/to/specification.json');

// From a JSON string
$jsonString = file_get_contents('/path/to/specification.json');
$schema = \ByJG\ApiTools\Base\Schema::fromJson($jsonString);

// From an array
$schemaArray = json_decode(file_get_contents('/path/to/specification.json'), true);
$schema = \ByJG\ApiTools\Base\Schema::fromArray($schemaArray);
```

**Note:** The `getInstance()` method is deprecated since version 6.0. Use `fromFile()`, `fromJson()`, or `fromArray()`
instead.

## SwaggerSchema Specific Features

The `SwaggerSchema` class provides specific methods for working with OpenAPI 2.0 (Swagger) specifications:

### Handling Null Values

OpenAPI 2.0 doesn't explicitly describe null values. The `SwaggerSchema` class provides a way to configure whether null
values should be allowed in responses:

```php
<?php
// When creating the schema
$schema = new \ByJG\ApiTools\Swagger\SwaggerSchema($data, true); // Allow null values

// Or after creation
$schema->setAllowNullValues(true); // Allow null values
$schema->setAllowNullValues(false); // Don't allow null values
```

## OpenApiSchema Specific Features

The `OpenApiSchema` class provides specific methods for working with OpenAPI 3.0 specifications:

### Server Variables

OpenAPI 3.0 allows defining server URLs with variables. The `OpenApiSchema` class provides a way to set these variables:

```php
<?php
// Example OpenAPI 3.0 specification with server variables
$openApiSpec = [
    'openapi' => '3.0.0',
    'servers' => [
        [
            'url' => 'https://{environment}.example.com/v1',
            'variables' => [
                'environment' => [
                    'default' => 'api',
                    'enum' => ['api', 'api.dev', 'api.staging']
                ]
            ]
        ]
    ]
];

$schema = new \ByJG\ApiTools\OpenApi\OpenApiSchema($openApiSpec);

// Set a server variable
$schema->setServerVariable('environment', 'api.dev');

// Now getServerUrl() will return 'https://api.dev.example.com/v1'
echo $schema->getServerUrl();
```

## Common Methods

Both schema classes provide the following common methods:

- `getServerUrl()` - Get the server URL from the specification
- `getBasePath()` - Get the base path from the specification
- `getPathDefinition($path, $method)` - Get the definition for a specific path and method
- `getRequestParameters($path, $method)` - Get the request parameters for a specific path and method
- `getResponseParameters($path, $method, $status)` - Get the response parameters for a specific path, method, and status
  code
- `getDefinition($name)` - Get a definition by name
