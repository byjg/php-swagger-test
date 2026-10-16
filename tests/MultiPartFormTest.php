<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\MockRequester;
use ByJG\ApiTools\OpenApiValidation;
use ByJG\Util\Uri;
use ByJG\WebRequest\Helper\RequestMultiPart;
use ByJG\WebRequest\MultiPartItem;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Parsing a multipart/form-data request body into its fields, before it is matched against the
 * specification.
 */
class MultiPartFormTest extends TestCase
{
    use OpenApiValidation;

    protected function parse(string $contentType, string $body): ?array
    {
        $requester = new class (Response::getInstance(200)) extends MockRequester {
            public function parse(string $contentType, string $body): ?array
            {
                return $this->parseMultiPartForm($contentType, $body);
            }
        };

        return $requester->parse($contentType, $body);
    }

    /**
     * A body as a browser or curl sends it: CRLF line endings and no ";" after the field name.
     */
    protected function browserBody(string $boundary): string
    {
        return "--$boundary\r\n"
            . "Content-Disposition: form-data; name=\"title\"\r\n\r\n"
            . "Hello\r\n"
            . "--$boundary\r\n"
            . "Content-Disposition: form-data; name=\"notes\"\r\n\r\n"
            . "line 1\r\nline 2\r\n"
            . "--$boundary\r\n"
            . "Content-Disposition: form-data; name=\"photo\"; filename=\"smile.png\"\r\n"
            . "Content-Type: image/png\r\n\r\n"
            . "\x89PNG\r\n\x1a\n\r\n"
            . "--$boundary--\r\n";
    }

    public function testParsesTheFieldsABrowserSends(): void
    {
        $this->assertSame(
            ['title' => 'Hello', 'notes' => "line 1\r\nline 2", 'photo' => "\x89PNG\r\n\x1a\n"],
            $this->parse('multipart/form-data; boundary=XyZ', $this->browserBody('XyZ'))
        );
    }

    public function testAcceptsAQuotedBoundary(): void
    {
        $this->assertSame(
            'Hello',
            $this->parse('multipart/form-data; boundary="XyZ"; charset=utf-8', $this->browserBody('XyZ'))['title'] ?? null
        );
    }

    public function testABoundaryWithRegexCharactersIsMatchedLiterally(): void
    {
        $this->assertSame(
            'Hello',
            $this->parse('multipart/form-data; boundary=a+b.c(d)', $this->browserBody('a+b.c(d)'))['title'] ?? null
        );
    }

    public function testParsesTheFormatOfByjgWebRequest(): void
    {
        // LF line endings and a ";" after each name
        $request = RequestMultiPart::build(new Uri('/upload'), 'post', [
            new MultiPartItem('note', 'some note'),
            new MultiPartItem('upfile', "\x89PNG", 'smile', 'image/png'),
        ], 'boundary123');

        $this->assertSame(
            ['note' => 'some note', 'upfile' => "\x89PNG"],
            $this->parse($request->getHeaderLine('Content-Type'), (string)$request->getBody())
        );
    }

    public function testAPlainFieldIsValidatedAgainstTheSpecification(): void
    {
        $this->setSchema(Schema::getInstance([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Upload', 'version' => '1.0'],
            'paths' => ['/upload' => ['post' => [
                'requestBody' => ['content' => ['multipart/form-data' => ['schema' => [
                    'type' => 'object',
                    'required' => ['title', 'photo'],
                    'properties' => [
                        'title' => ['type' => 'string', 'maxLength' => 5],
                        'notes' => ['type' => 'string'],
                        'photo' => ['type' => 'string', 'format' => 'binary'],
                    ],
                ]]]],
                'responses' => ['200' => ['description' => 'OK']],
            ]]],
        ]));

        $send = function (string $title): void {
            $request = new MockRequester(Response::getInstance(200)->withBody(new MemoryStream('')));
            $request
                ->withMethod('POST')
                ->withPath('/upload')
                ->withRequestHeader(['Content-Type' => 'multipart/form-data; boundary=XyZ'])
                ->withRequestBody(str_replace('Hello', $title, $this->browserBody('XyZ')))
                ->expectStatus(200);
            $this->sendRequest($request);
        };

        // "Hello" is exactly maxLength: the trailing CRLF is not part of the value
        $send('Hello');

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('longer than maxLength 5');
        $send('Hello!');
    }
}
