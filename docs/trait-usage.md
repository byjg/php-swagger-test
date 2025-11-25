---
sidebar_position: 6
---

# Using the OpenApiValidation Trait

The `OpenApiValidation` trait provides OpenAPI/Swagger validation functionality that can be used in any PHP class,
without requiring you to extend `ApiTestCase`.

## Why Use the Trait?

### Problem: Single Inheritance Limitation

PHP only allows single inheritance. If you have an existing test base class, you can't extend both:

```php
// ❌ Can't do both!
class MyApiTest extends MyCompanyBaseTest // Your base class
class MyApiTest extends ApiTestCase        // OpenAPI validation

// PHP doesn't support multiple inheritance
```

### Solution: Use the Trait

The `OpenApiValidation` trait gives you all the OpenAPI validation functionality without inheritance constraints:

```php
// ✅ Now you can have both!
class MyApiTest extends MyCompanyBaseTest
{
    use OpenApiValidation;
    
    // You now have access to all OpenAPI validation methods
}
```

---

## Basic Usage

### Option 1: Extend ApiTestCase (Traditional)

If you don't have a custom base class, just extend `ApiTestCase`:

```php
<?php
use ByJG\ApiTools\ApiTestCase;
use ByJG\ApiTools\Base\Schema;

class MyApiTest extends ApiTestCase
{
    public function setUp(): void
    {
        $schema = Schema::fromFile('/path/to/openapi.json');
        $this->setSchema($schema);
    }

    public function testGetPet()
    {
        $request = new \ByJG\ApiTools\ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath('/pet/1');

        $this->sendRequest($request);
    }
}
```

### Option 2: Use the Trait (Flexible)

If you have a custom base class, use the trait:

```php
<?php
use ByJG\ApiTools\OpenApiValidation;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\ApiRequester;

class MyApiTest extends MyCompanyBaseTest  // Your existing base class
{
    use OpenApiValidation;  // Add OpenAPI validation

    public function setUp(): void
    {
        parent::setUp();  // Call parent setup if needed
        
        $schema = Schema::fromFile('/path/to/openapi.json');
        $this->setSchema($schema);
    }

    public function testGetPet()
    {
        $request = new ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath('/pet/1');

        $this->sendRequest($request);
    }
}
```

---

## Advanced Examples

### Multiple Traits

You can combine multiple traits:

```php
class MyApiTest extends TestCase
{
    use OpenApiValidation;      // OpenAPI validation
    use DatabaseTransactions;   // Laravel trait
    use WithFaker;              // Faker trait
    
    public function testComplexScenario()
    {
        // Use database transactions
        DB::beginTransaction();
        
        // Use faker
        $name = $this->faker->name;
        
        // Use OpenAPI validation
        $request = new ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath('/users')
            ->withRequestBody(['name' => $name])
            ->expectStatus(201);
        
        $this->sendRequest($request);
        
        DB::rollBack();
    }
}
```

### Custom Base Class with Utilities

```php
// Your company's base test class
abstract class CompanyBaseTest extends TestCase
{
    protected function authenticate(): string
    {
        // Company-specific authentication logic
        return 'Bearer token123';
    }
    
    protected function getApiUrl(): string
    {
        return getenv('API_URL') ?: 'http://localhost:8080';
    }
}

// Your API test using both
class UserApiTest extends CompanyBaseTest
{
    use OpenApiValidation;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->setSchema(Schema::fromFile(__DIR__ . '/openapi.json'));
    }
    
    public function testAuthenticatedRequest()
    {
        $request = new ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath('/user/profile')
            ->withRequestHeader([
                'Authorization' => $this->authenticate()  // Use parent method
            ])
            ->expectStatus(200);
        
        $this->sendRequest($request);
    }
}
```

### Testing Framework Agnostic

While the trait works best with PHPUnit (for convenience methods), you can use it with other frameworks:

