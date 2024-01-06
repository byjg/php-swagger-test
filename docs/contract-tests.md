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

@todo: Explain in the Docs sections the `RestServer` component

## Using it as Unit Test cases

If you want mock the request API and just test the expected parameters, you are sending and
receiving you have to:

### 1. Create the Swagger or OpenAPI Test Schema

```php
<?php
$schema = \ByJG\ApiTools\Base\Schema::getInstance($contentsOfSchemaJson);
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
