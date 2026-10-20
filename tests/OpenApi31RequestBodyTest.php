<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;
use PHPUnit\Framework\TestCase;

class OpenApi31RequestBodyTest extends TestCase
{
    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/example/openapi31.json');
    }

    public function testRequestBodyMatchValid(): void
    {
        $requestBody = $this->schema->getRequestParameters('/users', 'post');

        $validBody = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    // Note: Nullable type array tests will be added in Phase 2
    // public function testRequestBodyMatchNullableEmail(): void

    public function testRequestBodyMatchMissingOptionalField(): void
    {
        $requestBody = $this->schema->getRequestParameters('/users', 'post');

        $validBody = [
            'id' => 1,
            'name' => 'John Doe'
            // email is optional, so it can be omitted
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testRequestBodyMatchMissingRequiredField(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/users', 'post');

        $invalidBody = [
            'id' => 1
            // Missing required 'name' field
        ];

        $requestBody->match($invalidBody);
    }

    public function testRequestBodyMatchInvalidType(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/users', 'post');

        $invalidBody = [
            'id' => 'not-a-number',  // Should be integer
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $requestBody->match($invalidBody);
    }

    public function testRequestBodyRequiredButEmpty(): void
    {
        $this->expectException(RequiredArgumentNotFound::class);
        $this->expectExceptionMessage('The body is required');

        $requestBody = $this->schema->getRequestParameters('/users', 'post');
        $requestBody->match(null);
    }

    public function testRequestBodyNotDefinedButProvided(): void
    {
        $this->expectException(InvalidDefinitionException::class);
        $this->expectExceptionMessage("Request body provided for 'get /users' but the OpenAPI 3.1 specification does not define a request body for this operation");

        $requestBody = $this->schema->getRequestParameters('/users', 'get');
        $requestBody->match(['name' => 'John Doe']);
    }

    public function testRequestBodyNotDefinedAndNotProvided(): void
    {
        $requestBody = $this->schema->getRequestParameters('/users', 'get');
        $this->assertTrue($requestBody->match(null));
    }
}
