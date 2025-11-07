<?php

namespace ByJG\ApiTools;

use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericApiException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;
use ByJG\ApiTools\Exception\StatusCodeNotMatchedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Base test case for OpenAPI/Swagger validation.
 *
 * This class extends PHPUnit's TestCase and uses the OpenApiValidation trait
 * to provide OpenAPI/Swagger validation capabilities.
 *
 * If you need to extend a different base class, you can use the OpenApiValidation
 * trait directly instead of extending this class.
 *
 * @see OpenApiValidation
 */
abstract class ApiTestCase extends TestCase
{
    use OpenApiValidation;

    /**
     * Legacy method for backward compatibility.
     *
     * @param string $method The HTTP Method: GET, PUT, DELETE, POST, etc
     * @param string $path The REST path call
     * @param int $statusExpected
     * @param string|array|null $query
     * @param array|string|null $requestBody
     * @param array $requestHeader
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     * @throws StatusCodeNotMatchedException
     * @deprecated Since version 6.0, use sendRequest() with ApiRequester fluent interface instead. Will be removed in version 7.0
     */
    protected function makeRequest(
        string $method,
        string $path,
        int $statusExpected = 200,
        string|array|null $query = null,
        array|string|null $requestBody = null,
        array $requestHeader = []
    ): ResponseInterface {
        $this->checkSchema();
        return $this->getRequester()
            ->withSchema($this->schema)
            ->withMethod($method)
            ->withPath($path)
            ->withQuery($query)
            ->withRequestBody($requestBody)
            ->withRequestHeader($requestHeader)
            ->expectStatus($statusExpected)
            ->send();
    }
}
