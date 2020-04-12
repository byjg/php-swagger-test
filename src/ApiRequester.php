<?php

namespace ByJG\ApiTools;

use ByJG\Util\HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Request handler based on ByJG HttpClient (WebRequest) .
 */
class ApiRequester extends AbstractRequester
{
    /** @var HttpClient */
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::getInstance()
            ->withNoFollowRedirect();
    }

    protected function handleRequest(RequestInterface $request)
    {
        $request->withHeader("User-Agent", "ByJG Swagger Test");
        return $this->httpClient->sendRequest($request);
    }
}
