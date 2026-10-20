---
sidebar_position: 10
---

# Migration Guide

## OpenAPI 3.1 Support (Added in 6.1)

:::info Non-Breaking Addition
Version 6.1 adds full support for OpenAPI 3.1 with JSON Schema 2020-12 compatibility. All existing code continues to
work without changes.
:::

### What's New in OpenAPI 3.1

- **Automatic Version Detection**: The library now automatically detects and handles OpenAPI 3.1 schemas
- **Type Array Nullable**: Support for JSON Schema union types like `["string", "null"]`
- **Webhooks**: Test incoming HTTP requests your API will receive
- **Modern JSON Schema Keywords**: `const`, `if/then/else`, `prefixItems` for tuples
- **Enhanced $ref**: References can have sibling keywords

### Migration from OpenAPI 3.0 to 3.1

:::tip No Code Changes Required
The library automatically detects the version - your existing code works with both 3.0 and 3.1 schemas!
:::

```php title="Example: Automatic version detection"
// This works for both 3.0 and 3.1
$schema = Schema::fromFile('/path/to/openapi.json');

// Check which version you're using
echo $schema->getSpecificationVersion(); // the "openapi" value, e.g. "3.0.3" or "3.1.0"
```

### Updating Your OpenAPI Schemas

If you want to upgrade your OpenAPI schemas from 3.0 to 3.1:

**1. Update the version number:**

```json title="Update OpenAPI version"
{
  "openapi": "3.1.0"
}
```

**2. Replace `nullable` with type arrays (optional but recommended):**

**Before (OpenAPI 3.0):**

```json
{
  "type": "string",
  "nullable": true
}
```

**After (OpenAPI 3.1):**

```json
{
  "type": [
    "string",
    "null"
  ]
}
```

**3. Use webhooks for incoming requests (new feature):**

```json title="Example: Webhook definition"
{
  "openapi": "3.1.0",
  "webhooks": {
    "newUser": {
      "post": {
        "requestBody": {},
        "responses": {}
      }
    }
  }
}
```

For detailed information on OpenAPI 3.1 features, see [OpenAPI 3.1 Features Guide](openapi-3.1-features.md).

### Backward Compatibility

:::success 100% Backward Compatible
- ✅ All OpenAPI 3.0 and Swagger 2.0 schemas work without changes
- ✅ All existing test code continues to work
- ✅ No breaking changes
- ✅ Mixed 3.0 and 3.1 schemas can be used in the same project

:::

---

## Migrating from Schema::getInstance() (Removed in 7.0)

:::warning Deprecated
The `Schema::getInstance()` method was deprecated in version 6.0 and is removed in version 7.0.
:::

### Why the Change?

:::info Reason for Deprecation
The method name `getInstance()` suggests a singleton pattern, but it actually creates new instances each time (factory
pattern). This is confusing for developers.
:::

### New Factory Methods

Three new, clearer factory methods have been added:

**Old Way (removed in 7.0):**

```php
// From JSON string
$schema = Schema::getInstance(file_get_contents('/path/to/spec.json'));

// From array
$schema = Schema::getInstance($arrayData);
```

**New Way (Recommended):**

```php
// From file (recommended - simplest)
$schema = Schema::fromFile('/path/to/spec.json');

// From JSON string
$jsonString = file_get_contents('/path/to/spec.json');
$schema = Schema::fromJson($jsonString);

// From array
$schema = Schema::fromArray($arrayData);

// With null values allowed (Swagger 2.0 only)
$schema = Schema::fromFile('/path/to/spec.json', allowNullValues: true);
$schema = Schema::fromJson($jsonString, allowNullValues: true);
$schema = Schema::fromArray($arrayData, allowNullValues: true);
```

### Benefits

:::tip Why Use New Methods
1. **Clearer intent**: Method name matches what it does (factory, not singleton)
2. **Better error messages**: Each method validates its specific input type
3. **More convenient**: `fromFile()` handles file reading for you
4. **Consistent naming**: Follows common factory method patterns

