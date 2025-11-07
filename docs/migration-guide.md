---
sidebar_position: 7
---

# Migration Guide

## Migrating from Schema::getInstance() (Deprecated in 6.0)

The `Schema::getInstance()` method has been deprecated in version 6.0 and will be removed in version 7.0.

### Why the Change?

The method name `getInstance()` suggests a singleton pattern, but it actually creates new instances each time (factory
pattern). This is confusing for developers.

### New Factory Methods

Three new, clearer factory methods have been added:

**Old Way (Deprecated):**

```php
// From JSON string
$schema = Schema::getInstance(file_get_contents('/path/to/spec.json'));

// From array
$schema = Schema::getInstance($arrayData);
```

**New Way:**

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

1. **Clearer intent**: Method name matches what it does (factory, not singleton)
2. **Better error messages**: Each method validates its specific input type
3. **More convenient**: `fromFile()` handles file reading for you
4. **Consistent naming**: Follows common factory method patterns

---

## Migrating from assertRequest() (Deprecated in 6.0)

The `assertRequest()` method has been renamed to `sendRequest()` for clarity.

### Why the Change?

The method name `assertRequest()` is misleading because:

- It returns a value (assertions typically don't return)
- The actual validation happens inside via exceptions
- Developers expect assertion methods to be void

### Migration

**Old Way (Deprecated):**

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

The assertion-style methods (`assertStatus()`, `assertResponseCode()`, `assertBodyContains()`, etc.) have been renamed
to expectation-style methods in version 6.0 for better semantic clarity.

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

## Migrating from makeRequest() (Deprecated in 6.0)

The `makeRequest()` method with 6 parameters has been deprecated in version 6.0 and will be removed in version 7.0.

### Why the Change?

The old `makeRequest()` method had several issues:

- Required passing 6 parameters (even empty ones) making it verbose and error-prone
- Parameters had to be in a specific order
- Not easily extensible for new features
- Less readable code

The new fluent interface with `ApiRequester` provides:

- More readable and self-documenting code
- Only specify the parameters you need
- Easy to extend with new features
- Better IDE autocomplete support

### Migration Examples

#### Example 1: Simple GET Request

**Old Way (Deprecated):**

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

**Old Way (Deprecated):**

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

**Old Way (Deprecated):**

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

**Old Way (Deprecated):**

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

**Old Way (Deprecated):**

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
        ->assertResponseCode(200);
    
    $response = $this->sendRequest($request);
}
```

### Additional Benefits of the New Approach

#### 1. Better Assertions

You can add multiple assertions to your request:

```php
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withMethod('GET')
    ->withPath('/pet/1')
    ->assertResponseCode(200)
    ->assertHeaderContains('Content-Type', 'application/json')
    ->assertBodyContains('Fluffy');

$this->sendRequest($request);
```

#### 2. Reusable Request Builders

You can create helper methods that return configured requesters:

```php
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

Both methods return the response, allowing you to inspect it further:

```php
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

- **Version 6.0**:
    - `Schema::getInstance()` deprecated (use `fromJson()`, `fromArray()`, or `fromFile()`)
    - `assertRequest()` deprecated (use `sendRequest()`)
    - `makeRequest()` deprecated (use `ApiRequester` fluent interface)
- **Version 7.0**: All deprecated methods will be removed

### Need Help?

If you encounter issues during migration, please:

1. Check the [Troubleshooting Guide](troubleshooting.md)
2. Review the [API Reference](functional-tests.md)
3. Open an issue on [GitHub](https://github.com/byjg/php-swagger-test/issues)
