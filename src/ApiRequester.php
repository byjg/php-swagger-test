<?php

namespace ByJG\ApiTools;

use ByJG\ApiTools\Response\ResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use ByJG\ApiTools\Response\PsrResponse;

/**
 * Request handler based on a Guzzle client.
 */
class ApiRequester extends AbstractRequester
{
    /** @var ClientInterface */
    private $guzzleHttpClient;

    public function __construct()
    {
        parent::__construct();
        $this->guzzleHttpClient = new Client(['headers' => ['User-Agent' => 'Swagger Test']]);
    }

    /**
     * @param string $path
     * @param array  $headers
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    protected function handleRequest($path, $headers)
    {
        // Make the request
        $request = new Request(
            $this->method,
            $this->schema->getServerUrl() . $path,
            $headers,
            json_encode($this->requestBody)
        );

        try {
            return new PsrResponse($this->guzzleHttpClient->send($request, ['allow_redirects' => false]));
        } catch (BadResponseException $ex) {
            return new PsrResponse($ex->getResponse());
        }
    }
}
