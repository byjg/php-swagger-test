<?php

namespace ByJG\ApiTools;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericApiException;
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
     * @var array<callable> PHPUnit assertions to execute after response is received
     */
    protected array $phpunitAssertions = [];

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
    public function withQuery(?array $query = null): self
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

    /**
     * Expect a specific HTTP status code.
     *
     * @param int $expectedStatus Expected HTTP status code
     * @return $this
     */
    public function expectStatus(int $expectedStatus): self
    {
        $this->statusExpected = $expectedStatus;

        // Add PHPUnit assertion to be executed after response is received
        $this->phpunitAssertions[] = function ($testCase, $response) use ($expectedStatus) {
            $testCase->assertEquals(
                $expectedStatus,
                $response->getStatusCode(),
                "Expected HTTP status code $expectedStatus"
            );
        };

        return $this;
    }

    /**
     * Expect a specific header to contain a value.
     *
     * @param string $header Header name
     * @param string $contains Expected value to be contained in the header
     * @return $this
     */
    public function expectHeaderContains(string $header, string $contains): self
    {
        $this->assertHeader[$header] = $contains;

        return $this;
    }

    /**
     * Expect the response body to contain a string.
     *
     * @param string $contains Expected string to be contained in the body
     * @return $this
     */
    public function expectBodyContains(string $contains): self
    {
        $this->assertBody[] = $contains;

        return $this;
    }

    /**
     * Expect the JSON response to contain specific key-value pairs.
     *
     * This performs a subset match - the response can contain additional fields.
     *
     * @param array $expected Expected key-value pairs (supports nested arrays)
     * @return $this
     */
    public function expectJsonContains(array $expected): self
    {
        $this->phpunitAssertions[] = function ($testCase, $response) use ($expected) {
            $body = json_decode((string)$response->getBody(), true);

            if ($body === null) {
                $testCase->fail('Response body is not valid JSON');
            }

            foreach ($expected as $key => $value) {
                $testCase->assertArrayHasKey(
                    $key,
                    $body,
                    "Expected JSON response to contain key '$key'"
                );

                if (is_array($value)) {
                    $testCase->assertEquals(
                        $value,
                        $body[$key],
                        "Expected JSON key '$key' to match nested array"
                    );
                } else {
                    $testCase->assertEquals(
                        $value,
                        $body[$key],
                        "Expected JSON key '$key' to equal " . json_encode($value)
                    );
                }
            }
        };

        return $this;
    }

    /**
     * Expect a specific value at a JSONPath expression.
     *
     * Supports simple dot notation like 'user.name' or 'items.0.id'.
     *
     * @param string $path JSONPath expression (dot notation)
     * @param mixed $expectedValue Expected value at that path
     * @return $this
     */
    public function expectJsonPath(string $path, mixed $expectedValue): self
    {
        $this->phpunitAssertions[] = function ($testCase, $response) use ($path, $expectedValue) {
            $body = json_decode((string)$response->getBody(), true);

            if ($body === null) {
                $testCase->fail('Response body is not valid JSON');
            }

            // Simple JSONPath implementation using dot notation
            $keys = explode('.', $path);
            $current = $body;

            foreach ($keys as $key) {
                if (is_array($current) && array_key_exists($key, $current)) {
                    $current = $current[$key];
                } else {
                    $testCase->fail("JSONPath '$path' not found in response (failed at key '$key')");
                    return;
                }
            }

            $testCase->assertEquals(
                $expectedValue,
                $current,
                "Expected value at JSONPath '$path' to equal " . json_encode($expectedValue)
            );
        };

        return $this;
    }

    /**
     * Get the expected status code.
     *
     * @return int
     */
    public function getExpectedStatus(): int
    {
        return $this->statusExpected;
    }

    /**
     * Get the registered PHPUnit assertions.
     *
     * @return array<callable>
     */
    public function getPhpunitAssertions(): array
    {
        return $this->phpunitAssertions;
    }

    /**
     * @return ResponseInterface
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
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
        $requestBody = $this->psr7Request->getBody()->getContents();
        if (!empty($requestBody)) {
            $contentType = $this->psr7Request->getHeaderLine("content-type");
            if (empty($contentType) || str_contains($contentType, "application/json")) {
                $requestBody = json_decode($requestBody, true);
            } elseif (str_contains($contentType, "multipart/")) {
                $requestBody = $this->parseMultiPartForm($contentType, $requestBody);
            } else {
                throw new InvalidRequestException("Cannot handle Content Type '$contentType'");
            }
        }

        // Check if the body is the expected before request
        $bodyRequestDef = $this->schema->getRequestParameters($this->psr7Request->getUri()->getPath(), $this->psr7Request->getMethod());
        $bodyRequestDef->match($requestBody);

        // Handle Request
        $response = $this->handleRequest($this->psr7Request);
        $responseHeader = $response->getHeaders();
        $contentType = "";
        foreach ($responseHeader as $headerName => $headerValue) {
            if (is_numeric($headerName)) {
                $header = explode(":", $headerValue[0]);
                $headerName = trim($header[0]);
                $headerValue = trim($header[1] ?? "");
            }
            $headerName = strtolower($headerName);
            if ($headerName == 'content-type') {
                $contentType = $headerValue;
                break;
            }
        }
        $responseBodyStr = (string) $response->getBody();
        if ($contentType === 'application/json') {
            $responseBodyParsed = json_decode($responseBodyStr, true);
        } elseif ($contentType === 'text/xml') {
            $responseBodyParsed = simplexml_load_string($responseBodyStr);
        } else {
            $responseBodyParsed = $responseBodyStr;
        }
        $statusReturned = $response->getStatusCode();

        // Assert results
        if ($this->statusExpected != $statusReturned) {
            throw new StatusCodeNotMatchedException(
                "Status code not matched: Expected $this->statusExpected, got $statusReturned",
                $responseBodyStr
            );
        }

        $bodyResponseDef = $this->schema->getResponseParameters(
            $this->psr7Request->getUri()->getPath(),
            $this->psr7Request->getMethod(),
            $this->statusExpected
        );
        $bodyResponseDef->match($responseBodyParsed);

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
