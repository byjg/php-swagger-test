<?php

namespace ByJG\ApiTools\Base;

use ByJG\ApiTools\AbstractRequester;
use ByJG\ApiTools\ApiRequester;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\ApiTools\Exception\StatusCodeNotMatchedException;
use ByJG\ApiTools\Exception\GenericSwaggerException;
use GuzzleHttp\GuzzleException;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    /**
     * @var Schema
     */
    protected $schema;

    /**
     * configure the schema to use for requests
     *
     * When set, all requests without an own schema use this one instead.
     *
     * @param Schema|null $schema
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param string $method The HTTP Method: GET, PUT, DELETE, POST, etc
     * @param string $path The REST path call
     * @param int $statusExpected
     * @param array|null $query
     * @param array|null $requestBody
     * @param array $requestHeader
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws StatusCodeNotMatchedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @deprecated Use assertRequest instead
     */
    protected function makeRequest(
        $method,
        $path,
        $statusExpected = 200,
        $query = null,
        $requestBody = null,
        $requestHeader = []
    ) {
        if (!$this->schema) {
            throw new GenericSwaggerException('You have to configure a schema for the testcase');
        }
        $requester = new ApiRequester();
        $body = $requester
            ->withSchema($this->schema)
            ->withMethod($method)
            ->withPath($path)
            ->withQuery($query)
            ->withRequestBody($requestBody)
            ->withRequestHeader($requestHeader)
            ->assertResponseCode($statusExpected)
            ->send();

        // Note:
        // This code is only reached if the send is successful and
        // all matches are satisfied. Otherwise an error is throwed before
        // reach this
        $this->assertTrue(true);

        return $body;
    }

    /**
     * @param AbstractRequester $request
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws StatusCodeNotMatchedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function assertRequest(AbstractRequester $request)
    {
        // Add own schema if nothing is passed.
        if (!$request->hasSchema()) {
            if (!$this->schema) {
                throw new GenericSwaggerException('You have to configure a schema for either the request or the testcase');
            }
            $request->withSchema($this->schema);
        }

        // Request based on the Swagger Request definitios
        $body = $request->send();

        // Note:
        // This code is only reached if the send is successful and
        // all matches are satisfied. Otherwise an error is throwed before
        // reach this
        $this->assertTrue(true);

        return $body;
    }
}
