---
sidebar_position: 7
---

# Troubleshooting Guide

This guide helps you resolve common issues when using PHP Swagger Test.

## Common Errors

### "Request body provided for '...' but the ... specification does not define a request body"

**Full Error:**

```
Request body provided for 'GET /pet/{petId}' but the OpenAPI 3.0 specification 
does not define a request body for this operation.

Suggestion: Either remove the request body from your test using withRequestBody(), 
or add a 'requestBody' definition to your OpenAPI specification for this endpoint.
```

**Cause:**
You're sending a request body for an operation that doesn't define one in the OpenAPI/Swagger specification.

**Solutions:**

1. **If the endpoint should NOT accept a body** (e.g., GET, DELETE requests):
   Remove the `withRequestBody()` call from your test:

   ```php
   // WRONG - GET requests typically don't have bodies
   $request->withMethod('GET')
       ->withPath('/pet/1')
       ->withRequestBody(['data' => 'value']);  // ← Remove this
   
   // CORRECT
   $request->withMethod('GET')
       ->withPath('/pet/1');
   ```

2. **If the endpoint SHOULD accept a body** (e.g., POST, PUT, PATCH):
   Add the request body definition to your OpenAPI specification:

   **For OpenAPI 3.0:**
   ```yaml
   paths:
     /pet:
       post:
         requestBody:  # ← Add this
           required: true
           content:
             application/json:
               schema:
                 $ref: '#/components/schemas/Pet'
   ```

   **For Swagger 2.0:**
   ```yaml
   paths:
     /pet:
       post:
         parameters:
           - in: body  # ← Add this parameter
             name: body
             required: true
             schema:
               $ref: '#/definitions/Pet'
   ```

---

### "You have to configure a schema for either the request or the testcase"

**Cause:**
No OpenAPI/Swagger schema has been configured for the test case.

**Solution:**
Add schema configuration in your test's `setUp()` method:

```php
class MyTestCase extends \ByJG\ApiTools\ApiTestCase
{
    public function setUp(): void
    {
        $schema = \ByJG\ApiTools\Base\Schema::fromFile('/path/to/openapi.json');
        $this->setSchema($schema);
    }
    
    // Your tests...
}
```

**Alternative Solution:**
Configure schema per-request:

```php
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withSchema($schema)  // ← Set schema on request
    ->withMethod('GET')
    ->withPath('/pet/1');

$this->sendRequest($request);
```

---

### "The body is required but it is empty"

**Cause:**
The OpenAPI specification marks the request body as required, but your test isn't providing one.

**Solution:**
Add a request body to your test:

```php
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withMethod('POST')
    ->withPath('/pet')
    ->withRequestBody([  // ← Add this
        'name' => 'Fluffy',
        'status' => 'available'
    ]);

$this->sendRequest($request);
```

---

### "Path '...' not found in specifications"

**Cause:**
The path you're testing doesn't exist in your OpenAPI/Swagger specification.

**Common Issues:**

1. **Path parameter mismatch:**
   ```php
   // Your spec has: /pet/{petId}
   // But you're testing: /pet/1
   
   // This should work - parameters are replaced automatically
   $request->withPath('/pet/1');  // ✓ Matches /pet/{petId}
   ```

2. **Missing leading slash:**
   ```php
   $request->withPath('pet/1');   // ✗ WRONG
   $request->withPath('/pet/1');  // ✓ CORRECT
   ```

3. **Base path confusion:**
   If your spec defines a base path (Swagger 2.0) or server URL (OpenAPI 3.0),
   don't include it in your test path:

   ```yaml
   # OpenAPI 3.0
   servers:
     - url: https://api.example.com/v1
   
   paths:
     /pet/{petId}:  # ← Use this in your test
   ```

   ```php
   $request->withPath('/pet/1');           // ✓ CORRECT
   $request->withPath('/v1/pet/1');        // ✗ WRONG
   ```

4. **Path doesn't exist in spec:**
   Add the path to your OpenAPI specification:

   ```yaml
   paths:
     /pet/{petId}:  # ← Add this path
       get:
         # ... operation definition
   ```

---

### "Method '...' not found for path '...'"

**Cause:**
The HTTP method you're testing doesn't exist for that path in your specification.

**Solution:**
Either:

1. **Fix your test** to use the correct HTTP method:
   ```php
   $request->withMethod('POST');  // Change to match your spec
   ```

2. **Add the method** to your OpenAPI specification:
   ```yaml
   paths:
     /pet/{petId}:
       get:    # ← Already exists
         # ...
       put:    # ← Add this method
         # ... operation definition
   ```

---

### "Expected status code ..., got ..."

**Cause:**
The API returned a different status code than expected.

**Solution:**

1. **Adjust your expectation** if the new status code is correct:
   ```php
   $request
       ->withMethod('POST')
       ->withPath('/pet')
       ->expectStatus(201);  // ← Adjust this
   ```

2. **Fix your API** if it's returning the wrong status code.

3. **Add the status code** to your OpenAPI specification:
   ```yaml
   paths:
     /pet:
       post:
         responses:
           '201':  # ← Add this response
             description: Pet created
             content:
               application/json:
                 schema:
                   $ref: '#/components/schemas/Pet'
   ```

---

### "Value '...' in '...' not matched in pattern"

**Cause:**
A value doesn't match the regex pattern defined in your OpenAPI specification.

**Example Spec:**

