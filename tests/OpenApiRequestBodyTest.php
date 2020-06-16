<?php

namespace Test;

use ByJG\ApiTools\Exception\NotMatchedException;

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
    public function testMatchRequestBody()
    {
        $body = [
            "id" => "10",
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20T17:32:28Z',
            "status" => 'placed',
            "complete" => true
        ];

        $requestParameter = self::openApiSchema()->getRequestParameters('/v2/store/order', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * @expectedException \ByJG\ApiTools\Exception\RequiredArgumentNotFound
     * @expectedExceptionMessage The body is required
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
    public function testMatchRequiredRequestBodyEmpty()
    {
        $requestParameter = self::openApiSchema()->getRequestParameters('/v2/store/order', 'post');
        $this->assertTrue($requestParameter->match(null));
    }

    /**
     * @expectedException \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @expectedExceptionMessage Body is passed but there is no request body definition
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
    public function testMatchInexistantBodyDefinition()
    {
        $body = [
            "id" => "10",
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20T17:32:28Z',
            "status" => 'placed',
            "complete" => true
        ];

        $requestParameter = self::openApiSchema()->getRequestParameters('/v2/pet/1', 'get');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Path expected an integer value
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     */
    public function testMatchDataType()
    {
        self::openApiSchema()->getRequestParameters('/v2/pet/STRING', 'get');
        $this->assertTrue(true);
    }

    // @todo Validate parameters in query
    public function testMatchParameterInQuery()
    {
        self::openApiSchema()->getRequestParameters('/v2/pet/findByStatus?status=pending', 'get');
        $this->assertTrue(true);
    }

    public function testMatchParameterInQuery2()
    {
        self::openApiSchema3()->getRequestParameters('/tests/12345?count=20&offset=2', 'get');
        $this->assertTrue(true);
    }

    /**
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Path expected an integer value
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     */
    public function testMatchParameterInQuery3()
    {
        self::openApiSchema3()->getRequestParameters('/tests/STRING?count=20&offset=2', 'get');
        $this->assertTrue(true);
    }


    /**
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Required property
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
    public function testMatchRequestBodyRequired1()
    {
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
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Value of property 'name' is null, but should be of type 'string'
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
    public function testMatchRequestBodyRequiredNullsNotAllowed()
    {
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\RequiredArgumentNotFound
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
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Required property 'user_uuid'
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
    public function testMatchRequestBodyRequired_Issue21_Required()
    {
        // Missing Request
        $body = [
            "wallet_uuid" => "502a1aa3-5239-4d4b-af09-4dc24ac5f034",
        ];

        $requestParameter = $this->openApiSchema2()->getRequestParameters('/accounts/create', 'post');
        $requestParameter->match($body);
    }

    public function testMatchRequestBodyMatchesStringWithDateFormat()
    {
        // Missing Request
        $body = [
            "date" => "2013-02-12",
        ];

        $requestParameter = $this->openApiSchema()->getRequestParameters('/store-dates', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    public function testMatchRequestBodyMatchesStringWithAnyFormat()
    {
        // Missing Request
        $body = [
            "any_format" => "rock",
        ];

        $requestParameter = $this->openApiSchema()->getRequestParameters('/store-dates', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    public function testMatchRequestBodyMatchesStringWithDateTimeFormat()
    {
        // Missing Request
        $datesTimes = [
            '2000-01-01T01:00:00+1200',
            '2010-10-20T17:32:28Z',
        ];

        foreach ($datesTimes as $time) {
            $body = [
                "date_time" => $time,
            ];

            $requestParameter = $this->openApiSchema()->getRequestParameters('/store-dates', 'post');
            $this->assertTrue($requestParameter->match($body));
        }
    }

    public function testMatchRequestBodyMatchesStringWithDateTimeFormatAndThrowsInvalid()
    {
        // Missing Request
        $datesTimes = [
            '01-01T01:00:00+1200',
            '0000-01-01T01:00:00+1200',
            '2000-30-01T01:00:00+1200',
            '2000-01-01T01:00:00+01',
        ];

        foreach ($datesTimes as $time) {
            $body = [
                "date_time" => $time,
            ];

            $this->expectException(NotMatchedException::class);
            $this->expectExceptionMessage("Value '{$time}' in 'date_time' has invalid format (date-time). ");

            $requestParameter = $this->openApiSchema()->getRequestParameters('/store-dates', 'post');
            $this->assertFalse($requestParameter->match($body));
        }
    }

    public function testMatchRequestBodyMatchesStringWithDateFormatAndThrowsInvalid()
    {
        // Missing Request
        $body = [
            "date" => "test",
        ];

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Value 'test' in 'date' has invalid format (date). ");
        $requestParameter = $this->openApiSchema()->getRequestParameters('/store-dates', 'post');
        $this->assertFalse($requestParameter->match($body));
    }
}
