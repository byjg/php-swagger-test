<?php

namespace Tests;

use ByJG\ApiTools\AbstractRequester;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\OpenApiValidation;
use ByJG\Util\Uri;
use ByJG\WebRequest\Psr7\Request;
use ByJG\WebRequest\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * send() builds the request URI from the server URL of the specification. The port goes with the
 * host: it is the server URL's when the server URL has a host, and the request's when it has not.
 */
class RequestUriTest extends TestCase
{
    use OpenApiValidation;

    public static function serverUrls(): array
    {
        return [
            'relative server URL keeps the request port' => ['/v2', 'http://localhost:8080/v2/pet'],
            'server URL with a port' => ['http://api.example.com:9000/v2', 'http://api.example.com:9000/v2/pet'],
            'server URL without a port' => ['http://api.example.com/v2', 'http://api.example.com/v2/pet'],
        ];
    }

    #[DataProvider('serverUrls')]
    public function testRequestUri(string $serverUrl, string $expectedUri): void
    {
        $this->setSchema(Schema::fromArray([
            'openapi' => '3.0.0',
            'info' => ['title' => 'Request URI', 'version' => '1.0'],
            'servers' => [['url' => $serverUrl]],
            'paths' => ['/pet' => ['get' => ['responses' => ['200' => ['description' => 'OK']]]]],
        ]));

        $request = new class extends AbstractRequester {
            public ?RequestInterface $sent = null;

            #[\Override]
            protected function handleRequest(RequestInterface $request): ResponseInterface
            {
                $this->sent = $request;
                return Response::getInstance(200);
            }
        };
        $request->withPsr7Request(Request::getInstance(new Uri('http://localhost:8080/pet'))->withMethod('get'));

        $this->sendRequest($request);

        $this->assertEquals($expectedUri, (string)$request->sent?->getUri());
    }
}