```php
// Pest PHP example
use ByJG\ApiTools\OpenApiValidation;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\ApiRequester;

uses(OpenApiValidation::class);

beforeEach(function () {
    $this->setSchema(Schema::fromFile('openapi.json'));
});

test('get pet endpoint', function () {
    $request = new ApiRequester();
    $request
        ->withMethod('GET')
        ->withPath('/pet/1');
    
    $this->sendRequest($request);
});
```

---

## Available Methods

When you use the `OpenApiValidation` trait, you get these methods:

### Configuration

```php
// Set the OpenAPI schema to validate against
$this->setSchema(Schema $schema): void

// Set a custom requester (optional)
$this->setRequester(AbstractRequester $requester): void

// Get the current requester instance
$this->getRequester(): AbstractRequester|null
```

### Request Validation

```php
// Send and validate request (recommended)
$this->sendRequest(AbstractRequester $request): ResponseInterface

// Legacy method (deprecated, use sendRequest)
$this->assertRequest(AbstractRequester $request): ResponseInterface
```

### Protected Helpers

```php
// Check that schema is configured (throws exception if not)
$this->checkSchema(): void
```

---

## Common Patterns

### Per-Test Schema Configuration

```php
class MultiApiTest extends TestCase
{
    use OpenApiValidation;
    
    public function testUserApi()
    {
        $this->setSchema(Schema::fromFile('user-api.json'));
        
        $request = new ApiRequester();
        $request->withMethod('GET')->withPath('/users');
        $this->sendRequest($request);
    }
    
    public function testOrderApi()
    {
        $this->setSchema(Schema::fromFile('order-api.json'));
        
        $request = new ApiRequester();
        $request->withMethod('GET')->withPath('/orders');
        $this->sendRequest($request);
    }
}
```

### Per-Request Schema (No setUp Required)

```php
class FlexibleTest extends TestCase
{
    use OpenApiValidation;
    
    // No setUp method needed!
    
    public function testWithInlineSchema()
    {
        $schema = Schema::fromFile('openapi.json');
        
        $request = new ApiRequester();
        $request
            ->withSchema($schema)  // Schema on request
            ->withMethod('GET')
            ->withPath('/pet/1');
        
        // No need to call setSchema() on test class
        $this->sendRequest($request);
    }
}
```

---

## Migration from ApiTestCase

If you're currently extending `ApiTestCase` and want to switch to the trait:

**Before:**

```php
class MyTest extends ApiTestCase
{
    // ...
}
```

**After:**

```php
class MyTest extends MyCustomBase  // Or just TestCase
{
    use OpenApiValidation;
    // Everything else stays the same!
}
```

That's it! All your existing tests will work without any other changes.

---

## PHPUnit Assertions

The trait automatically detects if it's being used in a PHPUnit TestCase and will execute PHPUnit assertions from
convenience methods:

```php
class MyTest extends TestCase  // PHPUnit TestCase
{
    use OpenApiValidation;
    
    public function test()
    {
        $request = new ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath('/pet/1')
            ->expectStatus(200)  // ✅ PHPUnit assertion will run
            ->expectJsonContains(['status' => 'available']);  // ✅ Will run
        
        $this->sendRequest($request);
    }
}
```

If you're not using PHPUnit, the convenience methods will still validate against the OpenAPI schema, but won't register
PHPUnit assertions.

---

## Benefits

✅ **Flexibility** - Use with any base class  
✅ **Composition** - Combine multiple traits  
✅ **Backward Compatible** - ApiTestCase still works  
✅ **Clean Code** - Separate concerns  
✅ **Testable** - Easier to unit test  
✅ **Framework Agnostic** - Works beyond PHPUnit

---

## Summary

- **Use `ApiTestCase`** if you don't have a custom base class (simple)
- **Use `OpenApiValidation` trait** if you have a custom base class (flexible)
- Both provide the exact same functionality
- You can mix and match in the same project
