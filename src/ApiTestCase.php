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
use ByJG\Util\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

abstract class ApiTestCase extends TestCase
{
    /**
     * @var Schema
     */
    protected Schema $schema;

    /**
     * @var AbstractRequester|null
     */
    protected ?AbstractRequester $requester = null;

    /**
     * configure the schema to use for requests
     *
     * When set, all requests without an own schema use this one instead.
     *
     * @param Schema|null $schema
     */
    public function setSchema(?Schema $schema): void
    {
        $this->schema = $schema;
    }

    public function setRequester(AbstractRequester $requester): void
    {
        $this->requester = $requester;
    }

    /**
     * @return AbstractRequester|null
     */
    protected function getRequester(): AbstractRequester|null
    {
        if (is_null($this->requester)) {
            $this->requester = new ApiRequester();
        }
        return $this->requester;
    }

    /**
     * @param string $method The HTTP Method: GET, PUT, DELETE, POST, etc
     * @param string $path The REST path call
     * @param int $statusExpected
     * @param string|array|null $query
     * @param array|string|null $requestBody
     * @param array $requestHeader
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     * @deprecated Use assertRequest instead
     */
    protected function makeRequest(
        string $method,
        string $path,
        int $statusExpected = 200,
        string|array|null $query = null,
        array|string $requestBody = null,
        array $requestHeader = []
    ): ResponseInterface {
        $this->checkSchema();
        $body = $this->requester
            ->withSchema($this->schema)
            ->withMethod($method)
            ->withPath($path)
            ->withQuery($query)
            ->withRequestBody($requestBody)
            ->withRequestHeader($requestHeader)
            ->assertResponseCode($statusExpected)
            ->send();

        // Note:
        // This code is only reached if to send is successful and
        // all matches are satisfied. Otherwise, an error is throwed before
        // reach this
        $this->assertTrue(true);

        return $body;
    }

    /**
     * @param AbstractRequester $request
     * @return Response
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
    public function assertRequest(AbstractRequester $request): ResponseInterface
    {
        // Add own schema if nothing is passed.
        if (!$request->hasSchema()) {
            $this->checkSchema();
            $request = $request->withSchema($this->schema);
        }

        // Request based on the Swagger Request definitios
        $body = $request->send();

        // Note:
        // This code is only reached if to send is successful and
        // all matches are satisfied. Otherwise, an error is throwed before
        // reach this
        $this->assertTrue(true);

        return $body;
    }

    /**
     * @throws GenericSwaggerException
     */
    protected function checkSchema(): void
    {
        if (!$this->schema) {
            throw new GenericSwaggerException('You have to configure a schema for either the request or the testcase');
        }
    }
}
