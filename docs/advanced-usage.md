---
sidebar_position: 7
---

# Advanced Usage

This guide covers advanced features and use cases for PHP Swagger Test.

## Multipart Form Data and File Uploads

PHP Swagger Test supports testing endpoints that accept `multipart/form-data` requests, commonly used for file uploads.

### Basic File Upload

```php
<?php
use ByJG\ApiTools\ApiRequester;

class FileUploadTest extends \ByJG\ApiTools\ApiTestCase
{
    public function testUploadImage(): void
    {
        $imageContent = file_get_contents(__DIR__ . '/fixtures/test-image.jpg');

        $request = new ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath('/pet/1/uploadImage')
            ->withRequestHeader(['Content-Type' => 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW'])
            ->withRequestBody(
                "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\n" .
                "Content-Disposition: form-data; name=\"file\"; filename=\"image.jpg\"\r\n" .
                "Content-Type: application/octet-stream\r\n\r\n" .
                $imageContent . "\r\n" .
                "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\n" .
                "Content-Disposition: form-data; name=\"additionalMetadata\"\r\n\r\n" .
                "Test image upload\r\n" .
                "------WebKitFormBoundary7MA4YWxkTrZu0gW--\r\n"
            )
            ->expectStatus(200);

        $this->sendRequest($request);
    }
}
```

### Simplified File Upload (Base64)

For simpler testing, you can use base64-encoded content:

```php
public function testUploadImageSimplified(): void
{
    $request = new ApiRequester();
    $request
        ->withMethod('POST')
        ->withPath('/pet/1/uploadImage')
        ->withRequestBody([
            'file' => base64_encode(file_get_contents(__DIR__ . '/fixtures/test-image.jpg')),
            'filename' => 'test-image.jpg',
            'mimeType' => 'image/jpeg'
        ])
        ->expectStatus(200);

    $this->sendRequest($request);
}
```

### OpenAPI Specification for File Uploads

Your OpenAPI spec should define file uploads like this:

**OpenAPI 3.0:**

```yaml
paths:
  /pet/{petId}/uploadImage:
    post:
      requestBody:
        required: true
        content:
          multipart/form-data:
            schema:
              type: object
              properties:
                file:
                  type: string
                  format: binary
                additionalMetadata:
                  type: string
```

**Swagger 2.0:**

```yaml
paths:
  /pet/{petId}/uploadImage:
    post:
      consumes:
        - multipart/form-data
      parameters:
        - in: formData
          name: file
          type: file
          required: true
        - in: formData
          name: additionalMetadata
          type: string
```

---

## Custom Server URLs and Environments

### Overriding Server URLs

You can test against different environments by setting the server URL:

```php
<?php
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\ApiRequester;

class MultiEnvironmentTest extends \ByJG\ApiTools\ApiTestCase
{
    public function setUp(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/openapi.json');
        $this->setSchema($schema);
    }

    public function testAgainstDevelopment(): void
    {
        $request = new ApiRequester();
        $request
            ->withMethod('GET')
            // Override the server URL from spec
            ->withRequestHeader(['Host' => 'dev.example.com'])
            ->withPath('/pet/1');

        // Or modify the request URI
        $psr7Request = $request->psr7Request;
        $uri = $psr7Request->getUri()
            ->withScheme('https')
            ->withHost('dev.example.com')
            ->withPort(443);
        $request->withPsr7Request($psr7Request->withUri($uri));

        $this->sendRequest($request);
    }
}
```

### Using Server Variables (OpenAPI 3.0)

For OpenAPI 3.0, you can set server variables dynamically:

```yaml
# openapi.json
servers:
  - url: 'https://{environment}.example.com/v1'
    variables:
      environment:
        default: api
        enum:
          - api
          - api.dev
          - api.staging
```

```php
<?php
use ByJG\ApiTools\OpenApi\OpenApiSchema;

class ServerVariableTest extends \ByJG\ApiTools\ApiTestCase
{
    public function testWithDevelopmentServer(): void
    {
        $schema = OpenApiSchema::fromFile(__DIR__ . '/openapi.json');

        // Set server variable
        $schema->setServerVariable('environment', 'api.dev');

        $this->setSchema($schema);

        // Now getServerUrl() returns 'https://api.dev.example.com/v1'
        $this->assertEquals('https://api.dev.example.com/v1', $schema->getServerUrl());

        $request = new ApiRequester();
        $request->withMethod('GET')->withPath('/pet/1');

        $this->sendRequest($request);
    }
}
```

---

## Working with PSR-7 Requests

PHP Swagger Test is built on PSR-7, allowing seamless integration with PSR-7 compatible frameworks.

### Building Requests from PSR-7

