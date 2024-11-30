<?php

namespace Tests;

use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericSwaggerException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;

class OpenApiRequestBodyTest extends OpenApiBodyTestCase
{
    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchRequestBody()
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
     *
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchRequiredRequestBodyEmpty()
    {
        $this->expectException(RequiredArgumentNotFound::class);
        $this->expectExceptionMessage("The body is required");
        
        $requestParameter = self::openApiSchema()->getRequestParameters('/v2/store/order', 'post');
        $this->assertTrue($requestParameter->match(null));
    }

    /**
     *
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchInexistantBodyDefinition()
    {
        $this->expectException(InvalidDefinitionException::class);
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
     *
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testMatchDataType()
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Expected 'petId' to be numeric, but found 'STRING'");
        
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
    public function testMatchParameterInQuery()
    {
        self::openApiSchema()->getRequestParameters('/v2/pet/findByStatus?status=pending', 'get');
        $this->assertTrue(true);
    }

    public function testMatchParameterInQueryRequired()
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Value of property 'status' is null, but should be of type 'array'");

        self::openApiSchema()->getRequestParameters('/v2/pet/findByStatus', 'get', "");
    }

    public function testMatchParameterInQueryRequired2()
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Value of property 'username' is null, but should be of type 'string");

        self::openApiSchema()->getRequestParameters('/user/login', 'get', "");
    }

    public function testMatchParameterInQueryRequired3()
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Value of property 'password' is null, but should be of type 'string");

        self::openApiSchema()->getRequestParameters('/user/login', 'get', "username=test");
    }

    public function testMatchParameterInQueryNotDefined()
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("There are parameters that are not defined in the schema: notdefined");

        self::openApiSchema()->getRequestParameters('/user/login', 'get', "username=test&password=test&notdefined=error");
    }

    public function testMatchParameterInQueryNotValid()
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Value 'ABC' in 'status' not matched in ENUM");

        self::openApiSchema()->getRequestParameters('/v2/pet/findByStatus', 'get', "status=ABC");
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testMatchParameterInQuery2()
    {
        self::openApiSchema3()->getRequestParameters('/tests/12345', 'get', "count=20&offset=2");
        $this->assertTrue(true);
    }

    /**
     *
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws InvalidRequestException
     */
    public function testMatchParameterInQuery3()
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Expected 'test_id' to be numeric, but found 'STRING'");

        self::openApiSchema3()->getRequestParameters('/tests/STRING', 'get', "count=20&offset=2");
        $this->assertTrue(true);
    }

    public function testMatchParameterInQuery4()
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Expected 'count' to be numeric, but found 'ABC'");

        self::openApiSchema3()->getRequestParameters('/tests/12345', 'get', "count=ABC&offset=2");
        $this->assertTrue(true);
    }

    public function testMatchParameterInQuery5()
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Expected 'offset' to be numeric, but found 'ABC'");

        self::openApiSchema3()->getRequestParameters('/tests/12345', 'get', "count=20&offset=ABC");
        $this->assertTrue(true);
    }

    public function testMatchParameterInQuery6()
    {
        self::openApiSchema3()->getRequestParameters('/tests/12345', 'get');
        $this->assertTrue(true);
    }

    /**
     *
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequired1()
    {
        $this->expectException(NotMatchedException::class);
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
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequiredNullsNotAllowed()
    {
        $this->expectException(NotMatchedException::class);
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
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequiredNullsAllowed()
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
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequired3()
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
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequired_Issue21()
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
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchRequestBodyRequired_Issue21_Required()
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Required property 'user_uuid'");
        
        // Missing Request
        $body = [
            "wallet_uuid" => "502a1aa3-5239-4d4b-af09-4dc24ac5f034",
        ];

        $requestParameter = $this->openApiSchema2()->getRequestParameters('/accounts/create', 'post');
        $requestParameter->match($body);
    }
}
