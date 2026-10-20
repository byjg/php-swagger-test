---
sidebar_position: 6
---

# OpenAPI 3.1 Features

This document describes the OpenAPI 3.1 specific features supported by php-swagger-test.

## Table of Contents

- [Version Detection](#version-detection)
- [Nullable Types](#nullable-types)
- [Webhooks](#webhooks)
- [JSON Schema 2020-12 Features](#json-schema-2020-12-features)
- [Migration from 3.0 to 3.1](#migration-from-30-to-31)

## Version Detection

The library automatically detects OpenAPI 3.1 schemas based on the `openapi` field:

```php
use ByJG\ApiTools\Base\Schema;

// OpenAPI 3.0 - returns OpenApiSchema
$schema30 = Schema::fromJson('{"openapi": "3.0.3", ...}');

// OpenAPI 3.1 - returns OpenApi31Schema
$schema31 = Schema::fromJson('{"openapi": "3.1.0", ...}');

// Check version
echo $schema31->getSpecificationVersion(); // "3.1.0" (the "openapi" value)
```

## Nullable Types

### OpenAPI 3.0 Approach

OpenAPI 3.0 uses the `nullable` keyword:

```json
{
  "type": "string",
  "nullable": true
}
```

### OpenAPI 3.1 Approach

OpenAPI 3.1 uses JSON Schema union types:

```json
{
  "type": [
    "string",
    "null"
  ]
}
```

### Testing Nullable Fields

```php
$schema = Schema::fromFile('openapi31.json');
$responseBody = $schema->getResponseParameters('/users', 'get', 200);

// Both null and string values are valid
$responseBody->match(['email' => null]);                   // Valid
$responseBody->match(['email' => 'test@example.com']);     // Valid
```

### Multiple Nullable Types

You can have multiple types including null:

```json
{
  "type": [
    "string",
    "number",
    "null"
  ]
}
```

### Nullable Objects with Required Fields

OpenAPI 3.1 supports nullable objects that have required fields and nested properties using `$ref`:

```json
{
  "type": "object",
  "properties": {
    "name": {
      "type": "string"
    },
    "manager": {
      "type": ["object", "null"],
      "required": ["phone"],
      "properties": {
        "email": {
          "$ref": "#/components/schemas/emailProperty"
        },
        "phone": {
          "$ref": "#/components/schemas/phoneNumberProperty"
        },
        "firstName": {
          "$ref": "#/components/schemas/firstNameProperty"
        }
      }
    }
  },
  "required": ["name"]
}
```

When validating nullable objects with required fields:

```php
// Valid: manager is null
$requestBody->match([
    'name' => 'ACME Corp',
    'manager' => null
]);

// Valid: manager is omitted (not required)
$requestBody->match([
    'name' => 'ACME Corp'
]);

// Valid: manager has required phone field
$requestBody->match([
    'name' => 'ACME Corp',
    'manager' => [
        'phone' => '+1234567890'
    ]
]);

// Invalid: manager is present but missing required phone
$requestBody->match([
    'name' => 'ACME Corp',
    'manager' => [
        'email' => 'test@example.com'
    ]
]); // Throws NotMatchedException
```

This feature is particularly useful when modeling optional complex objects that, when present, must satisfy specific
requirements.

## Webhooks

Webhooks allow you to describe incoming HTTP requests that your API will receive.

### Schema Definition

```json
{
  "openapi": "3.1.0",
  "webhooks": {
    "newUser": {
      "post": {
        "requestBody": {
          "content": {
            "application/json": {
              "schema": {
                "type": "object",
                "properties": {
                  "userId": {
                    "type": "integer"
                  },
                  "event": {
                    "type": "string"
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
```

### Testing Webhooks

```php
$schema = Schema::fromFile('openapi31.json');

// Check if webhooks exist
if ($schema->hasWebhooks()) {
    // Get all webhook names
    $webhooks = $schema->getWebhookNames(); // ['newUser', 'orderUpdated']

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

## JSON Schema 2020-12 Features

### const Keyword

Validate that a value is exactly a constant:

```json
{
  "type": "object",
  "properties": {
    "status": {
      "const": "active"
    }
  }
}
```

```php
$requestBody->match(['status' => 'active']);      // Valid
$requestBody->match(['status' => 'inactive']);    // Throws NotMatchedException
```

### Conditional Schemas (if/then/else)

Apply different validation rules based on conditions:

```json
{
  "type": "object",
  "properties": {
    "country": {
      "type": "string"
    },
    "postalCode": {
      "type": "string"
    }
  },
  "if": {
    "properties": {
      "country": {
        "const": "US"
      }
    }
  },
  "then": {
    "properties": {
      "postalCode": {
        "pattern": "^[0-9]{5}(-[0-9]{4})?$"
      }
    }
  },
  "else": {
    "properties": {
      "postalCode": {
        "pattern": "^[A-Z0-9 -]+$"
      }
    }
  }
}
```

```php
// US postal code
$requestBody->match([
    'country' => 'US',
    'postalCode' => '12345'
]); // Valid

// Non-US postal code
$requestBody->match([
    'country' => 'CA',
    'postalCode' => 'K1A 0B1'
]); // Valid
```

### Tuple Validation (prefixItems)

Validate arrays with specific types at specific positions:

```json
{
  "type": "array",
  "prefixItems": [
    {
      "type": "number",
      "description": "latitude"
    },
    {
      "type": "number",
      "description": "longitude"
    }
  ],
  "minItems": 2,
  "maxItems": 2
}
```

```php
$requestBody->match([40.7128, -74.0060]);         // Valid (lat, lng)
$requestBody->match([40.7128]);                   // Invalid - too few items
$requestBody->match(['40.7128', -74.0060]);       // Invalid - first item is string
```

### $ref with Sibling Keywords

In OpenAPI 3.1, you can have sibling keywords alongside `$ref`:

```json
{
  "$ref": "#/components/schemas/User",
  "description": "The authenticated user",
  "example": {
    "id": 1,
    "name": "John"
  }
}
```

In OpenAPI 3.0, you would need to wrap this in `allOf`.

## Migration from 3.0 to 3.1

### 1. Update openapi Version

```json
{
  "openapi": "3.1.0",
  // Changed from "3.0.3"
  ...
}
```

### 2. Replace nullable with Type Arrays

Before (3.0):

```json
{
  "type": "string",
  "nullable": true
}
```

After (3.1):

```json
{
  "type": [
    "string",
    "null"
  ]
}
```

### 3. Simplify $ref Usage

Before (3.0):

```json
{
  "allOf": [
    {
      "$ref": "#/components/schemas/User"
    },
    {
      "description": "Additional info"
    }
  ]
}
```

After (3.1):

```json
{
  "$ref": "#/components/schemas/User",
  "description": "Additional info"
}
```

### 4. Optional Server Variable Defaults

In 3.1, server variable defaults are optional:

Before (3.0 - required):

```json
{
  "servers": [
    {
      "url": "http://{host}",
      "variables": {
        "host": {
          "default": "localhost"
          // Required
        }
      }
    }
  ]
}
```

After (3.1 - optional):

```json
{
  "servers": [
    {
      "url": "http://{host}",
      "variables": {
        "host": {
          "enum": [
            "localhost",
            "example.com"
          ]
          // default is optional
        }
      }
    }
  ]
}
```

### 5. Use Modern JSON Schema Keywords

Take advantage of new keywords:

- Use `const` instead of single-value `enum`
- Use `prefixItems` for tuple validation
- Use `if/then/else` for conditional validation

## Compatibility Notes

- **Backward Compatibility**: OpenAPI 3.0 schemas continue to work without changes
- **Mixed Usage**: You can use both 3.0 and 3.1 schemas in the same project
- **Automatic Detection**: The library automatically detects the version and applies appropriate validation rules
- **No Breaking Changes**: Existing tests and code continue to work

## Examples

See the `/tests/example/` directory for complete working examples:

- `openapi31.json` - Basic 3.1 schema
- `openapi31-nullable.json` - Nullable type examples
- `openapi31-nested-ref-required.json` - Nullable objects with required fields and nested $ref
- `openapi31-webhooks.json` - Webhook definitions
- `openapi31-conditional.json` - Conditional schemas
- `openapi31-tuples.json` - Tuple validation

## Further Reading

- [OpenAPI 3.1 Specification](https://spec.openapis.org/oas/v3.1.0)
- [JSON Schema 2020-12](https://json-schema.org/draft/2020-12/json-schema-core.html)
- [What's New in OpenAPI 3.1](https://www.openapis.org/blog/2021/02/16/migrating-from-openapi-3-0-to-3-1-0)