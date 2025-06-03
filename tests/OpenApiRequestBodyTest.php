<?php

namespace Tests;

use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;

class OpenApiRequestBodyTest extends OpenApiBodyTestCase
{
    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
     */
    public function testMatchRequestBody(): void
    {
        $body = [
            "id" => "10",
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true
        ];

        $requestParameter = self::openApiSchema()->getRequestParameters('/v2/store/order', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
     */
    public function testMatchRequiredRequestBodyEmpty(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\RequiredArgumentNotFound::class);
        $this->expectExceptionMessage("The body is required");
        
        $requestParameter = self::openApiSchema()->getRequestParameters('/v2/store/order', 'post');
        $this->assertTrue($requestParameter->match(null));
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
     */
    public function testMatchInexistantBodyDefinition(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\InvalidDefinitionException::class);
        $this->expectExceptionMessage("Body is passed but there is no request body definition");
        
        $body = [
            "id" => "10",
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true
        ];

        $requestParameter = self::openApiSchema()->getRequestParameters('/v2/pet/1', 'get');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testMatchDataType(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Path expected an integer value");
        
        self::openApiSchema()->getRequestParameters('/v2/pet/STRING', 'get');
        $this->assertTrue(true);
    }

    // @todo Validate parameters in query

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testMatchParameterInQuery(): void
    {
        self::openApiSchema()->getRequestParameters('/v2/pet/findByStatus?status=pending', 'get');
        $this->assertTrue(true);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testMatchParameterInQuery2(): void
    {
        self::openApiSchema3()->getRequestParameters('/tests/12345?count=20&offset=2', 'get');
        $this->assertTrue(true);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws InvalidRequestException
     */
    public function testMatchParameterInQuery3(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Path expected an integer value");
        
        self::openApiSchema3()->getRequestParameters('/tests/STRING?count=20&offset=2', 'get');
        $this->assertTrue(true);
    }


    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequired1(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Required property");
        
        $body = [
            "id" => "10",
            "status" => "pending",
        ];

        $requestParameter = self::openApiSchema()->getRequestParameters('/v2/pet', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * It is not OK when allowNullValues is false (as by default) { name: null }
     * https://stackoverflow.com/questions/45575493/what-does-required-in-openapi-really-mean
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequiredNullsNotAllowed(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Value of property 'name' is null, but should be of type 'string'");
        
        $body = [
            "id" => "10",
            "status" => "pending",
            "name" => null,
            "photoUrls" => ["http://example.com/1", "http://example.com/2"]
        ];

        $requestParameter = self::openApiSchema()->getRequestParameters('/v2/pet', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequiredNullsAllowed(): void
    {
        $allowNullValues = true;
        $body = [
            "id" => "10",
            "status" => "pending",
            "name" => null,
            "photoUrls" => ["http://example.com/1", "http://example.com/2"]
        ];

        $requestParameter = self::openApiSchema($allowNullValues)->getRequestParameters('/v2/petnull', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * It is OK: { name: ""}
     * https://stackoverflow.com/questions/45575493/what-does-required-in-openapi-really-mean
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequired3(): void
    {
        $body = [
            "id" => "10",
            "status" => "pending",
            "name" => "",
            "photoUrls" => ["http://example.com/1", "http://example.com/2"]
        ];

        $requestParameter = self::openApiSchema()->getRequestParameters('/v2/pet', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * issue #21
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequired_Issue21(): void
    {
        // Full Request
        $body = [
            "wallet_uuid" => "502a1aa3-5239-4d4b-af09-4dc24ac5f034",
            "user_uuid" => "e7f6c18b-8094-4c2c-9987-1be5b7c46678"
        ];

        $requestParameter = $this->openApiSchema2()->getRequestParameters('/accounts/create', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * Issue #21
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequired_Issue21_Required(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Required property 'user_uuid'");
        
        // Missing Request
        $body = [
            "wallet_uuid" => "502a1aa3-5239-4d4b-af09-4dc24ac5f034",
        ];

        $requestParameter = $this->openApiSchema2()->getRequestParameters('/accounts/create', 'post');
        $requestParameter->match($body);
    }
}
