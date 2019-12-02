<?php

namespace Test;

use ByJG\ApiTools\ApiRequester;
use ByJG\ApiTools\ApiTestCase;

class BaseTestCase extends ApiTestCase
{

    protected $filePath;

    public function setUp()
    {
        // This is important!
        parent::setUp();
    }

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
        $request = new ApiRequester();
        $request
            ->withMethod('POST')
            ->withPath("/pet")
            ->withRequestBody([
                'id' => 1,
                'name' => 'Spike',
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
     * @throws \GuzzleHttp\Exception\GuzzleException

     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Required property 'name'
     */
    public function testAddError()
    {
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
     * @throws \GuzzleHttp\Exception\GuzzleException

     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Expected empty body
     */
    public function testPostError()
    {
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
}
