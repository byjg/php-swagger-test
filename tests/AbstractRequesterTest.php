<?php

namespace Test;

use ByJG\ApiTools\ApiTestCase;
use ByJG\ApiTools\MockRequester;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Psr7\Response;
use ByJG\Util\Uri;
use MintWare\Streams\MemoryStream;

abstract class AbstractRequesterTest extends ApiTestCase
{
    public function testExpectOK()
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @throws \ByJG\Util\Psr7\MessageException
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Required property 'name'
     */
    public function testExpectError()
    {
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testValidateAssertResponse()
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testValidateAssertResponse404()
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @throws \ByJG\Util\Psr7\MessageException
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Expected empty body for GET 404 /v2/pet/1
     */
    public function testValidateAssertResponse404WithContent()
    {
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @throws \ByJG\Util\Psr7\MessageException
     * @expectedException \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @expectedExceptionMessage Status code not matched: Expected 404, got 522
     */
    public function testValidateAssertResponseNotExpected()
    {
        $expectedResponse = Response::getInstance(522);

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1")
            ->assertResponseCode(404);

        $this->assertRequest($request);
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testValidateAssertHeaderContains()
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @throws \ByJG\Util\Psr7\MessageException
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Does not exists header 'X-Test' with value 'Different'
     */
    public function testValidateAssertHeaderContainsWrongValue()
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
            ->assertHeaderContains("X-Test", "Different");

        $this->assertRequest($request);
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @throws \ByJG\Util\Psr7\MessageException
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Does not exists header 'X-Test' with value 'Different'
     */
    public function testValidateAssertHeaderContainsNonExistent()
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
            ->assertHeaderContains("X-Test", "Different");

        $this->assertRequest($request);
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testValidateAssertBodyContains()
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException
     * @throws \ByJG\Util\Psr7\MessageException
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Body does not contain 'Doris'
     */
    public function testValidateAssertBodyNotContains()
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
            ->assertBodyContains("Doris");

        $this->assertRequest($request);
    }
}
