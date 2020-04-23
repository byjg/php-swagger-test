<?php

namespace ByJG\ApiTools;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\StatusCodeNotMatchedException;
use ByJG\Util\Psr7\MessageException;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Uri;
use MintWare\Streams\MemoryStream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Abstract baseclass for request handlers.
 *
 * The baseclass provides processing and verification of request and response.
 * It only delegates the actual message exchange to the derived class. For the
 * messages, it uses the PSR-7 implementation from Guzzle.
 *
 * This is an implementation of the Template Method Patttern
 * (https://en.wikipedia.org/wiki/Template_method_pattern).
 */
abstract class AbstractRequester
{
    protected $method = 'get';
    protected $path = '/';
    protected $requestHeader = [];
    protected $query = [];
    protected $requestBody = null;
    /**
     * @var Schema
     */
    protected $schema = null;

    protected $statusExpected = 200;
    protected $assertHeader = [];
    protected $assertBody = null;

    /**
     * abstract function to be implemented by derived classes
     *
     * This function must be implemented by derived classes. It should process
     * the given request and return an according response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    abstract protected function handleRequest(RequestInterface $request);

    /**
     * @param Schema $schema
     * @return $this
     */
    public function withSchema($schema)
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSchema()
    {
        return !empty($this->schema);
    }

    /**
     * @param string $method
     * @return $this
     */
    public function withMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function withPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param array $requestHeader
     * @return $this
     */
    public function withRequestHeader($requestHeader)
    {
        if (is_null($requestHeader)) {
            $this->requestHeader = [];
            return $this;
        }

        $this->requestHeader = array_merge($this->requestHeader, $requestHeader);

        return $this;
    }

    /**
     * @param array $query
     * @return $this
     */
    public function withQuery($query = null)
    {
        if (is_null($query)) {
            $this->query = [];
            return $this;
        }

        $this->query = array_merge($this->query, $query);

        return $this;
    }

    /**
     * @param null $requestBody
     * @return $this
     */
    public function withRequestBody($requestBody)
    {
        $this->requestBody = $requestBody;

        return $this;
    }

    public function assertResponseCode($code)
    {
        $this->statusExpected = $code;

        return $this;
    }

    public function assertHeaderContains($header, $contains)
    {
        $this->assertHeader[$header] = $contains;

        return $this;
    }

    public function assertBodyContains($contains)
    {
        $this->assertBody = $contains;

        return $this;
    }

    /**
     * @return mixed
     * @throws Exception\DefinitionNotFoundException
     * @throws Exception\GenericSwaggerException
     * @throws Exception\HttpMethodNotFoundException
     * @throws Exception\InvalidDefinitionException
     * @throws Exception\PathNotFoundException
     * @throws NotMatchedException
     * @throws StatusCodeNotMatchedException
     * @throws MessageException
     */
    public function send()
    {
        // Preparing Parameters
        $paramInQuery = null;
        if (!empty($this->query)) {
            $paramInQuery = '?' . http_build_query($this->query);
        }

        // Preparing Header
        if (empty($this->requestHeader)) {
            $this->requestHeader = [];
        }
        $header = array_merge(
            [
                'Accept' => 'application/json'
            ],
            $this->requestHeader
        );

        // Defining Variables
        $serverUrl = $this->schema->getServerUrl();
        $basePath = $this->schema->getBasePath();
        $pathName = $this->path;

        // Check if the body is the expected before request
        $bodyRequestDef = $this->schema->getRequestParameters("$basePath$pathName", $this->method);
        $bodyRequestDef->match($this->requestBody);

        // Make the request
        $request = Request::getInstance(Uri::getInstanceFromString($serverUrl . $pathName . $paramInQuery))
            ->withMethod($this->method);

        if (!empty($this->requestBody)) {
            $request->withBody(new MemoryStream(json_encode($this->requestBody)));
        }

        foreach ($header as $key => $value) {
            $request->withHeader($key, $value);
        }

        $response = $this->handleRequest($request);
        $responseHeader = $response->getHeaders();
        $responseBodyStr = (string) $response->getBody();
        $responseBody = json_decode($responseBodyStr, true);
        $statusReturned = $response->getStatusCode();

        // Assert results
        if ($this->statusExpected != $statusReturned) {
            throw new StatusCodeNotMatchedException(
                "Status code not matched: Expected {$this->statusExpected}, got {$statusReturned}",
                $responseBody
            );
        }

        $bodyResponseDef = $this->schema->getResponseParameters(
            "$basePath$pathName",
            $this->method,
            $this->statusExpected
        );
        $bodyResponseDef->match($responseBody);

        foreach ($this->assertHeader as $key => $value) {
            if (!isset($responseHeader[$key]) || strpos($responseHeader[$key][0], $value) === false) {
                throw new NotMatchedException(
                    "Does not exists header '$key' with value '$value'",
                    $responseHeader
                );
            }
        }

        if (!empty($this->assertBody) && strpos($responseBodyStr, $this->assertBody) === false) {
            throw new NotMatchedException("Body does not contain '{$this->assertBody}'");
        }

        return $responseBody;
    }
}
