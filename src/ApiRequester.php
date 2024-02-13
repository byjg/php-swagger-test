<?php

namespace ByJG\ApiTools;

use ByJG\Util\Exception\MessageException;
use ByJG\Util\Exception\NetworkException;
use ByJG\Util\Exception\RequestException;
use ByJG\Util\HttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Request handler based on ByJG HttpClient (WebRequest) .
 */
class ApiRequester extends AbstractRequester
{
    /** @var HttpClient */
    private HttpClient $httpClient;

    /**
     * ApiRequester constructor.
     * @throws RequestException
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
     * @return ResponseInterface
     * @throws NetworkException
     * @throws RequestException
     */
    protected function handleRequest(RequestInterface $request): ResponseInterface
    {
        $request = $request->withHeader("User-Agent", "ByJG Swagger Test");
        return $this->httpClient->sendRequest($request);
    }
}
