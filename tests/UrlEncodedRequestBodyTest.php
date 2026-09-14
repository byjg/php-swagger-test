<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\MockRequester;
use ByJG\ApiTools\OpenApiValidation;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * Request bodies sent as `application/x-www-form-urlencoded` are parsed and
 * matched against the specification, alongside JSON and multipart.
 */
class UrlEncodedRequestBodyTest extends TestCase
{
    use OpenApiValidation;

    #[Override]
    public function setUp(): void
    {
        $this->setSchema(Schema::fromFile(__DIR__ . '/example/openapi-urlencoded.json'));
    }

    private function okResponse(): Response
    {
        return Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode(["status" => "subscribed"])));
    }

    public function testMatchesAFormBodyAgainstTheSpecification(): void
    {
        $request = new MockRequester($this->okResponse());
        $request
            ->withMethod('POST')
            ->withPath('/subscribe')
            ->withRequestHeader(['Content-Type' => 'application/x-www-form-urlencoded'])
            ->withRequestBody(http_build_query([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]))
            ->expectStatus(200);

        $this->sendRequest($request);
    }

    /**
     * Form values are always strings; numeric types are checked with
     * is_numeric(), so an integer property still matches.
     */
    public function testMatchesANumericPropertySentAsAString(): void
    {
        $request = new MockRequester($this->okResponse());
        $request
            ->withMethod('POST')
            ->withPath('/subscribe')
            ->withRequestHeader(['Content-Type' => 'application/x-www-form-urlencoded'])
            ->withRequestBody(http_build_query([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 42,
            ]))
            ->expectStatus(200);

        $this->sendRequest($request);
    }

    public function testRejectsAFormBodyMissingARequiredProperty(): void
    {
        $request = new MockRequester($this->okResponse());
        $request
            ->withMethod('POST')
            ->withPath('/subscribe')
            ->withRequestHeader(['Content-Type' => 'application/x-www-form-urlencoded'])
            ->withRequestBody(http_build_query(['name' => 'John Doe']))
            ->expectStatus(200);

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Required property 'email'");

        $this->sendRequest($request);
    }

    public function testRejectsAFormBodyWithAnUndeclaredProperty(): void
    {
        $request = new MockRequester($this->okResponse());
        $request
            ->withMethod('POST')
            ->withPath('/subscribe')
            ->withRequestHeader(['Content-Type' => 'application/x-www-form-urlencoded'])
            ->withRequestBody(http_build_query([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'nickname' => 'JD',
            ]))
            ->expectStatus(200);

        $this->expectException(NotMatchedException::class);

        $this->sendRequest($request);
    }

    /**
     * The charset parameter must not defeat the content type detection.
     */
    public function testAcceptsTheContentTypeWithACharsetParameter(): void
    {
        $request = new MockRequester($this->okResponse());
        $request
            ->withMethod('POST')
            ->withPath('/subscribe')
            ->withRequestHeader(['Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8'])
            ->withRequestBody(http_build_query([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]))
            ->expectStatus(200);

        $this->sendRequest($request);
    }
}
