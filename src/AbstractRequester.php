<?php

namespace ByJG\ApiTools;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericSwaggerException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;
use ByJG\ApiTools\Exception\StatusCodeNotMatchedException;
use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Request;
use ByJG\XmlUtil\XmlDocument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Abstract baseclass for request handlers.
 *
 * The baseclass provides processing and verification of request and response.
 * It only delegates the actual message exchange to the derived class. For the
 * messages, it uses the PHP PSR-7 implementation.
 *
 * This is an implementation of the Template Method Patttern
 * (https://en.wikipedia.org/wiki/Template_method_pattern).
 */
abstract class AbstractRequester
{
    /**
     * @var Schema|null
     */
    protected ?Schema $schema = null;

    protected int $statusExpected = 200;
    protected array $assertHeader = [];
    protected array $assertBody = [];

    /**
     * @var RequestInterface
     */
    protected RequestInterface $psr7Request;

    /**
     * AbstractRequester constructor.
     * @throws MessageException
     * @throws RequestException
     */
    public function __construct()
    {
        $this->withPsr7Request(Request::getInstance(new Uri("/"))->withMethod("get"));
    }

    /**
     * abstract function to be implemented by derived classes
     *
     * This function must be implemented by derived classes. It should process
     * the given request and return an according response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    abstract protected function handleRequest(RequestInterface $request): ResponseInterface;

    /**
     * @param Schema $schema
     * @return $this
     */
    public function withSchema(Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSchema(): bool
    {
        return !empty($this->schema);
    }

    /**
     * @param string $method
     * @return $this
     */
    public function withMethod(string $method): self
    {
        $this->psr7Request = $this->psr7Request->withMethod($method);

        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function withPath(string $path): self
    {
        $uri = $this->psr7Request->getUri()->withPath($path);
        $this->psr7Request = $this->psr7Request->withUri($uri);

        return $this;
    }

    /**
     * @param string|array $requestHeader
     * @return $this
     */
    public function withRequestHeader(string|array $requestHeader): self
    {
        foreach ((array)$requestHeader as $name => $value) {
            $this->psr7Request = $this->psr7Request->withHeader($name, $value);
        }

        return $this;
    }

    /**
     * @param array|null $query
     * @return $this
     */
    public function withQuery(array $query = null): self
    {
        $uri = $this->psr7Request->getUri();

        if (is_null($query)) {
            $uri = $uri->withQuery("");
            $this->psr7Request = $this->psr7Request->withUri($uri);
            return $this;
        }

        $currentQuery = [];
        parse_str($uri->getQuery(), $currentQuery);

        $uri = $uri->withQuery(http_build_query(array_merge($currentQuery, $query)));
        $this->psr7Request = $this->psr7Request->withUri($uri);

        return $this;
    }

    /**
     * @param mixed $requestBody
     * @return $this
     */
    public function withRequestBody(array|string $requestBody): self
    {
        $contentType = $this->psr7Request->getHeaderLine("Content-Type");
        if (is_array($requestBody) && (empty($contentType) || str_contains($contentType, "application/json"))) {
            $requestBody = json_encode($requestBody);
        }
        $this->psr7Request = $this->psr7Request->withBody(new MemoryStream($requestBody));

        return $this;
    }

    /**
     * @param RequestInterface $requestInterface
     * @return $this
     */
    public function withPsr7Request(RequestInterface $requestInterface): self
    {
        $this->psr7Request = $requestInterface->withHeader("Accept", "application/json");

        return $this;
    }

    public function assertResponseCode(int $code): self
    {
        $this->statusExpected = $code;

        return $this;
    }

    public function assertHeaderContains(string $header, string $contains): self
    {
        $this->assertHeader[$header] = $contains;

        return $this;
    }

    public function assertBodyContains(string $contains): self
    {
        $this->assertBody[] = $contains;

        return $this;
    }

    /**
     * @return ResponseInterface
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     */
    public function send(): ResponseInterface
    {
        // Process URI based on the OpenAPI schema
        $uriSchema = new Uri($this->schema->getServerUrl());

        if (empty($uriSchema->getScheme())) {
            $uriSchema = $uriSchema->withScheme($this->psr7Request->getUri()->getScheme());
        }

        if (empty($uriSchema->getHost())) {
            $uriSchema = $uriSchema->withHost($this->psr7Request->getUri()->getHost());
        }

        $uri = $this->psr7Request->getUri()
            ->withScheme($uriSchema->getScheme())
            ->withHost($uriSchema->getHost())
            ->withPort($uriSchema->getPort())
            ->withPath($uriSchema->getPath() . $this->psr7Request->getUri()->getPath());

        if (!preg_match("~^{$this->schema->getBasePath()}~",  $uri->getPath())) {
            $uri = $uri->withPath($this->schema->getBasePath() . $uri->getPath());
        }

        $this->psr7Request = $this->psr7Request->withUri($uri);

        // Prepare Body to Match Against Specification
        $rawBody = $this->psr7Request->getBody()->getContents();
        $isXmlBody = false;
        $requestBody = null;
        $contentType = $this->psr7Request->getHeaderLine("content-type");
        if (!empty($rawBody)) {
            if (str_contains($contentType, 'application/xml') || str_contains($contentType, 'text/xml')) {
                $isXmlBody = new XmlDocument($rawBody);
            } elseif (empty($contentType) || str_contains($contentType, "application/json")) {
                $requestBody = json_decode($rawBody, true);
            } elseif (str_contains($contentType, "multipart/")) {
                $requestBody = $this->parseMultiPartForm($contentType, $rawBody);
            } else {
                throw new InvalidRequestException("Cannot handle Content Type '$contentType'");
            }

        }

        // Check if the body is the expected before request
        if ($isXmlBody === false) {
            $bodyRequestDef = $this->schema->getRequestParameters($this->psr7Request->getUri()->getPath(), $this->psr7Request->getMethod());
            $bodyRequestDef->match($requestBody);
        }

        // Handle Request
        $response = $this->handleRequest($this->psr7Request);
        $responseHeader = $response->getHeaders();
        $responseBodyStr = (string) $response->getBody();
        $responseBody = json_decode($responseBodyStr, true);
        $statusReturned = $response->getStatusCode();

        // Assert results
        if ($this->statusExpected != $statusReturned) {
            throw new StatusCodeNotMatchedException(
                "Status code not matched: Expected $this->statusExpected, got $statusReturned",
                $responseBody
            );
        }

        $bodyResponseDef = $this->schema->getResponseParameters(
            $this->psr7Request->getUri()->getPath(),
            $this->psr7Request->getMethod(),
            $this->statusExpected
        );
        $bodyResponseDef->match($responseBody);

        foreach ($this->assertHeader as $key => $value) {
            if (!isset($responseHeader[$key]) || !str_contains($responseHeader[$key][0], $value)) {
                throw new NotMatchedException(
                    "Does not exists header '$key' with value '$value'",
                    $responseHeader
                );
            }
        }

        if (!empty($responseBodyStr)) {
            foreach ($this->assertBody as $item) {
                if (!str_contains($responseBodyStr, $item)) {
                    throw new NotMatchedException("Body does not contain '$item'");
                }
            }
        }

        return $response;
    }

    protected function parseMultiPartForm(?string $contentType, string $body): array|null
    {
        $matchRequest = [];

        if (empty($contentType) || !str_contains($contentType, "multipart/")) {
            return null;
        }

        $matches = [];

        preg_match('/boundary=(.*)$/', $contentType, $matches);
        $boundary = $matches[1];

        // split content by boundary and get rid of last -- element
        $blocks = preg_split("/-+$boundary/", $body);
        array_pop($blocks);

        // loop data blocks
        foreach ($blocks as $block) {
            if (empty($block))
                continue;

            if (str_contains($block, 'application/octet-stream')) {
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            } else {
                preg_match('/\bname=\"([^\"]*)\"\s*;.*?[\n|\r]+([^\n\r].*)?[\r|\n]$/s', $block, $matches);
            }
            $matchRequest[$matches[1]] = $matches[2];
        }

        return $matchRequest;
    }
}
