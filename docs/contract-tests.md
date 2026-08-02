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
    public function __construct(private MyAppKernel $app)
    {
        parent::__construct();
    }

    protected function handleRequest(RequestInterface $request): ResponseInterface
    {
        return $this->app->handle($request);
    }
}
```

`handleRequest()` is the only method you must implement: it receives a PSR-7 `RequestInterface`
and must return a PSR-7 `ResponseInterface`. Everything else — matching the request body against
the specification, checking the status code, matching the response body — is already done by
`AbstractRequester`.

Frameworks rarely speak PSR-7 on both ends, so in practice this method is where you convert to
and from your framework's own request/response objects. The sections below show that conversion
for Laravel, Symfony and Slim.

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

> **Already packaged:** [`byjg/gluo-laravel`](https://github.com/byjg/php-gluo-laravel) ships this
> requester together with a service provider, configuration, a test trait and a runtime validation
> middleware. `composer require byjg/gluo-laravel byjg/swagger-test` and you are done. The code
> below is the same integration, spelled out for projects that prefer to own it.

```php
<?php
use ByJG\ApiTools\AbstractRequester;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response as Psr7Response;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request as LaravelRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LaravelRequester extends AbstractRequester
{
    public function __construct(private Application $app)
    {
        parent::__construct();
    }

    protected function handleRequest(RequestInterface $request): ResponseInterface
    {
        // Laravel dispatches through the HTTP kernel. Note that
        // Illuminate\Foundation\Application has no handle() method of its own.
        $kernel = $this->app->make(HttpKernel::class);

        $laravelRequest = $this->toLaravelRequest($request);

        $response = $kernel->handle($laravelRequest);
        $kernel->terminate($laravelRequest, $response);

        return $this->toPsr7Response($response);
    }

    private function toLaravelRequest(RequestInterface $request): LaravelRequest
    {
        $body = (string)$request->getBody();

        return LaravelRequest::create(
            (string)$request->getUri(),
            strtoupper($request->getMethod()),
            [],
            [],
            [],
            $this->transformHeaders($request, $body),
            $body
        );
    }

    private function transformHeaders(RequestInterface $request, string $body): array
    {
        $server = [];

        foreach ($request->getHeaders() as $name => $values) {
            $key = strtoupper(str_replace('-', '_', (string)$name));

            // CGI exposes these two without the HTTP_ prefix; prefixing them
            // means Laravel never sees the content type at all.
            if (!in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $key = 'HTTP_' . $key;
            }

            $server[$key] = implode(', ', $values);
        }

        // withRequestBody() encodes arrays as JSON but sets no Content-Type.
        // Without this, Symfony assumes a urlencoded form on writes and
        // $request->input() comes back empty in the controller.
        if ($body !== '' && !isset($server['CONTENT_TYPE'])) {
            $server['CONTENT_TYPE'] = 'application/json';
        }

        return $server;
    }

    private function toPsr7Response(SymfonyResponse $response): ResponseInterface
    {
        $psr7Response = Psr7Response::getInstance($response->getStatusCode());

        foreach ($response->headers->all() as $name => $values) {
            $psr7Response = $psr7Response->withHeader((string)$name, $values);
        }

        $content = $response->getContent();

        // Streamed and binary-file responses return false here; their content
        // only materialises while being sent.
        if ($content === false) {
            ob_start();
            $response->sendContent();
            $content = ob_get_clean();
        }

        return $psr7Response->withBody(new MemoryStream((string)$content));
    }
}
```

**Usage in tests:**

The test must extend Laravel's own `Tests\TestCase`, which boots the application — `base_path()`,
`$this->app` and `actingAs()` all depend on it. Use the `OpenApiValidation` trait to add
`sendRequest()` rather than extending `ApiTestCase`, which extends plain PHPUnit and would leave
you without an application.

```php
<?php
namespace Tests\Feature;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\OpenApiValidation;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    use OpenApiValidation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setSchema(Schema::fromFile(base_path('openapi.json')));
    }

    public function testCreateUser(): void
    {
        // Use Laravel requester instead of ApiRequester
        $request = new LaravelRequester($this->app);
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

        // Convert Symfony Response to PSR-7. PSR-7 messages carry one header
        // at a time; there is no withHeaders().
        $psr7Response = Psr7Response::getInstance($symfonyResponse->getStatusCode());

        foreach ($symfonyResponse->headers->all() as $name => $values) {
            $psr7Response = $psr7Response->withHeader((string)$name, $values);
        }

        return $psr7Response->withBody(new MemoryStream((string)$symfonyResponse->getContent()));
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

Slim speaks PSR-7 natively, so almost nothing has to be translated. One detail does: `Slim\App`
implements `RequestHandlerInterface`, whose `handle()` expects a **`ServerRequestInterface`**,
while `handleRequest()` receives a plain `RequestInterface`. Rebuild it as a `ServerRequest`
before handing it over:

```php
<?php
use ByJG\ApiTools\AbstractRequester;
use ByJG\WebRequest\Psr7\ServerRequest;
use ByJG\Util\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

class SlimRequester extends AbstractRequester
{
    public function __construct(private App $app)
    {
        parent::__construct();
    }

    protected function handleRequest(RequestInterface $request): ResponseInterface
    {
        return $this->app->handle($this->toServerRequest($request));
    }

    private function toServerRequest(RequestInterface $request): ServerRequestInterface
    {
        $uri = new Uri((string)$request->getUri());
        parse_str($uri->getQuery(), $query);

        // withQueryParams() is declared on ServerRequest and returns static,
        // while the wither methods inherited from Request/Message are typed as
        // RequestInterface/MessageInterface. Call the specific one first so the
        // narrower type is not lost along the way.
        $serverRequest = (new ServerRequest($uri))->withQueryParams($query);

        $serverRequest = $serverRequest->withMethod($request->getMethod());
        $serverRequest = $serverRequest->withBody($request->getBody());

        foreach ($request->getHeaders() as $name => $values) {
            $serverRequest = $serverRequest->withHeader((string)$name, $values);
        }

        /** @var ServerRequestInterface $serverRequest */
        return $serverRequest;
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
