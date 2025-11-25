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

---

## Framework Integration

Contract testing with custom requesters allows you to test your API without making actual HTTP requests. Here's how to integrate with popular frameworks:

### Laravel Integration

Create a custom requester for Laravel applications:

```php
<?php
use ByJG\ApiTools\AbstractRequester;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ByJG\WebRequest\Psr7\Response as Psr7Response;
use ByJG\WebRequest\Psr7\MemoryStream;

class LaravelRequester extends AbstractRequester
{
    private Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function handleRequest(RequestInterface $request): ResponseInterface
    {
        // Convert PSR-7 request to Laravel request
        $laravelRequest = Request::create(
            (string)$request->getUri(),
            $request->getMethod(),
            [],
            [],
            [],
            $this->transformHeaders($request),
            (string)$request->getBody()
        );

        // Process through Laravel kernel
        $laravelResponse = $this->app->handle($laravelRequest);

        // Convert Laravel response to PSR-7
        return Psr7Response::getInstance($laravelResponse->getStatusCode())
            ->withBody(new MemoryStream($laravelResponse->getContent()))
            ->withHeaders($laravelResponse->headers->all());
    }

    private function transformHeaders(RequestInterface $request): array
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $values[0];
        }
        return $headers;
    }
}
```

**Usage in tests:**

```php
<?php
use Tests\TestCase;

class ApiContractTest extends \ByJG\ApiTools\ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $schema = \ByJG\ApiTools\Base\Schema::fromFile(base_path('openapi.json'));
        $this->setSchema($schema);
    }

    public function testCreateUser(): void
    {
        // Use Laravel requester instead of ApiRequester
        $request = new LaravelRequester(app());
        $request
            ->withMethod('POST')
            ->withPath('/api/users')
            ->withRequestBody([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ])
            ->expectStatus(201);

        $this->sendRequest($request);
    }
}
```

### Symfony Integration

Create a custom requester for Symfony applications:

```php
<?php
use ByJG\ApiTools\AbstractRequester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ByJG\WebRequest\Psr7\Response as Psr7Response;
use ByJG\WebRequest\Psr7\MemoryStream;

class SymfonyRequester extends AbstractRequester
{
    private KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        parent::__construct();
        $this->kernel = $kernel;
    }

    protected function handleRequest(RequestInterface $request): ResponseInterface
    {
        // Convert PSR-7 to Symfony Request
        $symfonyRequest = SymfonyRequest::create(
            (string)$request->getUri(),
            $request->getMethod(),
            [],
            [],
            [],
            $this->transformHeaders($request),
            (string)$request->getBody()
        );

        // Handle through Symfony kernel
        $symfonyResponse = $this->kernel->handle($symfonyRequest);

        // Convert Symfony Response to PSR-7
        return Psr7Response::getInstance($symfonyResponse->getStatusCode())
            ->withBody(new MemoryStream($symfonyResponse->getContent()))
            ->withHeaders($symfonyResponse->headers->all());
    }

    private function transformHeaders(RequestInterface $request): array
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $headers[$key] = $values[0];
        }
        // Add content type separately
        if ($request->hasHeader('Content-Type')) {
            $headers['CONTENT_TYPE'] = $request->getHeaderLine('Content-Type');
        }
        return $headers;
    }
}
```

**Usage in tests:**

```php
<?php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use ByJG\ApiTools\OpenApiValidation;

class ApiContractTest extends KernelTestCase
{
    use OpenApiValidation;

    protected function setUp(): void
    {
        self::bootKernel();

        $schema = \ByJG\ApiTools\Base\Schema::fromFile(
            self::$kernel->getProjectDir() . '/config/openapi.json'
        );
        $this->setSchema($schema);
    }

    public function testCreateUser(): void
    {
        $request = new SymfonyRequester(self::$kernel);
        $request
            ->withMethod('POST')
            ->withPath('/api/users')
            ->withRequestBody([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ])
            ->expectStatus(201);

        $this->sendRequest($request);
    }
}
```

### Slim Framework Integration

For Slim, you can test directly with PSR-7:

```php
<?php
use ByJG\ApiTools\AbstractRequester;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;

class SlimRequester extends AbstractRequester
{
    private App $app;

    public function __construct(App $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function handleRequest(RequestInterface $request): ResponseInterface
    {
        // Slim uses PSR-7 natively, so we can pass the request directly
        return $this->app->handle($request);
    }
}
```

**Usage in tests:**

```php
<?php
use Slim\Factory\AppFactory;

class ApiContractTest extends \ByJG\ApiTools\ApiTestCase
{
    private App $app;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Slim app
        $this->app = AppFactory::create();

        // Add your routes
        require __DIR__ . '/../config/routes.php';

        // Load schema
        $schema = \ByJG\ApiTools\Base\Schema::fromFile(__DIR__ . '/../openapi.json');
        $this->setSchema($schema);
    }

    public function testCreateUser(): void
    {
        $request = new SlimRequester($this->app);
        $request
            ->withMethod('POST')
            ->withPath('/api/users')
            ->withRequestBody([
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ])
            ->expectStatus(201);

        $this->sendRequest($request);
    }
}
```

---

## Benefits of Contract Testing

**Fast Execution:**
- No network overhead
- No web server required
- Tests run in milliseconds

**Isolation:**
- Tests don't depend on external services
- No port conflicts
- Perfect for CI/CD pipelines

**Debugging:**
- Full access to application internals
- Easier to set breakpoints
- Complete stack traces

**Consistent:**
- Same validation as functional tests
- Validates against OpenAPI spec
- Catches contract violations early

---

## Runtime Validation vs Contract Testing

For runtime validation in production middleware, see [Runtime Parameters Validator](runtime-parameters-validator.md#framework-integration).
