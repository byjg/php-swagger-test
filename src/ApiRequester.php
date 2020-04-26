<?php

namespace ByJG\ApiTools;

use ByJG\Util\CurlException;
use ByJG\Util\HttpClient;
use ByJG\Util\Psr7\MessageException;
use ByJG\Util\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Request handler based on ByJG HttpClient (WebRequest) .
 */
class ApiRequester extends AbstractRequester
{
    /** @var HttpClient */
    private $httpClient;

    /**
     * ApiRequester constructor.
     * @throws MessageException
     */
    public function __construct()
    {
        $this->httpClient = HttpClient::getInstance()
            ->withNoFollowRedirect();

        parent::__construct();
    }

    /**
     * @param RequestInterface $request
     * @return Response|ResponseInterface
     * @throws CurlException
     * @throws MessageException
     */
    protected function handleRequest(RequestInterface $request)
    {
        $request->withHeader("User-Agent", "ByJG Swagger Test");
        return $this->httpClient->sendRequest($request);
    }
}
