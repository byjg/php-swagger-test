---
sidebar_position: 3
---

# Runtime Parameters Validator

PHP Swagger Test can be used as a **runtime validator** in production applications to validate API requests and responses against your OpenAPI specification. This provides automatic validation without writing manual validation code.

## Why Use Runtime Validation?

**Benefits:**
- **Automatic validation** - No need to write validation code manually
- **Always in sync** - Validation rules come directly from your OpenAPI spec
- **Consistent** - Same validation in tests and production
- **Documentation as code** - Your spec is the single source of truth
- **Early error detection** - Catch invalid data before processing

**Use Cases:**
- Validate incoming API requests before processing
- Validate outgoing API responses before sending
- Validate data in API middleware
- Validate webhook payloads
- Integration with frameworks (Laravel, Symfony, Slim)

---

## Basic Request Validation

Validate an incoming request body before processing:

```php
<?php
use ByJG\ApiTools\Base\Schema;

// Load your OpenAPI spec
$schema = Schema::fromFile(__DIR__ . '/openapi.json');

// Get the request definition
$path = '/api/users';
$method = 'POST';
$bodyRequestDef = $schema->getRequestParameters($path, $method);

// Validate the request body
$requestBody = json_decode(file_get_contents('php://input'), true);

try {
    $bodyRequestDef->match($requestBody);
    // Request is valid, continue processing
} catch (\ByJG\ApiTools\Exception\BaseException $e) {
    // Request is invalid
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid request',
        'message' => $e->getMessage()
    ]);
    exit;
}
```

---

## Basic Response Validation

Validate a response before sending it to the client:

```php
<?php
use ByJG\ApiTools\Base\Schema;

$schema = Schema::fromFile(__DIR__ . '/openapi.json');

// Process your request and get the result
$result = $userService->createUser($userData);

// Validate the response
$path = '/api/users';
$method = 'POST';
$statusCode = 201;

$bodyResponseDef = $schema->getResponseParameters($path, $method, $statusCode);

try {
    $bodyResponseDef->match($result);
    // Response is valid
    http_response_code($statusCode);
    echo json_encode($result);
} catch (\ByJG\ApiTools\Exception\BaseException $e) {
    // Response doesn't match spec - this is a server error
    error_log("Response validation failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
```

---

## Middleware Integration

### PSR-15 Middleware

```php
<?php
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\BaseException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OpenApiValidationMiddleware implements MiddlewareInterface
{
    private Schema $schema;

    public function __construct(string $specPath)
    {
        $this->schema = Schema::fromFile($specPath);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Validate request
        try {
            $requestBody = json_decode($request->getBody()->getContents(), true);
            $bodyRequestDef = $this->schema->getRequestParameters($path, $method);
            $bodyRequestDef->match($requestBody);
        } catch (BaseException $e) {
            return new \Laminas\Diactoros\Response\JsonResponse([
                'error' => 'Invalid request',
                'message' => $e->getMessage()
            ], 400);
        }

        // Process request
        $response = $handler->handle($request);

        // Validate response (optional in production)
        if (getenv('VALIDATE_RESPONSES') === 'true') {
            try {
                $responseBody = json_decode($response->getBody()->getContents(), true);
                $bodyResponseDef = $this->schema->getResponseParameters(
                    $path,
                    $method,
                    $response->getStatusCode()
                );
                $bodyResponseDef->match($responseBody);
            } catch (BaseException $e) {
                error_log("Response validation failed: " . $e->getMessage());
                // Don't fail in production, just log
            }
        }

        return $response;
    }
}
```

**Usage:**

```php
$app->add(new OpenApiValidationMiddleware(__DIR__ . '/openapi.json'));
```

---

## Framework Integration

### Laravel Integration

