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
use Psr\Http\Message\ResponseInterface;

/**
 * Trait OpenApiValidation
 *
 * Provides OpenAPI/Swagger validation functionality that can be used in any test class.
 * This trait allows you to validate API requests/responses against OpenAPI specifications
 * without being forced to extend ApiTestCase.
 *
 * Example usage:
 * <code>
 * class MyTest extends MyCustomBaseTest
 * {
 *     use OpenApiValidation;
 *
 *     public function setUp(): void
 *     {
 *         parent::setUp();
 *         $this->setSchema(Schema::fromFile('openapi.json'));
 *     }
 *
 *     public function testApi()
 *     {
 *         $request = new ApiRequester()
 *             ->withMethod('GET')
 *             ->withPath('/pet/1');
 *         $this->sendRequest($request);
 *     }
 * }
 * </code>
 *
 * @package ByJG\ApiTools
 */
trait OpenApiValidation
{
    /**
     * @var Schema|null
     */
    protected ?Schema $schema = null;

    /**
     * @var AbstractRequester|null
     */
    protected ?AbstractRequester $requester = null;

    /**
     * Configure the schema to use for requests.
     *
     * When set, all requests without an own schema use this one instead.
     *
     * @param Schema|null $schema
     */
    public function setSchema(?Schema $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * Set a custom requester instance.
     *
     * @param AbstractRequester $requester
     */
    public function setRequester(AbstractRequester $requester): void
    {
        $this->requester = $requester;
    }

    /**
     * Get the requester instance, creating a default ApiRequester if needed.
     *
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
     * Send a request and validate it against the OpenAPI/Swagger schema.
     *
     * This method sends the HTTP request configured in the ApiRequester and validates:
     * - Request body matches the schema definition
     * - Response status code matches expected value
     * - Response body matches the schema definition
     *
     * Validation happens implicitly - if validation fails, an exception is thrown.
     *
     * @param AbstractRequester $request Configured request to send and validate
     * @return ResponseInterface The PSR-7 response object
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
    public function sendRequest(AbstractRequester $request): ResponseInterface
    {
        // Add own schema if nothing is passed.
        if (!$request->hasSchema()) {
            $this->checkSchema();
            $request = $request->withSchema($this->schema);
        }

        // Send request and validate against the OpenAPI/Swagger specification
        // Validation happens inside send() - exceptions are thrown on failure
        $response = $request->send();

        // Add implicit PHPUnit assertion for status code (prevents "risky test" warnings)
        // This assertion checks the expected status (default 200) matches actual status
        if (method_exists($this, 'assertEquals')) {
            $this->assertEquals(
                $request->getExpectedStatus(),
                $response->getStatusCode(),
                "Expected HTTP status code {$request->getExpectedStatus()}"
            );
        }

        // Execute any additional PHPUnit assertions from convenience methods
        // (e.g., assertStatus(), assertJsonContains(), assertJsonPath())
        if (method_exists($this, 'assertEquals')) {
            foreach ($request->getPhpunitAssertions() as $assertion) {
                $assertion($this, $response);
            }
        }

        return $response;
    }

    /**
     * Send a request and validate it against the OpenAPI/Swagger schema.
     *
     * @param AbstractRequester $request
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
     * @deprecated Since version 6.0, use sendRequest() instead. Will be removed in version 7.0
     */
    public function assertRequest(AbstractRequester $request): ResponseInterface
    {
        return $this->sendRequest($request);
    }

    /**
     * Check that a schema has been configured.
     *
     * @throws GenericApiException
     * @psalm-assert !null $this->schema
     */
    protected function checkSchema(): void
    {
        if (is_null($this->schema)) {
            throw new GenericApiException('You have to configure a schema for either the request or the testcase');
        }
    }
}
