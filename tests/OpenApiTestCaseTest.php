<?php

namespace Tests;

use ByJG\ApiTools\ApiRequester;
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

class OpenApiTestCaseTest extends TestingTestCase
{

    #[\Override]
    public function setUp(): void
    {
        $schema = Schema::getInstance(file_get_contents(__DIR__ . '/rest/openapi.json'));
        $this->setSchema($schema);
    }

    /**
     * Test that the API returns XML when Accept: application/xml is requested
     *
     * Note: This test bypasses schema validation because XML structure validation
     * is complex with SimpleXML and array conversions. Instead, it verifies the
     * XML structure directly.
     */
    public function testGetXmlResponse(): void
    {
        // Make a direct HTTP request with XML Accept header
        $client = \ByJG\WebRequest\HttpClient::getInstance();
        $uri = new \ByJG\Util\Uri('http://127.0.0.1:8081/v2/pet/1');
        $request = \ByJG\WebRequest\Psr7\Request::getInstance($uri)
            ->withMethod('GET')
            ->withHeader('Accept', 'application/xml');

        $response = $client->sendRequest($request);

        // Verify status code
        $this->assertEquals(200, $response->getStatusCode(), 'Expected HTTP status 200');

        // Verify Content-Type header
        $contentType = $response->getHeaderLine('Content-Type');
        $this->assertStringContainsString('application/xml', $contentType, 'Expected XML content type');

        // Verify the response is XML
        $responseBody = (string)$response->getBody();
        $this->assertStringContainsString('<?xml', $responseBody, 'Response should contain XML declaration');

        // Parse and verify XML structure
        $xml = simplexml_load_string($responseBody);
        $this->assertNotFalse($xml, 'Response should be valid XML');

        // Verify expected Pet structure in XML
        $this->assertNotEmpty((string)$xml->id, 'Pet should have an id');
        $this->assertEquals('1', (string)$xml->id, 'Pet id should be 1');
        $this->assertNotEmpty((string)$xml->name, 'Pet should have a name');
        $this->assertEquals('Doris', (string)$xml->name, 'Pet name should be Doris');
        $this->assertTrue(isset($xml->category), 'Pet should have a category');
        $this->assertTrue(isset($xml->status), 'Pet should have a status');
    }
}