```php
<?php
use ByJG\WebRequest\Psr7\Request;
use ByJG\WebRequest\Psr7\Uri;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\ApiTools\ApiRequester;

class Psr7IntegrationTest extends \ByJG\ApiTools\ApiTestCase
{
    public function testWithPsr7Request(): void
    {
        // Build a complete PSR-7 request
        $uri = new Uri('https://api.example.com/pet/1?detailed=true');

        $psr7Request = Request::getInstance($uri)
            ->withMethod('POST')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer token123')
            ->withBody(new MemoryStream(json_encode([
                'name' => 'Fluffy',
                'status' => 'available'
            ])));

        // Use it with ApiRequester
        $request = new ApiRequester();
        $request->withPsr7Request($psr7Request);

        $response = $this->sendRequest($request);

        // Response is also PSR-7
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Content-Type'));
    }
}
```

### Modifying PSR-7 Requests

```php
public function testModifyPsr7Request(): void
{
    $request = new ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath('/pet/1');

    // Access the underlying PSR-7 request
    $psr7 = $request->psr7Request;

    // Modify it directly
    $psr7 = $psr7
        ->withHeader('X-Custom-Header', 'value')
        ->withHeader('Accept-Language', 'en-US');

    // Update the requester
    $request->withPsr7Request($psr7);

    $this->sendRequest($request);
}
```

---

## Custom HTTP Clients

You can use custom HTTP clients by extending `AbstractRequester`.

### Using Guzzle

```php
<?php
use ByJG\ApiTools\AbstractRequester;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleRequester extends AbstractRequester
{
    private Client $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,  // Disable SSL verification for testing
        ]);
    }

    protected function handleRequest(RequestInterface $request): ResponseInterface
    {
        return $this->client->send($request, [
            'http_errors' => false,  // Don't throw exceptions on 4xx/5xx
        ]);
    }
}
```

**Usage:**

```php
class MyTest extends \ByJG\ApiTools\ApiTestCase
{
    public function testWithGuzzle(): void
    {
        $request = new GuzzleRequester();  // Use custom requester
        $request
            ->withSchema($this->schema)
            ->withMethod('GET')
            ->withPath('/pet/1');

        $this->sendRequest($request);
    }
}
```

### Using Symfony HttpClient

```php
<?php
use ByJG\ApiTools\AbstractRequester;
use Symfony\Component\HttpClient\Psr18Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class SymfonyRequester extends AbstractRequester
{
    private Psr18Client $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Psr18Client();
    }

    protected function handleRequest(RequestInterface $request): ResponseInterface
    {
        return $this->client->sendRequest($request);
    }
}
```

---

## Testing Authenticated Endpoints

### Bearer Token Authentication

```php
<?php
class AuthenticatedTest extends \ByJG\ApiTools\ApiTestCase
{
    private function getAuthToken(): string
    {
        // Get token from authentication endpoint or fixture
        return 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';
    }

    public function testWithBearerToken(): void
    {
        $request = new ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath('/user/profile')
            ->withRequestHeader([
                'Authorization' => 'Bearer ' . $this->getAuthToken()
            ])
            ->expectStatus(200);

        $this->sendRequest($request);
    }
}
```

### API Key Authentication

```php
public function testWithApiKey(): void
{
    $request = new ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath('/pet/1')
        ->withRequestHeader([
            'X-API-Key' => getenv('API_KEY')
        ])
        ->expectStatus(200);

    $this->sendRequest($request);
}
```

### Basic Authentication

```php
public function testWithBasicAuth(): void
{
    $username = 'user';
    $password = 'pass';
    $credentials = base64_encode("$username:$password");

    $request = new ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath('/admin/users')
        ->withRequestHeader([
            'Authorization' => "Basic $credentials"
        ])
        ->expectStatus(200);

    $this->sendRequest($request);
}
```

### Reusable Authentication Helper

```php
<?php
abstract class AuthenticatedTestCase extends \ByJG\ApiTools\ApiTestCase
{
    protected function createAuthenticatedRequest(string $method, string $path): ApiRequester
    {
        $request = new ApiRequester();
        $request
            ->withMethod($method)
            ->withPath($path)
            ->withRequestHeader([
                'Authorization' => 'Bearer ' . $this->getAuthToken()
            ]);

        return $request;
    }

    protected function getAuthToken(): string
    {
        // Implement token retrieval logic
        static $token = null;

        if ($token === null) {
            // Authenticate once and cache
            $authRequest = new ApiRequester();
            $authRequest
                ->withMethod('POST')
                ->withPath('/auth/login')
                ->withRequestBody([
                    'username' => 'testuser',
                    'password' => 'testpass'
                ]);

            $response = $this->sendRequest($authRequest);
            $body = json_decode((string)$response->getBody(), true);
            $token = $body['token'];
        }

        return $token;
    }
}

// Use in tests
class UserApiTest extends AuthenticatedTestCase
{
    public function testGetProfile(): void
    {
        $request = $this->createAuthenticatedRequest('GET', '/user/profile');
        $this->sendRequest($request);
    }
}
```

