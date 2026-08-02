# Swagger Test 6.0 Release Notes

## Overview

Version 6.0 modernises the test API and the supported platform. The fluent requester replaces the
six-parameter `makeRequest()`, the assertion-style methods become expectation-style, schemas gain
proper factory methods, and validation is available as a trait so you are no longer forced to
extend `ApiTestCase`.

This release drops PHP 8.1 and 8.2, and raises `byjg/webrequest` to `^6.0`.

## New Features

### `OpenApiValidation` trait

Validation is now available as a trait, so it can be added to any test class:

```php
class MyTest extends MyCustomBaseTest
{
    use OpenApiValidation;

    public function setUp(): void
    {
        parent::setUp();
        $this->setSchema(Schema::fromFile('openapi.json'));
    }
}
```

`ApiTestCase` still exists and simply uses the trait, so existing subclasses keep working. This
matters for framework integration — a Laravel or Symfony test case must extend the framework's own
base class, which previously ruled out `ApiTestCase` entirely.

See [Using the OpenApiValidation trait](docs/trait-usage.md).

### Schema factory methods

`Schema::getInstance()` suggested a singleton but always built a new object. Three explicit factory
methods replace it:

```php
$schema = Schema::fromFile('/path/to/spec.json');   // reads the file for you
$schema = Schema::fromJson($jsonString);
$schema = Schema::fromArray($arrayData);
```

Each validates its own input type and reports a specific error. `getInstance()` remains as a
deprecated alias.

### Expectation methods

The requester gained `expectJsonContains()` and `expectJsonPath()` for asserting against the
decoded JSON response, alongside the renamed `expectStatus()`, `expectHeaderContains()` and
`expectBodyContains()`:

```php
$request
    ->withMethod('GET')
    ->withPath('/pet/1')
    ->expectStatus(200)
    ->expectJsonPath('category.name', 'dog')
    ->expectJsonContains(['name' => 'Spike']);
```

Expectations registered this way run as real PHPUnit assertions after the response arrives, so a
passing contract test no longer counts as risky for lack of assertions.

### XML response support

Responses with `text/xml` or `application/xml` are parsed and matched against the specification.
Previously every response was assumed to be JSON.

Content-type detection was reworked: the main type is extracted before the `;`, so
`application/json; charset=utf-8` is recognised, and a response whose body fails to parse as JSON
while declaring a non-JSON content type is matched as a raw string rather than as `null`.

### Documentation

The documentation was restructured into topic pages for Docusaurus:

- [Functional test cases](docs/functional-tests.md)
- [Contract test cases](docs/contract-tests.md)
- [Runtime parameters validator](docs/runtime-parameters-validator.md)
- [Mocking requests](docs/mock-requests.md)
- [Schema classes](docs/schema-classes.md)
- [Using the OpenApiValidation trait](docs/trait-usage.md)
- [Advanced usage](docs/advanced-usage.md)
- [Exception handling](docs/exceptions.md)
- [Migration guide](docs/migration-guide.md)
- [Troubleshooting](docs/troubleshooting.md)

### Development environment

- Added a Docker setup for running the test fixtures
- Added `composer test` and `composer psalm` scripts
- Added `#[\Override]` attributes to overridden methods
- Added strict type declarations across the source and test suites

## Bug Fixes

- Fixed response header parsing when headers arrive as a numerically indexed list rather than a map
- Fixed content-type detection for headers carrying a charset parameter
- Improved error messages on a body mismatch to name the property and the definition that failed

## Breaking Changes

| Before (5.x) | After (6.0) | Description |
|--------------|-------------|-------------|
| PHP >=8.1 <8.4 | PHP >=8.3 <8.6 | Minimum PHP raised to 8.3; PHP 8.4 and 8.5 supported |
| `byjg/webrequest` ^5.0 | `byjg/webrequest` ^6.0 | Updated to major version 6 |
| `byjg/restserver` ^5.0 (dev) | `byjg/restserver` ^6.0 (dev) | Updated to major version 6 |
| PHPUnit ^9.6 | PHPUnit ^10.5 \| ^11.5 | Updated test framework |
| `GenericSwaggerException` | `GenericApiException` | Renamed; the old class was **removed** |
| `assertResponseCode()` | `expectStatus()` | Renamed; the old method was **removed** |
| `assertHeaderContains()` | `expectHeaderContains()` | Renamed; the old method was **removed** |
| `assertBodyContains()` | `expectBodyContains()` | Renamed; the old method was **removed** |

The `assert*` methods on the requester were renamed rather than deprecated, because they never
asserted anything at call time — they registered an expectation validated later by the send. The
old names are gone in 6.0; see the table above for the replacements.

## Deprecations

These still work in 6.0 and will be **removed in 7.0**:

| Deprecated | Replacement |
|------------|-------------|
| `Schema::getInstance()` | `Schema::fromFile()`, `Schema::fromJson()`, `Schema::fromArray()` |
| `assertRequest()` | `sendRequest()` |
| `makeRequest()` | The `ApiRequester` fluent interface |

`assertRequest()` was renamed because it returns a value and performs its validation by throwing,
which is not what a method named `assert*` usually does.

## Upgrading from 5.x

Full instructions, with before/after examples for every change, are in the
[Migration guide](docs/migration-guide.md).

The short version:

1. Raise your PHP version to at least 8.3.
2. Replace `GenericSwaggerException` with `GenericApiException`.
3. Rename `assertResponseCode()` → `expectStatus()`, `assertHeaderContains()` →
   `expectHeaderContains()`, `assertBodyContains()` → `expectBodyContains()`.
4. Optionally move off the deprecated `getInstance()`, `assertRequest()` and `makeRequest()` before
   7.0 removes them.
