<?php

namespace Test;

use ByJG\ApiTools\OpenApi\OpenApiSchema;
use PHPUnit\Framework\TestCase;

class OpenApiResponseAllOffTest extends TestCase
{

    /**
     * @var OpenApiSchema
     */
    protected $openapiObject;

    protected function setUp()
    {
        parent::setUp();
        $this->openapiObject = new OpenApiSchema(file_get_contents(__DIR__ . '/example/openapi_allOf.json'));
    }

    public function testAllOf()
    {
        $body = [
            'this' => 'is',
            'the' => 'way',
        ];
        $responseParameters = $this->openapiObject->getResponseParameters('/allOf', 'get', 200);
        $this->assertTrue($responseParameters->match($body));
    }

    public function testAllOfWithMissingRequiredAttribute()
    {
        $body = [
            'this' => 'is',
        ];
        $responseParameters = $this->openapiObject->getResponseParameters('/allOf', 'get', 200);
        $this->expectExceptionMessage("Required property 'the' in 'get 200 /allOf' not found in object");
        $responseParameters->match($body);
    }

    public function testAllOfInvalidOptionalProperty()
    {
        $body = [
            'this' => 'is',
            'the' => 'way',
            'jedi' => ['sith']
        ];
        $responseParameters = $this->openapiObject->getResponseParameters('/allOf', 'get', 200);
        $this->expectExceptionMessage("Value in 'jedi' is not string");
        $this->assertFalse($responseParameters->match($body));
    }

    public function testAllOfInvalid()
    {
        $body = [
            'this' => 'is',
            'the' => 'way',
        ];
        $responseParameters = $this->openapiObject->getResponseParameters('/allOfInvalid', 'get', 200);
        $this->expectExceptionMessage('An entry of allOf must be object in get 200 /allOfInvalid');
        $responseParameters->match($body);
    }
}
