<?php

namespace ByJG\Swagger;

use ByJG\Swagger\Exception\NotMatchedException;
use ByJG\Swagger\Exception\StatusCodeNotMatchedException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;

class SwaggerRequester
{
    protected $method = 'get';
    protected $path = '/';
    protected $requestHeader = [];
    protected $query = [];
    protected $requestBody = null;
    /**
     * @var \ByJG\Swagger\SwaggerSchema
     */
    protected $swaggerSchema = null;

    protected $statusExpected = 200;
    protected $assertHeader = [];

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $guzzleHttpClient;

    public function __construct()
    {
        $this->guzzleHttpClient = new Client(['headers' => ['User-Agent' => 'Swagger Test']]);
    }

    /**
     * @param SwaggerSchema $schema
     * @return $this
     */
    public function withSwaggerSchema($schema)
    {
        $this->swaggerSchema = $schema;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSwaggerSchema()
    {
        return !empty($this->swaggerSchema);
    }

    /**
     * @param string $method
     * @return SwaggerRequester
     */
    public function withMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param string $path
     * @return SwaggerRequester
     */
    public function withPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param array $requestHeader
     * @return SwaggerRequester
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
     * @return SwaggerRequester
     */
    public function withQuery($query)
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
     * @return SwaggerRequester
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

    /**
     * @return mixed
     * @throws Exception\DefinitionNotFoundException
     * @throws Exception\GenericSwaggerException
     * @throws Exception\HttpMethodNotFoundException
     * @throws Exception\InvalidDefinitionException
     * @throws Exception\InvalidRequestException
     * @throws Exception\PathNotFoundException
     * @throws Exception\RequiredArgumentNotFound
     * @throws NotMatchedException
     * @throws StatusCodeNotMatchedException
     * @throws \GuzzleHttp\Exception\GuzzleException
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
        $httpSchema = $this->swaggerSchema->getHttpSchema();
        $host = $this->swaggerSchema->getHost();
        $basePath = $this->swaggerSchema->getBasePath();
        $pathName = $this->path;

        // Check if the body is the expected before request
        $bodyRequestDef = $this->swaggerSchema->getRequestParameters("$basePath$pathName", $this->method);
        $bodyRequestDef->match($this->requestBody);

        // Make the request
        $request = new Request(
            $this->method,
            "$httpSchema://$host$basePath$pathName$paramInQuery",
            $header,
            json_encode($this->requestBody)
        );

        $statusReturned = null;
        try {
            $response = $this->guzzleHttpClient->send($request, ['allow_redirects' => false]);
            $responseHeader = $response->getHeaders();
            $responseBody = json_decode((string) $response->getBody(), true);
            $statusReturned = $response->getStatusCode();
        } catch (BadResponseException $ex) {
            $responseHeader = $ex->getResponse()->getHeaders();
            $responseBody = json_decode((string) $ex->getResponse()->getBody(), true);
            $statusReturned = $ex->getResponse()->getStatusCode();
        }

        // Assert results
        if ($this->statusExpected != $statusReturned) {
            throw new StatusCodeNotMatchedException(
                "Status code not matched $statusReturned",
                $responseBody
            );
        }

        $bodyResponseDef = $this->swaggerSchema->getResponseParameters(
            "$basePath$pathName",
            $this->method,
            $this->statusExpected
        );
        $bodyResponseDef->match($responseBody);

        if (count($this->assertHeader) > 0) {
            foreach ($this->assertHeader as $key => $value) {
                if (!isset($responseHeader[$key]) || strpos($responseHeader[$key][0], $value) === false) {
                    throw new NotMatchedException(
                        "Does not exists header '$key' with value '$value'",
                        $responseHeader
                    );
                }
            }
        }

        return $responseBody;
    }
}