---

## Testing Pagination

```php
<?php
class PaginationTest extends \ByJG\ApiTools\ApiTestCase
{
    public function testPaginatedEndpoint(): void
    {
        $allItems = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $request = new ApiRequester();
            $request
                ->withMethod('GET')
                ->withPath('/pets')
                ->withQuery([
                    'page' => $page,
                    'limit' => 10
                ])
                ->expectStatus(200);

            $response = $this->sendRequest($request);
            $body = json_decode((string)$response->getBody(), true);

            $allItems = array_merge($allItems, $body['items']);

            $hasMore = $body['hasMore'] ?? false;
            $page++;

            // Safety limit
            if ($page > 10) {
                break;
            }
        }

        $this->assertGreaterThan(0, count($allItems));
    }
}
```

---

## Testing Rate Limiting

```php
<?php
use ByJG\ApiTools\Exception\StatusCodeNotMatchedException;

class RateLimitTest extends \ByJG\ApiTools\ApiTestCase
{
    public function testRateLimiting(): void
    {
        $requests = 0;
        $rateLimited = false;

        for ($i = 0; $i < 100; $i++) {
            try {
                $request = new ApiRequester();
                $request
                    ->withMethod('GET')
                    ->withPath('/pets')
                    ->expectStatus(200);

                $this->sendRequest($request);
                $requests++;

            } catch (StatusCodeNotMatchedException $e) {
                if ($e->getBody()['statusCode'] === 429) {
                    $rateLimited = true;
                    break;
                }
                throw $e;
            }

            usleep(10000); // 10ms delay
        }

        $this->assertTrue(
            $rateLimited,
            "Expected rate limiting after $requests requests"
        );
    }
}
```

---

## Testing with Null Values (Swagger 2.0)

Swagger 2.0 doesn't explicitly support null values. Use `allowNullValues` to handle this:

```php
<?php
use ByJG\ApiTools\Base\Schema;

class NullValueTest extends \ByJG\ApiTools\ApiTestCase
{
    public function setUp(): void
    {
        // Allow null values in responses
        $schema = Schema::fromFile(__DIR__ . '/swagger.json', allowNullValues: true);
        $this->setSchema($schema);
    }

    public function testResponseWithNulls(): void
    {
        $request = new ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath('/pet/1');

        $response = $this->sendRequest($request);

        $body = json_decode((string)$response->getBody(), true);

        // These nulls won't cause validation errors
        $this->assertNull($body['category'] ?? null);
    }
}
```

---

## Environment-Specific Configuration

```php
<?php
class ConfigurableTest extends \ByJG\ApiTools\ApiTestCase
{
    private function getSpecPath(): string
    {
        $env = getenv('TEST_ENV') ?: 'local';

        return match($env) {
            'local' => __DIR__ . '/specs/openapi.local.json',
            'dev' => __DIR__ . '/specs/openapi.dev.json',
            'staging' => __DIR__ . '/specs/openapi.staging.json',
            'production' => __DIR__ . '/specs/openapi.prod.json',
            default => __DIR__ . '/specs/openapi.json',
        };
    }

    public function setUp(): void
    {
        $schema = Schema::fromFile($this->getSpecPath());
        $this->setSchema($schema);
    }

    public function testEndpoint(): void
    {
        // Test runs against environment-specific spec
        $request = new ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath('/pet/1');

        $this->sendRequest($request);
    }
}
```

**Run tests:**

```bash
# Test against local spec
TEST_ENV=local vendor/bin/phpunit

# Test against staging spec
TEST_ENV=staging vendor/bin/phpunit
```

---

## Testing Webhooks and Callbacks

While OpenAPI callbacks are not fully implemented, you can test webhook endpoints:

```php
<?php
class WebhookTest extends \ByJG\ApiTools\ApiTestCase
{
    public function testWebhookEndpoint(): void
    {
        // Simulate webhook payload
        $webhookPayload = [
            'event' => 'pet.updated',
            'petId' => 123,
            'timestamp' => time(),
            'data' => [
                'name' => 'Fluffy',
                'status' => 'sold'
            ]
        ];

        $request = new ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath('/webhooks/pet-updates')
            ->withRequestHeader([
                'X-Webhook-Signature' => hash_hmac('sha256', json_encode($webhookPayload), 'secret')
            ])
            ->withRequestBody($webhookPayload)
            ->expectStatus(200);

        $this->sendRequest($request);
    }
}
```

---

## Related Documentation

- [Functional Tests](functional-tests.md) - Basic testing patterns
- [Contract Tests](contract-tests.md) - Testing with custom requesters
- [Schema Classes](schema-classes.md) - Working with different OpenAPI versions
- [Exception Handling](exceptions.md) - Handling validation errors