See [Contract Tests - Framework Integration](contract-tests.md#framework-integration) for Laravel examples.

### Symfony Integration

See [Contract Tests - Framework Integration](contract-tests.md#framework-integration) for Symfony examples.

### Slim Framework Integration

```php
<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\BaseException;

$app = AppFactory::create();

$schema = Schema::fromFile(__DIR__ . '/openapi.json');

// Validation middleware
$app->add(function (Request $request, $handler) use ($schema) {
    $path = $request->getUri()->getPath();
    $method = $request->getMethod();

    try {
        $body = json_decode($request->getBody()->getContents(), true);
        $bodyRequestDef = $schema->getRequestParameters($path, $method);
        $bodyRequestDef->match($body ?? []);
    } catch (BaseException $e) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'error' => 'Validation failed',
            'message' => $e->getMessage()
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    return $handler->handle($request);
});

$app->run();
```

---

## Webhook Validation

Validate incoming webhook payloads:

```php
<?php
use ByJG\ApiTools\Base\Schema;

class WebhookHandler
{
    private Schema $schema;

    public function __construct()
    {
        $this->schema = Schema::fromFile(__DIR__ . '/webhook-spec.json');
    }

    public function handlePetUpdated(array $payload): void
    {
        // Validate webhook payload
        try {
            $bodyRequestDef = $this->schema->getRequestParameters(
                '/webhooks/pet-updated',
                'POST'
            );
            $bodyRequestDef->match($payload);
        } catch (\ByJG\ApiTools\Exception\BaseException $e) {
            error_log("Invalid webhook payload: " . $e->getMessage());
            http_response_code(400);
            return;
        }

        // Process valid webhook
        $this->processPetUpdate($payload);
        http_response_code(200);
    }

    private function processPetUpdate(array $payload): void
    {
        // Your business logic here
    }
}
```

---

## Performance Considerations

### Cache the Schema

Load the schema once and reuse it:

```php
<?php
class SchemaCache
{
    private static ?Schema $schema = null;

    public static function getSchema(): Schema
    {
        if (self::$schema === null) {
            self::$schema = Schema::fromFile(__DIR__ . '/openapi.json');
        }
        return self::$schema;
    }
}

// Use in your code
$schema = SchemaCache::getSchema();
```

### Conditional Validation

Only validate in development/staging:

```php
<?php
if (getenv('APP_ENV') !== 'production') {
    try {
        $bodyRequestDef->match($requestBody);
    } catch (BaseException $e) {
        // Strict validation in non-production
        throw $e;
    }
} else {
    // Optional: Log but don't fail in production
    try {
        $bodyRequestDef->match($requestBody);
    } catch (BaseException $e) {
        error_log("Validation warning: " . $e->getMessage());
        // Continue processing
    }
}
```

---

## Error Handling Best Practices

For detailed exception handling, see [Exception Handling](exceptions.md#best-practices-for-exception-handling).

### Return Detailed Errors in Development

```php
<?php
use ByJG\ApiTools\Exception\BaseException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;
use ByJG\ApiTools\Exception\NotMatchedException;

try {
    $bodyRequestDef->match($requestBody);
} catch (RequiredArgumentNotFound $e) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required field',
        'message' => $e->getMessage(),
        'provided_body' => getenv('APP_DEBUG') ? $e->getBody() : null
    ]);
} catch (NotMatchedException $e) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Validation failed',
        'message' => $e->getMessage(),
        'provided_body' => getenv('APP_DEBUG') ? $e->getBody() : null
    ]);
} catch (BaseException $e) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid request',
        'message' => $e->getMessage()
    ]);
}
```

### Sanitize Errors for Production

```php
<?php
function handleValidationError(BaseException $e): array
{
    if (getenv('APP_ENV') === 'production') {
        // Generic error for production
        return [
            'error' => 'Validation failed',
            'message' => 'The request did not match the expected format'
        ];
    }

    // Detailed error for development
    return [
        'error' => 'Validation failed',
        'message' => $e->getMessage(),
        'body' => $e->getBody(),
        'trace' => $e->getTraceAsString()
    ];
}
```

---

## Complete Example: API Endpoint

Here's a complete example of a validated API endpoint:

```php
<?php
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\BaseException;

class UserApi
{
    private Schema $schema;
    private PDO $db;

    public function __construct()
    {
        $this->schema = Schema::fromFile(__DIR__ . '/openapi.json');
        $this->db = new PDO(/* ... */);
    }

    public function createUser(): void
    {
        // Get request body
        $requestBody = json_decode(file_get_contents('php://input'), true);

        // Validate request
        try {
            $bodyRequestDef = $this->schema->getRequestParameters('/users', 'POST');
            $bodyRequestDef->match($requestBody);
        } catch (BaseException $e) {
            $this->sendError(400, 'Invalid request', $e->getMessage());
            return;
        }

        // Process request
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO users (name, email) VALUES (:name, :email)'
            );
            $stmt->execute([
                'name' => $requestBody['name'],
                'email' => $requestBody['email']
            ]);

            $userId = $this->db->lastInsertId();

            // Prepare response
            $responseBody = [
                'id' => (int)$userId,
                'name' => $requestBody['name'],
                'email' => $requestBody['email'],
                'created_at' => date('c')
            ];

            // Validate response
            try {
                $bodyResponseDef = $this->schema->getResponseParameters('/users', 'POST', 201);
                $bodyResponseDef->match($responseBody);
            } catch (BaseException $e) {
                error_log("Response validation failed: " . $e->getMessage());
                // Log but continue in production
            }

            // Send response
            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode($responseBody);

        } catch (\PDOException $e) {
            $this->sendError(500, 'Database error', $e->getMessage());
        }
    }

    private function sendError(int $code, string $error, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $error,
            'message' => getenv('APP_DEBUG') ? $message : 'An error occurred'
        ]);
    }
}

// Handle request
$api = new UserApi();
$api->createUser();
```

---

## Related Documentation

- [Functional Tests](functional-tests.md) - Using validation in tests
- [Exception Handling](exceptions.md) - Understanding validation exceptions
- [Schema Classes](schema-classes.md) - Working with OpenAPI schemas
- [Contract Tests](contract-tests.md) - Framework integration examples
