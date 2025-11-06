<?php

namespace Tests;

use ByJG\ApiTools\ApiTestCase;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericApiException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;
use ByJG\ApiTools\Exception\StatusCodeNotMatchedException;
use ByJG\ApiTools\MockRequester;
use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Request;
use ByJG\WebRequest\Psr7\Response;

abstract class AbstractRequester extends ApiTestCase
{
    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     * @throws GenericApiException
     */
    public function testExpectOK(): void
    {
        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "photoUrls" => []
            ])));

        // Basic Request
        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1");

        $this->assertRequest($request);

        // PSR7 Request
        $psr7Request = Request::getInstance(new Uri("/pet/1"))
            ->withMethod("get");

        $request = new MockRequester($expectedResponse);
        $request->withPsr7Request($psr7Request);

        $this->assertRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testExpectError(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Required property 'name'");
        
        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "photoUrls" => []
            ])));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1");

        $this->assertRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testValidateAssertResponse(): void
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
            ->assertResponseCode(200);

        $this->assertRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testValidateAssertResponse404(): void
    {
        $expectedResponse = Response::getInstance(404);

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->assertResponseCode(404);

        $this->assertRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testValidateAssertResponse404WithContent(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Expected empty body for GET 404 /v2/pet/1");
        
        $expectedResponse = Response::getInstance(404)
            ->withBody(new MemoryStream('{"error":"not found"}'));

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->assertResponseCode(404);

        $this->assertRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testValidateAssertResponseNotExpected(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\StatusCodeNotMatchedException::class);
        $this->expectExceptionMessage("Status code not matched: Expected 404, got 522");
        
        $expectedResponse = Response::getInstance(522);

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->assertResponseCode(404);

        $this->assertRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testValidateAssertHeaderContains(): void
    {
        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "photoUrls" => []
            ])))
            ->withHeader("X-Test", "Some Value to test");

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->assertResponseCode(200)
            ->assertHeaderContains("X-Test", "Value");

        $this->assertRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testValidateAssertHeaderContainsWrongValue(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Does not exists header 'X-Test' with value 'Different'");
        
        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "photoUrls" => []
            ])))
            ->withHeader("X-Test", "Some Value to test");

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->assertResponseCode(200)
            ->assertHeaderContains("X-Test", "Different");

        $this->assertRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testValidateAssertHeaderContainsNonExistent(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Does not exists header 'X-Test' with value 'Different'");
        
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
            ->assertResponseCode(200)
            ->assertHeaderContains("X-Test", "Different");

        $this->assertRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testValidateAssertBodyContains(): void
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
            ->assertResponseCode(200)
            ->assertBodyContains("Spike");

        $this->assertRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequestException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testValidateAssertBodyNotContains(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Body does not contain 'Doris'");
        
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
            ->assertResponseCode(200)
            ->assertBodyContains("Doris");

        $this->assertRequest($request);
    }
}
