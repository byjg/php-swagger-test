<?php


namespace ByJG\ApiTools;

use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\MockClient;
use ByJG\WebRequest\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockRequester extends AbstractRequester
{
    /** @var MockClient */
    private MockClient $httpClient;

    /**
     * MockAbstractRequest constructor.
     * @param Response $expectedResponse
     * @throws RequestException
     * @throws MessageException
     */
    public function __construct(Response $expectedResponse)
    {
        $this->httpClient = new MockClient($expectedResponse);
        parent::__construct();
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws RequestException
     */
    #[\Override]
    protected function handleRequest(RequestInterface $request): ResponseInterface
    {
        $request = $request->withHeader("User-Agent", "ByJG Swagger Test");
        return $this->httpClient->sendRequest($request);
    }
}