# Swagger Test 6.1 Release Notes

## Overview

Version 6.1 adds full support for the **OpenAPI 3.1** specification, including JSON Schema 2020-12
compatibility, webhooks, and the modern schema keywords introduced with it.

This is an additive release. Every Swagger 2.0 and OpenAPI 3.0 schema keeps working unchanged, and
no existing test code needs to be touched — see [Backward Compatibility](#backward-compatibility).

## New Features

### OpenAPI 3.1 support

The specification version is detected automatically, so the same call handles 2.0, 3.0 and 3.1:

```php
$schema = Schema::fromFile('/path/to/openapi.json');

echo $schema->getSpecificationVersion();   // "2.0", "3.0" or "3.1"
```

Supported 3.1 features:

| Feature | Description |
|---------|-------------|
| Union types | `type: ["string", "null"]` in place of the deprecated `nullable` keyword |
| Webhooks | Describe and validate incoming HTTP requests your API receives |
| `const` | Validate a constant value |
| `if` / `then` / `else` | Conditional schemas |
| `prefixItems` | Tuple validation — specific types at specific array positions |
| `$ref` with siblings | References may carry additional keywords alongside them |

Nullable handling was extended to cover objects inside `oneOf` / `anyOf` / `allOf`, including
nullable objects with required fields and nested `$ref`s.

See the [OpenAPI 3.1 Features Guide](docs/openapi-3.1-features.md) for details, and the
[Version comparison](docs/version-comparison.md) for the full support matrix.

### New classes

| Class | Purpose |
|-------|---------|
| `OpenApi31\OpenApi31Schema` | OpenAPI 3.1 schema parsing and validation |
| `OpenApi31\OpenApi31RequestBody` | 3.1 request body matching |
| `OpenApi31\OpenApi31ResponseBody` | 3.1 response body matching |
| `OpenApi\OpenApiBase` | Shared base holding the logic common to 3.0 and 3.1 |

### New methods

Webhook validation, on `OpenApi31Schema`:

```php
$schema->hasWebhooks();
$schema->getWebhookNames();
$schema->getWebhookRequestParameters($webhookName, $method);
$schema->getWebhookResponseParameters($webhookName, $method, $status);
```

Schema dialect detection, on `OpenApi31Schema`:

```php
$schema->getSchemaDialect();      // the declared $schema dialect, or null
$schema->isJsonSchema202012();
```

Specification version, on `Base\Schema`:

```php
$schema->getSpecificationVersion();
```

## Changed

- The OpenAPI implementation was refactored so 3.0 and 3.1 share their common logic through
  `OpenApiBase`, removing duplication between the two
- The `Base\Schema` factory methods now detect and dispatch to the 3.1 implementation
- `matchObject` was removed as dead code while simplifying nullable object handling

## Documentation

- Added the [OpenAPI 3.1 Features Guide](docs/openapi-3.1-features.md)
- Added the [Version comparison](docs/version-comparison.md) support matrix
- Updated the README with the 3.1 feature highlights and the revised limitations list
- Added PHPDoc for the new classes and methods

## Backward Compatibility

No breaking changes. Specifically:

- Every OpenAPI 3.0 and Swagger 2.0 schema is parsed exactly as before
- All existing test code continues to work untouched
- 3.0 and 3.1 schemas can be used side by side in the same project

## Deprecations

No new deprecations. Those introduced in 6.0 remain in place and are still scheduled for removal in
7.0:

| Deprecated in 6.0 | Replacement |
|-------------------|-------------|
| `Schema::getInstance()` | `Schema::fromFile()`, `Schema::fromJson()`, `Schema::fromArray()` |
| `assertRequest()` | `sendRequest()` |
| `makeRequest()` | The `ApiRequester` fluent interface |

## Upgrading from 6.0

Bump the version. There is nothing else to do.

To move a schema from 3.0 to 3.1, see the [Migration guide](docs/migration-guide.md) — the library
handles both, so the migration is optional and can be done one schema at a time.

For the previous release, see [CHANGELOG-6.0.md](CHANGELOG-6.0.md).
