<?php

namespace ByJG\Swagger;

use PHPUnit\Framework\TestCase;

abstract class SwaggerTestCase extends TestCase
{
    /**
     * @var \ByJG\Swagger\SwaggerSchema
     */
    protected $swaggerSchema;

    protected $filePath;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        if (empty($this->filePath)) {
            throw new \Exception('You have to define the property $filePath');
        }

        $this->swaggerSchema = new SwaggerSchema(file_get_contents($this->filePath));
    }

    /**
     * @param string $method The HTTP Method: GET, PUT, DELETE, POST, etc
     * @param string $path The REST path call
     * @param int $statusExpected
     * @param array|null $query
     * @param array|null $requestBody
     * @param array $requestHeader
     * @return mixed
     * @deprecated Use assertRequest instead
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     * @throws \ByJG\Swagger\Exception\RequiredArgumentNotFound
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
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
        // reach this;
        $this->assertTrue(true);

        return $body;
    }

    /**
     * @param \ByJG\Swagger\SwaggerRequester $request
     * @return mixed
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     * @throws \ByJG\Swagger\Exception\RequiredArgumentNotFound
     * @throws \Exception
     */
    public function assertRequest(SwaggerRequester $request)
    {
        // Request based on the Swagger Request definitios
        $body = $request
            ->withSwaggerSchema($this->swaggerSchema)
            ->send();

        // Note:
        // This code is only reached if the send is successful and
        // all matches are satisfied. Otherwise an error is throwed before
        // reach this;
        $this->assertTrue(true);

        return $body;
    }
}
