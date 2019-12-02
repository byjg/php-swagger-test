<?php

namespace ByJG\ApiTools;

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

abstract class ApiTestCase extends TestCase
{
    /**
     * @var Schema
     */
    protected $swaggerSchema;

    protected $filePath;

    /**
     * @throws GenericSwaggerException
     */
    protected function setUp()
    {
        if (empty($this->filePath)) {
            throw new GenericSwaggerException('You have to define the property $filePath');
        }

        $this->swaggerSchema = Schema::getInstance(file_get_contents($this->filePath));
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
        $requester = new ApiRequester();
        $body = $requester
            ->withSwaggerSchema($this->swaggerSchema)
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
     * @param ApiRequester $request
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws StatusCodeNotMatchedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function assertRequest(ApiRequester $request)
    {
        // Add own swagger if nothing is passed.
        if (!$request->hasSwaggerSchema()) {
            $request->withSwaggerSchema($this->swaggerSchema);
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
