<?php

namespace Tests;

use ByJG\ApiTools\ApiRequester;
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
use ByJG\ApiTools\OpenApiValidation;
use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\Helper\RequestMultiPart;
use ByJG\WebRequest\MultiPartItem;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Request;
use ByJG\WebRequest\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class TestingTestCase
 * @package Test
 *
 * IMPORTANT: This class is base for the other tests
 *
 * @see OpenApiTestCaseTest
 * @see SwaggerTestCaseTest
 */
abstract class TestingTestCase extends TestCase
{
    use OpenApiValidation;

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     * @throws GenericApiException
     */
    public function testGet(): void
    {
        // sendRequest() implicitly asserts status code (200 is default)
        // The expectStatus(200) is optional here since 200 is the default
        $request = new ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath("/pet/1");

        $this->sendRequest($request);
    }

    /**
     * @throws GenericApiException
     * @throws DefinitionNotFoundException
     * @throws NotMatchedException
     * @throws RequiredArgumentNotFound
     * @throws HttpMethodNotFoundException
     * @throws PathNotFoundException
     * @throws StatusCodeNotMatchedException
     * @throws RequestException
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws InvalidDefinitionException
     */
    public function testPost(): void
    {
        $body = [
            'id' => 1,
            'name' => 'Spike',
            'category' => [ 'id' => 201, 'name' => 'dog'],
            'tags' => [[ 'id' => 2, 'name' => 'blackwhite']],
            'photoUrls' => [],
            'status' => 'available'
        ];

        // Basic Request with status expectation
        $request = new ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath("/pet")
            ->withRequestBody($body)
            ->expectStatus(200);

        $response = $this->sendRequest($request);

        // Additional validation of response structure
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($responseBody, 'Response should be a valid JSON array');


        // PSR7 Request
        $psr7Request = Request::getInstance(new Uri("/pet"))
            ->withMethod("post")
            ->withBody(new MemoryStream(json_encode($body)));

        $expectedResponse = new Response();
        $request = new MockRequester($expectedResponse);
        $request->withPsr7Request($psr7Request);

        $this->sendRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testAddError(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Required property 'name'");
        
        $request = new ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath("/pet")
            ->withRequestBody([
                'id' => 1,
                'category' => [ 'id' => 201, 'name' => 'dog'],
                'tags' => [[ 'id' => 2, 'name' => 'blackwhite']],
                'photoUrls' => [],
                'status' => 'available'
            ]);

        $this->sendRequest($request);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function testPostError(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Expected empty body");
        
        $request = new ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath("/pet")
            ->withRequestBody([
                'id' => 999, // <== The API will generate an invalid response for this ID
                'name' => 'Spike',
                'category' => [ 'id' => 201, 'name' => 'dog'],
                'tags' => [[ 'id' => 2, 'name' => 'blackwhite']],
                'photoUrls' => [],
                'status' => 'available'
            ]);

        $this->sendRequest($request);
    }

    /**
     * @throws GenericApiException
     * @throws PathNotFoundException
     * @throws DefinitionNotFoundException
     * @throws StatusCodeNotMatchedException
     * @throws RequestException
     * @throws NotMatchedException
     * @throws RequiredArgumentNotFound
     * @throws InvalidRequestException
     * @throws MessageException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     */
    public function testMultipart(): void
    {
        $multipart = [
            new MultiPartItem("note", "somenote"),
            new MultiPartItem("upfile", file_get_contents(__DIR__ . "/smile.png"), "smile", "image/png")
        ];
        $psr7Requester = RequestMultiPart::build(new Uri("/inventory"), "post", $multipart);

        $request = new ApiRequester();
        $request
            ->withPsr7Request($psr7Requester)
            ->expectStatus(200)
            ->expectBodyContains("smile")
            ->expectBodyContains("somenote");

        $this->sendRequest($request);
    }

}
