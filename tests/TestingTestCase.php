<?php

namespace Test;

use ByJG\ApiTools\ApiRequester;
use ByJG\ApiTools\ApiTestCase;
use ByJG\ApiTools\MockRequester;
use ByJG\Util\Helper\RequestMultiPart;
use ByJG\Util\MultiPartItem;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Psr7\Response;
use ByJG\Util\Uri;
use MintWare\Streams\MemoryStream;

/**
 * Class TestingTestCase
 * @package Test
 *
 * IMPORTANT: This class is base for the other tests
 *
 * @see OpenApiTestCaseTest
 * @see SwaggerTestCaseTest
 */
abstract class TestingTestCase extends ApiTestCase
{

    public function testGet()
    {
        $request = new ApiRequester();
        $request
            ->withMethod('GET')
            ->withPath("/pet/1");

        $this->assertRequest($request);
    }

    public function testPost()
    {
        $body = [
            'id' => 1,
            'name' => 'Spike',
            'category' => [ 'id' => 201, 'name' => 'dog'],
            'tags' => [[ 'id' => 2, 'name' => 'blackwhite']],
            'photoUrls' => [],
            'status' => 'available'
        ];

        // Basic Request
        $request = new ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath("/pet")
            ->withRequestBody($body);

        $this->assertRequest($request);


        // PSR7 Request
        $psr7Request = Request::getInstance(new Uri("/pet"))
            ->withMethod("post")
            ->withBody(new MemoryStream(json_encode($body)));

        $expectedResponse = new Response();
        $request = new MockRequester($expectedResponse);
        $request->withPsr7Request($psr7Request);

        $this->assertRequest($request);
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException

     */
    public function testAddError()
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage('Required property \'name\'');

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

        $this->assertRequest($request);
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     * @throws \ByJG\ApiTools\Exception\StatusCodeNotMatchedException

     */
    public function testPostError()
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage('Expected empty body');

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

        $this->assertRequest($request);
    }

    public function testMultipart()
    {
        $multipart = [
            new MultiPartItem("note", "somenote"),
            new MultiPartItem("upfile", file_get_contents(__DIR__ . "/smile.png"), "smile", "image/png")
        ];
        $psr7Requester = RequestMultiPart::build(new Uri("/inventory"), "post", $multipart);

        $request = new ApiRequester();
        $request
            ->withPsr7Request($psr7Requester)
            ->assertResponseCode(200)
            ->assertBodyContains("smile")
            ->assertBodyContains("somenote");

        $this->assertRequest($request);
    }

}
