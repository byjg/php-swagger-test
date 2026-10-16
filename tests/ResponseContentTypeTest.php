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
 * The response is validated against the media type of its Content-Type header, not one
 * guessed from the body: an HTML page, a type range ("text/*") and any type ("*\/*").
 */
class ResponseContentTypeTest extends TestCase
{
    use OpenApiValidation;

    #[Override]
    public function setUp(): void
    {
        $response = fn (array $content) => ['get' => ['responses' => ['200' => ['description' => 'OK', 'content' => $content]]]];

        $this->setSchema(Schema::getInstance([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Content types', 'version' => '1.0'],
            'paths' => [
                '/page' => $response(['text/html' => []]),
                '/text' => $response(['text/*' => ['schema' => ['type' => 'string']]]),
                '/any' => $response(['*/*' => []]),
                '/pet' => $response(['application/xml' => ['schema' => [
                    'type' => 'object',
                    'required' => ['name'],
                    'properties' => ['name' => ['type' => 'string']],
                ]]]),
                '/order' => $response(['application/json' => ['schema' => [
                    'type' => 'object',
                    'required' => ['id'],
                    'properties' => ['id' => ['type' => 'integer']],
                ]]]),
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
        $request
            ->withMethod('GET')
            ->withPath($path)
            ->expectStatus(200);

        $this->sendRequest($request);
    }

    public function testAnHtmlPageDocumentedWithoutASchemaMatches(): void
    {
        // It used to be checked as text/plain, guessed from the string body: "Content type not found"
        $this->get('/page', '<html><body>Hello</body></html>', 'text/html; charset=utf-8');
        $this->addToAssertionCount(1);
    }

    public function testATypeRangeCoversTheContentType(): void
    {
        $this->get('/text', "id,name\n1,Rex", 'text/csv');
        $this->addToAssertionCount(1);
    }

    public function testAnyTypeCoversTheContentType(): void
    {
        $this->get('/any', "\x89PNG\r\n", 'image/png');
        $this->addToAssertionCount(1);
    }

    public function testAContentTypeTheOperationDoesNotDocumentFails(): void
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Content type 'application/json' not found");

        $this->get('/page', '{"id": 1}', 'application/json');
    }

    public function testAnXmlBodyIsValidatedAgainstTheSchemaOfItsType(): void
    {
        $this->get('/pet', '<pet><name>Rex</name></pet>', 'application/xml');

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('Required property');
        $this->get('/pet', '<pet><age>3</age></pet>', 'application/xml');
    }

    public function testWithoutAContentTypeHeaderTheTypeIsGuessedFromTheBody(): void
    {
        $this->get('/order', '{"id": 1}', null);

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('Required property');
        $this->get('/order', '{"name": "Rex"}', null);
    }
}
