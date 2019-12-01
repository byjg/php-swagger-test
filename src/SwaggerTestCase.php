<?php

namespace ByJG\Swagger;

use ByJG\Swagger\Exception\GenericSwaggerException;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use ByJG\Swagger\Base\Schema;

abstract class SwaggerTestCase extends TestCase
{
    /**
     * @var SwaggerSchema
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
     * @throws Exception\DefinitionNotFoundException
     * @throws Exception\HttpMethodNotFoundException
     * @throws Exception\InvalidDefinitionException
     * @throws Exception\InvalidRequestException
     * @throws Exception\NotMatchedException
     * @throws Exception\PathNotFoundException
     * @throws Exception\RequiredArgumentNotFound
     * @throws Exception\StatusCodeNotMatchedException
     * @throws GenericSwaggerException
     * @throws GuzzleException
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
        $requester = new SwaggerRequester();
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
     * @param SwaggerRequester $request
     * @return mixed
     * @throws Exception\DefinitionNotFoundException
     * @throws Exception\HttpMethodNotFoundException
     * @throws Exception\InvalidDefinitionException
     * @throws Exception\InvalidRequestException
     * @throws Exception\NotMatchedException
     * @throws Exception\PathNotFoundException
     * @throws Exception\RequiredArgumentNotFound
     * @throws Exception\StatusCodeNotMatchedException
     * @throws GenericSwaggerException
     * @throws GuzzleException
     */
    public function assertRequest(SwaggerRequester $request)
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
