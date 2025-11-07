---
sidebar_position: 2
---

# Contract Tests

Sometimes, you want to run functional tests without making the actual HTTP
requests and without setting up a webserver for that. Instead, you forward the
requests to the routing of your application kernel which lives in the same
process as the functional tests. In order to do that, you need a bit of
glue code based on the `AbstractRequester` baseclass:

```php
class MyAppRequester extends ByJG\ApiTools\AbstractRequester
{
    /** @var MyAppKernel */
    private $app;

    public function __construct(MyAppKernel $app)
    {
        parent::construct();
        $this->app = $app;
    }

    protected function handleRequest(RequestInterface $request)
    {
        return $this->app->handle($request);
    }
}
```

You now use an instance of this class in place of the `ApiRequester` class from the examples above. 
Of course, if you need to apply changes to the request, or the response in order
to fit your framework, this is exactly the right place to do it.

## Using with RestServer component

The [byjg/restserver](https://github.com/byjg/php-restserver) component can be used to create a server that handles
requests based on OpenAPI/Swagger specifications. This is useful for testing your API without setting up a full web
server.

```php
<?php
use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\Route\OpenApiRouteList;

// Load the OpenAPI/Swagger specification
$specification = '/path/to/specification.json';

// Create a route definition based on the specification
$routeDefinition = new OpenApiRouteList($specification);

// Create a request handler and handle the routes
$restServer = new HttpRequestHandler();
$restServer->handle($routeDefinition);
```

This code will create a server that handles requests according to the routes defined in your OpenAPI/Swagger
specification.

## Using it as Unit Test cases

If you want mock the request API and just test the expected parameters, you are sending and
receiving you have to:

### 1. Create the Swagger or OpenAPI Test Schema

```php
<?php
$schema = \ByJG\ApiTools\Base\Schema::fromJson($contentsOfSchemaJson);
// Or load directly from a file
$schema = \ByJG\ApiTools\Base\Schema::fromFile('/path/to/specification.json');
```

### 2. Get the definitions for your path

```php
<?php
$path = '/path/to/method';
$statusExpected = 200;
$method = 'POST';

// Returns a SwaggerRequestBody instance
$bodyRequestDef = $schema->getRequestParameters($path, $method);

// Returns a SwaggerResponseBody instance
$bodyResponseDef = $schema->getResponseParameters($path, $method, $statusExpected);
```

### 3. Match the result

```php
<?php
if (!empty($requestBody)) {
    $bodyRequestDef->match($requestBody);
}
$bodyResponseDef->match($responseBody);
```

If the request or response body does not match with the definition an exception NotMatchException will
be thrown.
