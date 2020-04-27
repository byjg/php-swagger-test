<?php

namespace Test;

use ByJG\ApiTools\AbstractRequester;
use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Base\Schema;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractRequesterTest extends TestCase
{
    /** @var MockObject|AbstractRequester */
    private $requester;

    /** @var MockObject|Schema */
    private $schema;

    protected function setUp()
    {
        parent::setUp();

        $this->requester = $this->getMockForAbstractClass(AbstractRequester::class);

        $this->schema = $this->getMockBuilder(Schema::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testDefault()
    {
        // request body part of the schema
        $requestBody = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestBody->expects($this->once())
            ->method('match')
            ->with(null);

        // response body part of the schema
        $responseBody = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->getMock();
        $responseBody->expects($this->once())
            ->method('match')
            ->with(null);

        // set up schema
        $this->schema->method('getServerUrl')
            ->willReturn('https://api.example.com');
        $this->schema->method('getBasePath')
            ->willReturn('/v1');
        $this->schema->method('getRequestParameters')
            ->with(
                '/v1/endpoint',
                'POST'
            )
            ->willReturn($requestBody);
        $this->schema->method('getResponseParameters')
            ->with(
                '/v1/endpoint',
                'POST',
                200
            )
            ->willReturn($responseBody);

        // set up abstract function to validate the request being sent
        $this->requester->expects($this->once())
            ->method('handleRequest')
            ->with($this->isInstanceOf(Request::class))
            /** @var Request $request */
            ->willReturnCallback(function ($request) {
                // validate headers
                $headers = $request->getHeaders();
                $this->assertEquals($headers['Host'], ['api.example.com']);
                $this->assertEquals($headers['Accept'], ['application/json']);
                // validate method
                $this->assertEquals('POST', $request->getMethod());
                // validate URI
                $uri = $request->getUri();
                $this->assertEquals('https', $uri->getScheme());
                $this->assertEquals('', $uri->getUserInfo());
                $this->assertEquals('api.example.com', $uri->getHost());
                $this->assertEquals('/endpoint', $uri->getPath());
                $this->assertEquals('id=42', $uri->getQuery());
                $this->assertEquals('', $uri->getFragment());

                return new Response(200);
            });

        $this->requester->withSchema($this->schema);
        $this->requester->withMethod('POST');
        $this->requester->withPath('/endpoint');
        $this->requester->withQuery(['id' => 42]);

        $res = $this->requester->send();

        $this->assertNull($res);
    }
}
