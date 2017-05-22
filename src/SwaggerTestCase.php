<?php
/**
 * User: jg
 * Date: 22/05/17
 * Time: 15:32
 */

namespace ByJG\Swagger;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

abstract class SwaggerTestCase extends TestCase
{
    /**
     * @var \ByJG\Swagger\SwaggerSchema
     */
    protected $swaggerSchema;

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $guzzleHttpClient;

    protected $filePath;

    protected function setUp()
    {
        if (empty($this->filePath)) {
            throw new \Exception('You have to define the property $filePath');
        }

        $this->swaggerSchema = new SwaggerSchema(file_get_contents($this->filePath));

        $this->guzzleHttpClient = new Client(['headers' => ['User-Agent' => 'Swagger Test']]);
    }

    protected function getCustomRequest()
    {
        return [];
    }

    /**
     * @param string $method The HTTP Method: GET, PUT, DELETE, POST, etc
     * @param string $path The REST path call
     * @param int $statusExpected
     * @param array|null $query
     * @param array|null $requestBody
     * @return mixed
     */
    protected function makeRequest($method, $path, $statusExpected = 200, $query = null, $requestBody = null)
    {
        $paramInQuery = null;
        if (!empty($query)) {
            $paramInQuery = '?' . http_build_query($query);
        }

        $header = array_merge([
                'Accept' => 'application/json'
            ],
            $this->getCustomRequest()
        );

        $httpSchema = $this->swaggerSchema->getHttpSchema();
        $host = $this->swaggerSchema->getHost();
        $basePath = $this->swaggerSchema->getBasePath();

        $request = new Request(
            $method,
            "$httpSchema://$host$basePath$path$paramInQuery",
            $header,
            json_encode($requestBody)
        );

        $statusReturned = null;
        try {
            $response = $this->guzzleHttpClient->send($request);
            $responseBody = json_decode((string) $response->getBody(), true);
            $statusReturned = $response->getStatusCode();
        } catch (BadResponseException $ex) {
            $responseBody = json_decode((string) $ex->getResponse()->getBody(), true);
            $statusReturned = $ex->getResponse()->getStatusCode();
        }

        $this->assertEquals($statusExpected, $statusReturned);

        $method = strtolower($method);

        $bodyRequestDef = $this->swaggerSchema->getRequestParameters("$basePath$path", $method);
        $bodyResponseDef = $this->swaggerSchema->getResponseParameters("$basePath$path", $method, $statusExpected);

        if (!empty($requestBody)) {
            $bodyRequestDef->match($requestBody);
        }
        $bodyResponseDef->match($responseBody);

        return $responseBody;
    }
}
