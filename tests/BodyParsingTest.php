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
 * Request and response bodies are parsed by their Content-Type the same way before they are
 * matched: JSON and any "+json" type, XML and any "+xml" type, and anything else as a string.
 */
class BodyParsingTest extends TestCase
{
    use OpenApiValidation;

    #[Override]
    public function setUp(): void
    {
        $title = ['schema' => [
            'type' => 'object',
            'required' => ['title'],
            'properties' => ['title' => ['type' => 'string']],
        ]];
        $text = ['schema' => ['type' => 'string', 'maxLength' => 5]];
        $get = fn (array $content) => ['get' => ['responses' => ['200' => ['description' => 'OK', 'content' => $content]]]];
        $post = fn (array $content) => ['post' => [
            'requestBody' => ['content' => $content],
            'responses' => ['200' => ['description' => 'OK']],
        ]];

        $this->setSchema(Schema::fromArray([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Body parsing', 'version' => '1.0'],
            'paths' => [
                '/problem' => $get(['application/problem+xml' => $title]),
                '/text' => $get(['text/plain' => $text]),
                '/patch' => $post(['application/merge-patch+json' => $title]),
                '/xml' => $post(['application/xml' => $title]),
                '/note' => $post(['text/plain' => $text]),
            ],
        ]));
    }

    protected function get(string $path, string $body, ?string $contentType): void
    {
        $response = Response::getInstance(200)->withBody(new MemoryStream($body));
        if ($contentType !== null) {
            $response = $response->withHeader('Content-Type', $contentType);
        }
        $request = new MockRequester($response);
        $request->withMethod('GET')->withPath($path)->expectStatus(200);

        $this->sendRequest($request);
    }

    protected function post(string $path, string $body, string $contentType): void
    {
        $request = new MockRequester(Response::getInstance(200)->withBody(new MemoryStream('')));
        $request
            ->withMethod('POST')
            ->withPath($path)
            ->withRequestHeader(['Content-Type' => $contentType])
            ->withRequestBody($body)
            ->expectStatus(200);

        $this->sendRequest($request);
    }

    public function testAnXmlSuffixResponseIsParsedAsXml(): void
    {
        $this->get('/problem', '<problem><title>Not found</title></problem>', 'application/problem+xml');

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('Required property');
        $this->get('/problem', '<problem><detail>x</detail></problem>', 'application/problem+xml');
    }

    public function testMalformedXmlIsRejectedWithoutAWarning(): void
    {
        // phpunit.xml fails a test on a PHP warning: simplexml_load_string() must not raise one
        $this->expectException(NotMatchedException::class);
        $this->get('/problem', '<problem><title>', 'application/problem+xml');
    }

    public function testAPlainTextResponseWithoutAContentTypeIsValidated(): void
    {
        // It used to become null, failing with "Body is expected"
        $this->get('/text', 'Hello', null);

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('longer than maxLength 5');
        $this->get('/text', 'Hello!', null);
    }

    public function testAJsonSuffixRequestIsParsedAsJson(): void
    {
        // It used to raise InvalidRequestException: Cannot handle Content Type
        $this->post('/patch', '{"title": "Rex"}', 'application/merge-patch+json');

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('Required property');
        $this->post('/patch', '{"name": "Rex"}', 'application/merge-patch+json');
    }

    public function testAnXmlRequestIsParsedAsXml(): void
    {
        $this->post('/xml', '<pet><title>Rex</title></pet>', 'application/xml; charset=utf-8');

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('Required property');
        $this->post('/xml', '<pet><name>Rex</name></pet>', 'application/xml');
    }

    public function testAnyOtherRequestTypeIsMatchedAsAString(): void
    {
        $this->post('/note', 'Hello', 'text/plain');

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('longer than maxLength 5');
        $this->post('/note', 'Hello!', 'text/plain');
    }
}
