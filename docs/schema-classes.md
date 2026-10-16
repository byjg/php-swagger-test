---
sidebar_position: 5
---

# Schema Classes

PHP Swagger Test provides three main schema classes for working with different versions of the OpenAPI specification:

- `SwaggerSchema` - For OpenAPI 2.0 (formerly known as Swagger)
- `OpenApiSchema` - For OpenAPI 3.0.x
- `OpenApi31Schema` - For OpenAPI 3.1.x with JSON Schema 2020-12 support

All classes extend the abstract `Schema` class and provide specific implementations for their respective specification
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

**Note:** The `getInstance()` method, deprecated since version 6.0, is removed in version 7.0. Use `fromFile()`,
`fromJson()`, or `fromArray()` instead.

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

## OpenApi31Schema Specific Features

The `OpenApi31Schema` class handles OpenAPI 3.1.x specifications with full JSON Schema 2020-12 support.

### Key Differences from OpenAPI 3.0

1. **Nullable Handling**: Uses type arrays `["string", "null"]` instead of the `nullable` keyword
2. **Webhooks**: First-class support for describing incoming HTTP requests
3. **JSON Schema Keywords**: Full support for `const`, `if/then/else`, `prefixItems`, and more
4. **$ref Behavior**: Can have sibling keywords alongside `$ref`
5. **Server Variables**: Default values are optional (required in 3.0)

### Webhooks Support

OpenAPI 3.1 introduces webhooks for describing incoming HTTP requests:

```php
<?php
$schema = \ByJG\ApiTools\Base\Schema::fromFile('/path/to/openapi31.json');

// Check if schema has webhooks
if ($schema->hasWebhooks()) {
    // Get all webhook names
    $webhooks = $schema->getWebhookNames(); // ['newUser', 'orderUpdated', ...]

    // Validate webhook request
    $requestBody = $schema->getWebhookRequestParameters('newUser', 'post');
    $requestBody->match([
        'userId' => 123,
        'event' => 'user.created'
    ]);

    // Validate webhook response
    $responseBody = $schema->getWebhookResponseParameters('newUser', 'post', 200);
    $responseBody->match($responseData);
}
```

### Schema Dialect Detection

OpenAPI 3.1 can declare its JSON Schema dialect:

```php
<?php
$schema = \ByJG\ApiTools\Base\Schema::fromFile('/path/to/openapi31.json');

// Get the declared schema dialect
$dialect = $schema->getSchemaDialect();
// Returns: "https://spec.openapis.org/oas/3.1/dialect/base" or null

// Check if using JSON Schema 2020-12
if ($schema->isJsonSchema202012()) {
    // Can use advanced JSON Schema 2020-12 features
}
```

### Webhook Methods

- `hasWebhooks(): bool` - Check if the schema defines any webhooks
- `getWebhookNames(): array` - Get list of all webhook names
- `getWebhookDefinition(string $name, string $method): mixed` - Get webhook definition
- `getWebhookRequestParameters(string $name, string $method): Body` - Get request body validator
- `getWebhookResponseParameters(string $name, string $method, int $status): Body` - Get response validator

### Schema Dialect Methods

- `getSchemaDialect(): ?string` - Get the JSON Schema dialect declaration
- `isJsonSchema202012(): bool` - Check if using JSON Schema 2020-12

For comprehensive documentation on OpenAPI 3.1 features, see the [OpenAPI 3.1 Features Guide](openapi-3.1-features.md).

## Common Methods

Both schema classes provide the following common methods:

- `getServerUrl()` - Get the server URL from the specification
- `getBasePath()` - Get the base path from the specification
- `getPathDefinition($path, $method)` - Get the definition for a specific path and method
- `getRequestParameters($path, $method)` - Get the request parameters for a specific path and method
- `getResponseParameters($path, $method, $status)` - Get the response parameters for a specific path, method, and status
  code
- `getDefinition($name)` - Get a definition by name