```yaml
schema:
  type: string
  pattern: '^[a-z]+$'  # Only lowercase letters
```

**Solution:**

1. **Fix your test data** to match the pattern:
   ```php
   $request->withRequestBody([
       'status' => 'AVAILABLE'  // ✗ WRONG - uppercase
   ]);
   
   $request->withRequestBody([
       'status' => 'available'  // ✓ CORRECT - lowercase
   ]);
   ```

2. **Fix the pattern** in your spec if it's too restrictive:
   ```yaml
   pattern: '^[a-zA-Z]+$'  # Allow both upper and lowercase
   ```

---

### "Property '...' is required but not found"

**Cause:**
Your request/response is missing a required field defined in the specification.

**Solution:**

1. **Add the missing field** to your test:
   ```php
   $request->withRequestBody([
       'name' => 'Fluffy',
       'status' => 'available',  // ← Don't forget required fields
   ]);
   ```

2. **Make the field optional** in your spec if it shouldn't be required:
   ```yaml
   schema:
     type: object
     required:
       - name
       # Remove 'status' from required if optional
     properties:
       name:
         type: string
       status:
         type: string
   ```

---

### "Additional properties are not allowed"

**Cause:**
You're sending fields that aren't defined in your OpenAPI schema, and `additionalProperties`
is set to `false` (or not set, as false is the default).

**Solution:**

1. **Remove extra fields** from your test:
   ```php
   $request->withRequestBody([
       'name' => 'Fluffy',
       'extraField' => 'value',  // ← Remove this if not in spec
   ]);
   ```

2. **Add the field** to your OpenAPI specification:
   ```yaml
   schema:
     type: object
     properties:
       name:
         type: string
       extraField:  # ← Add this
         type: string
   ```

3. **Allow additional properties** in your spec (not recommended for strict validation):
   ```yaml
   schema:
     type: object
     additionalProperties: true  # ← Allow any extra properties
     properties:
       name:
         type: string
   ```

---

## Debugging Tips

### 1. Enable Verbose Error Output

Ensure PHPUnit is configured to show detailed errors in your `phpunit.xml.dist`:

```xml

<phpunit
        displayDetailsOnTestsThatTriggerErrors="true"
        displayDetailsOnTestsThatTriggerWarnings="true"
        displayDetailsOnTestsThatTriggerNotices="true"
        displayDetailsOnTestsThatTriggerDeprecations="true"
/>
```

### 2. Inspect the Response

Capture the response to see what your API actually returned:

```php
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withMethod('GET')
    ->withPath('/pet/1');

$response = $this->sendRequest($request);

// Debug: print the response
echo "Status: " . $response->getStatusCode() . "\n";
echo "Body: " . (string)$response->getBody() . "\n";
echo "Headers: " . print_r($response->getHeaders(), true) . "\n";
```

### 3. Validate Your OpenAPI Specification

Use online validators to ensure your specification is valid:

- [Swagger Editor](https://editor.swagger.io/)
- [OpenAPI.Tools](https://openapi.tools/)

### 4. Check JSON vs YAML

This library requires OpenAPI specifications in **JSON format**, not YAML.

Convert YAML to JSON:

```bash
# Using yq (https://github.com/mikefarah/yq)
yq eval -o=json openapi.yaml > openapi.json

# Using python
python -c 'import sys, yaml, json; json.dump(yaml.safe_load(sys.stdin), sys.stdout, indent=2)' < openapi.yaml > openapi.json
```

### 5. Test with Mock Requests

Use `MockRequester` to test your specification without making actual HTTP calls:

```php
use ByJG\ApiTools\MockRequester;
use ByJG\WebRequest\Psr7\Response;

$expectedResponse = Response::getInstance(200)
    ->withBody(json_encode(['id' => 1, 'name' => 'Fluffy']));

$request = new MockRequester($expectedResponse);
$request
    ->withMethod('GET')
    ->withPath('/pet/1');

// This validates against the spec without HTTP
$this->sendRequest($request);
```

---

## Getting Help

If you're still stuck:

1. **Check the examples** in the `/tests` directory of this repository
2. **Review the documentation:**
    - [Functional Tests](functional-tests.md)
    - [Contract Tests](contract-tests.md)
    - [Migration Guide](migration-guide.md)
3. **Search existing issues** on [GitHub Issues](https://github.com/byjg/php-swagger-test/issues)
4. **Ask for help** by opening a new issue with:
    - Your OpenAPI specification (or relevant portion)
    - Your test code
    - The full error message
    - PHP and library versions

---

## Common Patterns

### Testing Different Response Codes

```php
// Test success case
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withMethod('GET')
    ->withPath('/pet/1')
    ->expectStatus(200);
$this->sendRequest($request);

// Test not found case
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withMethod('GET')
    ->withPath('/pet/99999')
    ->expectStatus(404);
$this->sendRequest($request);
```

### Testing with Authentication

```php
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withMethod('GET')
    ->withPath('/pet/1')
    ->withRequestHeader([
        'Authorization' => 'Bearer ' . $this->getAuthToken()
    ]);
$this->sendRequest($request);
```

### Testing File Uploads

```php
$request = new \ByJG\ApiTools\ApiRequester();
$request
    ->withMethod('POST')
    ->withPath('/pet/1/uploadImage')
    ->withRequestBody([
        'file' => base64_encode(file_get_contents('/path/to/image.jpg')),
        'filename' => 'image.jpg'
    ]);
$this->sendRequest($request);
```
