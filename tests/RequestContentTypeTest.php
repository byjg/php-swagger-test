<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\MockRequester;
use ByJG\ApiTools\OpenApiValidation;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The request body is validated against the media type of its Content-Type header, the same
 * way on OpenAPI 3.0 and 3.1. It used to be the first documented type on 3.0 and JSON first on
 * 3.1, whatever the request was sent as.
 */
class RequestContentTypeTest extends TestCase
{
    use OpenApiValidation;

    private const BOUNDARY = '----FormBoundary7MA4YWxkTrZu0gW';

    public static function openApiVersions(): array
    {
        return ['OpenAPI 3.0' => ['3.0.0'], 'OpenAPI 3.1' => ['3.1.0']];
    }

    protected function useSpec(string $version): void
    {
        $object = fn (string $required) => [
            'type' => 'object',
            'required' => [$required],
            'properties' => [$required => ['type' => 'string']],
        ];
        $operation = fn (array $content) => ['post' => [
            'requestBody' => ['content' => $content],
            'responses' => ['200' => ['description' => 'OK']],
        ]];

        $this->setSchema(Schema::fromArray([
            'openapi' => $version,
            'info' => ['title' => 'Request content types', 'version' => '1.0'],
            'paths' => [
                '/json-first' => $operation([
                    'application/json' => ['schema' => $object('name')],
                    'multipart/form-data' => ['schema' => $object('file')],
                ]),
                '/multipart-first' => $operation([
                    'multipart/form-data' => ['schema' => $object('file')],
                    'application/json' => ['schema' => $object('name')],
                ]),
                '/json-only' => $operation(['application/json' => ['schema' => $object('name')]]),
                '/range' => $operation(['application/*' => ['schema' => $object('name')]]),
                '/any' => $operation(['*/*' => []]),
            ],
        ]));
    }

    protected function post(string $path, string|array $body, ?string $contentType): void
    {
        $request = new MockRequester(Response::getInstance(200)->withBody(new MemoryStream('')));
        $request->withMethod('POST')->withPath($path)->expectStatus(200);
        if ($contentType !== null) {
            $request->withRequestHeader(['Content-Type' => $contentType]);
        }
        $request->withRequestBody($body);

        $this->sendRequest($request);
    }

    /**
     * A multipart body with one file part named $field.
     */
    protected function multipart(string $field): string
    {
        return "--" . self::BOUNDARY . "\r\n"
            . "Content-Disposition: form-data; name=\"$field\"; filename=\"a.txt\"\r\n"
            . "Content-Type: application/octet-stream\r\n\r\n"
            . "value\r\n"
            . "--" . self::BOUNDARY . "--\r\n";
    }

    #[DataProvider('openApiVersions')]
    public function testAMultipartRequestIsValidatedAgainstTheMultipartSchema(string $version): void
    {
        $this->useSpec($version);
        $multipart = 'multipart/form-data; boundary=' . self::BOUNDARY;

        // JSON is documented first, but the request is multipart: its schema requires "file"
        $this->post('/json-first', $this->multipart('file'), $multipart);

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('Required property');
        $this->post('/json-first', $this->multipart('name'), $multipart);
    }

    #[DataProvider('openApiVersions')]
    public function testWithoutAContentTypeTheBodyIsValidatedAsJson(string $version): void
    {
        $this->useSpec($version);

        // withRequestBody() sends an array as JSON, with no header: JSON is preferred to the first type
        $this->post('/multipart-first', ['name' => 'Rex'], null);

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('Required property');
        $this->post('/multipart-first', ['file' => 'x'], null);
    }

    #[DataProvider('openApiVersions')]
    public function testAContentTypeTheOperationDoesNotDocumentFails(string $version): void
    {
        $this->useSpec($version);

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Content type 'application/x-www-form-urlencoded' not found");

        // It used to be checked against the JSON schema, and pass
        $this->post('/json-only', 'name=Rex', 'application/x-www-form-urlencoded');
    }

    #[DataProvider('openApiVersions')]
    public function testATypeRangeCoversTheContentType(string $version): void
    {
        $this->useSpec($version);
        $this->post('/range', 'name=Rex', 'application/x-www-form-urlencoded');

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('Required property');
        $this->post('/range', 'other=Rex', 'application/x-www-form-urlencoded');
    }

    #[DataProvider('openApiVersions')]
    public function testAnyTypeWithoutASchemaAcceptsAnyBody(string $version): void
    {
        $this->useSpec($version);
        $this->post('/any', 'whatever=1', 'application/x-www-form-urlencoded');
        $this->addToAssertionCount(1);
    }
}