:::

---

## Migrating from assertRequest() (Removed in 7.0)

:::warning Removed
The `assertRequest()` method was renamed to `sendRequest()` for clarity in version 6.0, and removed in version 7.0.
:::

### Why the Change?

:::info Reason for Deprecation
The method name `assertRequest()` is misleading because:
- It returns a value (assertions typically don't return)
- The actual validation happens inside via exceptions
- Developers expect assertion methods to be void

:::

### Migration

**Old Way (removed in 7.0):**

```php
$response = $this->assertRequest($request);
```

**New Way:**

```php
$response = $this->sendRequest($request);
```

That's it! The functionality is identical, just the name is clearer.

---

## Migrating to expect* Methods (Version 6.0)

:::info Renamed for Clarity
The assertion-style methods (`assertStatus()`, `assertResponseCode()`, `assertBodyContains()`, etc.) have been renamed
to expectation-style methods in version 6.0 for better semantic clarity.
:::

### Why the Change?

The new "expect" terminology is more semantically accurate:

- These methods **set up expectations** that are validated later when `sendRequest()` is called
- They don't immediately assert - they register expectations to validate after the response
- "Expect" clearly indicates you're defining what you expect, not asserting what already happened
- Common pattern in testing frameworks (PHPUnit prophecy, Mockery, etc.)

### Migration

**Old Way:**

```php
$request = new ApiRequester();
$request
    ->withMethod('GET')
    ->withPath('/pet/1')
    ->assertResponseCode(200)  // or assertStatus(200)
    ->assertBodyContains('Spike')
    ->assertHeaderContains('Content-Type', 'json');
```

**New Way:**

```php
$request = new ApiRequester();
$request
    ->withMethod('GET')
    ->withPath('/pet/1')
    ->expectStatus(200)
    ->expectBodyContains('Spike')
    ->expectHeaderContains('Content-Type', 'json');
```

### Method Mapping

| Old Method               | New Method               |
|--------------------------|--------------------------|
| `assertResponseCode()`   | `expectStatus()`         |
| `assertStatus()`         | `expectStatus()`         |
| `assertBodyContains()`   | `expectBodyContains()`   |
| `assertHeaderContains()` | `expectHeaderContains()` |
| `assertJsonContains()`   | `expectJsonContains()`   |
| `assertJsonPath()`       | `expectJsonPath()`       |

---

## Migrating from makeRequest() (Removed in 7.0)

:::warning Removed
The `makeRequest()` method with 6 parameters was deprecated in version 6.0 and is removed in version 7.0.
:::

### Why the Change?

**Old `makeRequest()` Issues:**

- Required passing 6 parameters (even empty ones)
- Parameters in specific order
- Not easily extensible
- Less readable code

**New `ApiRequester` Benefits:**

- More readable and self-documenting
- Only specify parameters you need
- Easy to extend with new features
- Better IDE autocomplete support

### Migration Examples

#### Example 1: Simple GET Request

**Old Way (removed in 7.0):**

```php
protected function testGetPet()
{
    $this->makeRequest(
        'GET',
        '/pet/1',
        200,
        null,
        null,
        []
    );
}
```

**New Way:**

```php
public function testGetPet()
{
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath('/pet/1');

    $this->sendRequest($request);
}
```

#### Example 2: POST with Body

**Old Way (removed in 7.0):**

```php
protected function testCreatePet()
{
    $this->makeRequest(
        'POST',
        '/pet',
        201,
        null,
        ['name' => 'Fluffy', 'status' => 'available'],
        []
    );
}
```

**New Way:**

```php
public function testCreatePet()
{
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('POST')
        ->withPath('/pet')
        ->withRequestBody(['name' => 'Fluffy', 'status' => 'available'])
        ->expectStatus(201);

    $this->sendRequest($request);
}
```

#### Example 3: GET with Query Parameters

**Old Way (removed in 7.0):**

```php
protected function testFindPets()
{
    $this->makeRequest(
        'GET',
        '/pet/findByStatus',
        200,
        ['status' => 'available'],
        null,
        []
    );
}
```

**New Way:**

```php
public function testFindPets()
{
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath('/pet/findByStatus')
        ->withQuery(['status' => 'available']);

    $this->sendRequest($request);
}
```

#### Example 4: Request with Headers

**Old Way (removed in 7.0):**

```php
protected function testAuthenticatedRequest()
{
    $this->makeRequest(
        'GET',
        '/pet/1',
        200,
        null,
        null,
        ['Authorization' => 'Bearer token123']
    );
}
```

**New Way:**

```php
public function testAuthenticatedRequest()
{
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath('/pet/1')
        ->withRequestHeader(['Authorization' => 'Bearer token123']);

    $this->sendRequest($request);
}
```

#### Example 5: Complex Request with All Parameters

**Old Way (removed in 7.0):**

```php
protected function testComplexRequest()
{
    $response = $this->makeRequest(
        'POST',
        '/pet/1',
        200,
        ['detailed' => 'true'],
        ['name' => 'Updated Name'],
        ['Authorization' => 'Bearer token123']
    );
}
```

**New Way:**

```php
public function testComplexRequest()
{
    $request = new \ByJG\ApiTools\ApiRequester();
    $response = $request
        ->withMethod('POST')
        ->withPath('/pet/1')
        ->withQuery(['detailed' => 'true'])
        ->withRequestBody(['name' => 'Updated Name'])
        ->withRequestHeader(['Authorization' => 'Bearer token123'])
        ->expectStatus(200);

    $response = $this->sendRequest($request);
}
```

### Additional Benefits of the New Approach

#### 1. Better Assertions

:::tip Multiple Expectations
You can add multiple assertions to your request:
:::

```php title="Example: Multiple expectations"
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withMethod('GET')
    ->withPath('/pet/1')
    ->expectStatus(200)
    ->expectHeaderContains('Content-Type', 'application/json')
    ->expectBodyContains('Fluffy');

$this->sendRequest($request);
```

#### 2. Reusable Request Builders

:::tip Helper Methods
You can create helper methods that return configured requesters:
:::

```php title="Example: Reusable authenticated request"
protected function createAuthenticatedRequest(string $method, string $path): \ByJG\ApiTools\ApiRequester
{
    $request = new \ByJG\ApiTools\ApiRequester();
    return $request
        ->withMethod($method)
        ->withPath($path)
        ->withRequestHeader(['Authorization' => 'Bearer ' . $this->getToken()]);
}

public function testWithHelper()
{
    $request = $this->createAuthenticatedRequest('GET', '/pet/1');
    $this->sendRequest($request);
}
```

#### 3. Response Inspection

:::tip Response Analysis
Both methods return the response, allowing you to inspect it further:
:::

```php title="Example: Inspecting response data"
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withMethod('GET')
    ->withPath('/pet/1');

$response = $this->sendRequest($request);

// Inspect the response
$body = json_decode((string)$response->getBody(), true);
$this->assertEquals('Fluffy', $body['name']);
```

### Timeline

:::caution Deprecation Timeline
- **Version 6.0**:
    - `Schema::getInstance()` deprecated (use `fromJson()`, `fromArray()`, or `fromFile()`)
    - `assertRequest()` deprecated (use `sendRequest()`)
    - `makeRequest()` deprecated (use `ApiRequester` fluent interface)
- **Version 7.0**: all three are removed

:::

### Need Help?

:::info Getting Support
If you encounter issues during migration:

1. Check the [Troubleshooting Guide](troubleshooting.md)
2. Review the [API Reference](functional-tests.md)
3. Open an issue on [GitHub](https://github.com/byjg/php-swagger-test/issues)

:::
