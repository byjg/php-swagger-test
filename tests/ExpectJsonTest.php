<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\MockRequester;
use ByJG\ApiTools\OpenApiValidation;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for expectJsonContains() and expectJsonPath() methods
 */
class ExpectJsonTest extends TestCase
{
    use OpenApiValidation;

    #[\Override]
    public function setUp(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/rest/openapi.json');
        $this->setSchema($schema);
    }

    /**
     * Test expectJsonContains() with valid JSON response
     */
    public function testExpectJsonContainsSuccess(): void
    {
        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "category" => ["id" => 201, "name" => "dog"],
                "photoUrls" => [],
                "status" => "available"
            ])));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonContains([
                "id" => 1,
                "name" => "Spike",
                "status" => "available"
            ]);

        $this->sendRequest($request);
    }

    /**
     * Test expectJsonContains() with nested array matching
     */
    public function testExpectJsonContainsNestedArraySuccess(): void
    {
        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "category" => ["id" => 201, "name" => "dog"],
                "photoUrls" => [],
                "status" => "available"
            ])));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonContains([
                "category" => ["id" => 201, "name" => "dog"]
            ]);

        $this->sendRequest($request);
    }

    /**
     * Test expectJsonContains() fails with missing key
     */
    public function testExpectJsonContainsMissingKey(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("Expected JSON response to contain key 'nonexistent'");

        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "photoUrls" => []
            ])));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonContains([
                "nonexistent" => "value"
            ]);

        $this->sendRequest($request);
    }

    /**
     * Test expectJsonContains() fails with wrong value
     */
    public function testExpectJsonContainsWrongValue(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("Expected JSON key 'name' to equal \"Fluffy\"");

        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "photoUrls" => []
            ])));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonContains([
                "name" => "Fluffy"
            ]);

        $this->sendRequest($request);
    }

    /**
     * Test expectJsonPath() with simple path
     */
    public function testExpectJsonPathSimpleSuccess(): void
    {
        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "photoUrls" => []
            ])));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonPath("name", "Spike")
            ->expectJsonPath("id", 1);

        $this->sendRequest($request);
    }

    /**
     * Test expectJsonPath() with nested path
     */
    public function testExpectJsonPathNestedSuccess(): void
    {
        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "category" => ["id" => 201, "name" => "dog"],
                "photoUrls" => []
            ])));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonPath("category.id", 201)
            ->expectJsonPath("category.name", "dog");

        $this->sendRequest($request);
    }

    /**
     * Test expectJsonPath() with array index
     */
    public function testExpectJsonPathArrayIndexSuccess(): void
    {
        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "tags" => [
                    ["id" => 1, "name" => "friendly"],
                    ["id" => 2, "name" => "playful"]
                ],
                "photoUrls" => []
            ])));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonPath("tags.0.name", "friendly")
            ->expectJsonPath("tags.1.name", "playful");

        $this->sendRequest($request);
    }

    /**
     * Test expectJsonPath() fails with missing path
     */
    public function testExpectJsonPathMissing(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("JSONPath 'category.id' not found in response");

        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "photoUrls" => []
            ])));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonPath("category.id", 201);

        $this->sendRequest($request);
    }

    /**
     * Test expectJsonPath() fails with wrong value
     */
    public function testExpectJsonPathWrongValue(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage("Expected value at JSONPath 'name' to equal \"Fluffy\"");

        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "photoUrls" => []
            ])));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonPath("name", "Fluffy");

        $this->sendRequest($request);
    }

    // ========================================
    // Edge case tests with MockRequester
    // ========================================

    /**
     * Test expectJsonContains() with invalid JSON
     *
     * Note: The OpenAPI schema validation catches invalid JSON before PHPUnit assertions,
     * so we expect NotMatchedException from schema validation rather than AssertionFailedError.
     */
    public function testExpectJsonContainsInvalidJson(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Value of property '#/components/schemas/Pet' is null");

        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream("This is not JSON"));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonContains(["name" => "Spike"]);

        $this->sendRequest($request);
    }

    /**
     * Test expectJsonPath() with invalid JSON
     *
     * Note: The OpenAPI schema validation catches invalid JSON before PHPUnit assertions,
     * so we expect NotMatchedException from schema validation rather than AssertionFailedError.
     */
    public function testExpectJsonPathInvalidJson(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Value of property '#/components/schemas/Pet' is null");

        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream("This is not JSON"));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->expectStatus(200)
            ->expectJsonPath("name", "Spike");

        $this->sendRequest($request);
    }
}
