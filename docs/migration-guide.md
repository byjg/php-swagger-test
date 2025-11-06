---
sidebar_position: 6
---

# Migration Guide

## Migrating from makeRequest() (Deprecated in 6.5)

The `makeRequest()` method with 6 parameters has been deprecated in version 6.5 and will be removed in version 7.0.

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
    
    $this->assertRequest($request);
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
        ->assertResponseCode(201);
    
    $this->assertRequest($request);
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
    
    $this->assertRequest($request);
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
    
    $this->assertRequest($request);
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
    
    $response = $this->assertRequest($request);
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

$this->assertRequest($request);
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
    $this->assertRequest($request);
}
```

#### 3. Response Inspection

Both methods return the response, allowing you to inspect it further:

```php
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withMethod('GET')
    ->withPath('/pet/1');

$response = $this->assertRequest($request);

// Inspect the response
$body = json_decode((string)$response->getBody(), true);
$this->assertEquals('Fluffy', $body['name']);
```

### Timeline

- **Version 6.5**: `makeRequest()` deprecated, migration recommended
- **Version 7.0**: `makeRequest()` will be removed

### Need Help?

If you encounter issues during migration, please:

1. Check the [Troubleshooting Guide](troubleshooting.md)
2. Review the [API Reference](functional-tests.md)
3. Open an issue on [GitHub](https://github.com/byjg/php-swagger-test/issues)
