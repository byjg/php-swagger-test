---
sidebar_position: 1
---

# Functional Test cases

Swagger Test provides the class `ApiTestCase` for you extend and create a PHPUnit test case. The code will try to
make a request to your API Method and check if the request parameters, status and object returned are conform to
the OpenAPI Specification Provided.

```php
<?php
/**
 * Create a TestCase inherited from SwaggerTestCase
 */
class MyTestCase extends \ByJG\ApiTools\ApiTestCase
{
    public function setUp(): void
    {
        $schema = \ByJG\ApiTools\Base\Schema::fromFile('/path/to/json/definition');
        $this->setSchema($schema);
    }

    /**
     * Test if the REST address /path/for/get/ID with the method GET returns what is
     * documented on the "swagger.json"
     */
    public function testGet()
    {
        $request = new \ByJG\ApiTools\ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath("/path/for/get/1");

        $this->sendRequest($request);
    }

    /**
     * Test if the REST address /path/for/get/NOTFOUND returns a status code 404.
     */
    public function testGetNotFound()
    {
        $request = new \ByJG\ApiTools\ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath("/path/for/get/NOTFOUND")
            ->expectStatus(404);

        $this->sendRequest($request);
    }

    /**
     * Test if the REST address /path/for/post/ID with the method POST
     * and the request object ['name'=>'new name', 'field' => 'value'] will return an object
     * as is documented in the "swagger.json" file
     */
    public function testPost()
    {
        $request = new \ByJG\ApiTools\ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath("/path/for/post/2")
            ->withRequestBody(['name'=>'new name', 'field' => 'value']);

        $this->sendRequest($request);
    }

    /**
     * Test if the REST address /another/path/for/post/{id} with the method POST
     * and the request object ['name'=>'new name', 'field' => 'value'] will return an object
     * as is documented in the "swagger.json" file
     */
    public function testPost2()
    {
        $request = new \ByJG\ApiTools\ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath("/path/for/post/3")
            ->withQuery(['id'=>10])
            ->withRequestBody(['name'=>'new name', 'field' => 'value']);

        $this->sendRequest($request);
    }

}
```

## Convenience Expectation Methods

In addition to OpenAPI schema validation, you can set expectations that will be validated when the request is sent:

### expectStatus()

Expect a specific HTTP status code:

```php
public function testGetWithStatus()
{
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath("/pet/1")
        ->expectStatus(200);  // Validates AND adds PHPUnit assertion

    $this->sendRequest($request);
}
```

### expectJsonContains()

Expect the JSON response to contain specific key-value pairs (subset matching):

```php
public function testPostWithJsonValidation()
{
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('POST')
        ->withPath("/pet")
        ->withRequestBody(['name' => 'Fluffy', 'status' => 'available'])
        ->expectStatus(200)
        ->expectJsonContains([
            'name' => 'Fluffy',
            'status' => 'available'
        ]);

    $this->sendRequest($request);
}
```

### expectJsonPath()

Expect a specific value at a JSONPath expression (dot notation):

```php
public function testNestedJsonValidation()
{
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath("/user/1")
        ->expectStatus(200)
        ->expectJsonPath('user.name', 'John')
        ->expectJsonPath('user.address.city', 'New York')
        ->expectJsonPath('orders.0.id', 123);  // First order ID

    $this->sendRequest($request);
}
```

### expectHeaderContains()

Expect a specific header to contain a value:

```php
public function testResponseHeaders()
{
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath("/pet/1")
        ->expectStatus(200)
        ->expectHeaderContains('Content-Type', 'application/json')
        ->expectHeaderContains('X-RateLimit-Limit', '1000');

    $this->sendRequest($request);
}
```

### expectBodyContains()

Expect the response body to contain a specific string:

```php
public function testBodyContains()
{
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath("/pet/1")
        ->expectStatus(200)
        ->expectBodyContains('Fluffy')
        ->expectBodyContains('"status":"available"');

    $this->sendRequest($request);
}
```

**Note:** This does a simple string contains check on the raw response body. For structured JSON validation, use `expectJsonContains()` or `expectJsonPath()` instead.

## Implicit Status Code Validation

**Important:** `sendRequest()` automatically validates that the response status code matches the expected status (
default 200). This means:

- You don't need to add explicit `expectStatus()` calls unless you want a different status code
- PHPUnit will not report "risky test" warnings - every test has at least one assertion
- If the status code doesn't match, the test will fail with a clear error message

```php
public function testGetWithDefaultStatus()
{
    // This expects status 200 by default and validates it automatically
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath("/pet/1");

    $this->sendRequest($request);  // Validates status code 200
}

public function testGetNotFound()
{
    // Explicitly set expected status to 404
    $request = new \ByJG\ApiTools\ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath("/pet/notfound")
        ->expectStatus(404);

    $this->sendRequest($request);  // Validates status code 404
}
```
