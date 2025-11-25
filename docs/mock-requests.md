---
sidebar_position: 4
---

# Mock Requests

PHP Swagger has the class `MockRequester` with exact the same functionalities of `ApiRequester` class. The only
difference is the `MockRequester` don't need to request to a real endpoint.

This is used to validate request and response against your OpenAPI spec without running any server code.

```php
<?php
class MyTest extends ApiTestCase
{
    public function testExpectOK()
    {
        $expectedResponse = \ByJG\WebRequest\Psr7\Response::getInstance(200)
            ->withBody(new \ByJG\WebRequest\Psr7\MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "photoUrls" => []
            ])));

        // The MockRequester does not send the request to a real endpoint
        // Just returning the expected Response object sent in the constructor
        $request = new \ByJG\ApiTools\MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1");

        $this->sendRequest($request); // That should be "True" based on the specification
    }
}
```

## Integration with PSR7

You can populate the `ApiRequester`/`MockRequester` with the information provided by the `RequestInterface` PSR7 interface.

e.g.

```php
<?php

$psr7Request = \ByJG\WebRequest\Psr7\Request::getInstance(new Uri("/method_to_be_tested?param1=value1"))
    ->withMethod("GET")
    ->withBody('{"foo":"bar"}');

$request = new \ByJG\ApiTools\ApiRequester();
$request->withPsr7Request($psr7Request);

// Return a ResponseInterface PSR7 component
$response = $request->send();
```
