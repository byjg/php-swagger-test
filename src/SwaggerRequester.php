<?php

namespace ByJG\Swagger;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Request handler based on a Guzzle client.
 */
class SwaggerRequester extends AbstractRequester
{
    /**
     * @var ClientInterface
     */
    protected $guzzleHttpClient;

    public function __construct()
    {
        $this->guzzleHttpClient = new Client(['headers' => ['User-Agent' => 'Swagger Test']]);
    }

    protected function handleRequest(RequestInterface $request)
    {
        return $this->guzzleHttpClient->send($request, ['allow_redirects' => false]);
    }
}
