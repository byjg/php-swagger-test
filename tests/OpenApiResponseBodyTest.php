<?php

namespace Tests;

use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericSwaggerException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;

class OpenApiResponseBodyTest extends OpenApiBodyTestCase
{
    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBody(): void
    {
        $openApiSchema = self::openApiSchema();

        $body = [
            "id" => 10,
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true
        ];

        $responseParameter = $openApiSchema->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));

        // Default
        $body = [
            "id" => 10,
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed'
        ];

        $responseParameter = $openApiSchema->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));

        // Number as string
        $body = [
            "id" => "10",
            "petId" => "50",
            "quantity" => "1",
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true
        ];

        $responseParameter = $openApiSchema->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyWithRefInsteadOfContent(): void
    {
        $openApiSchema = self::openApiSchema5();

        $body = [
            "param_response_1" => "example1",
            "param_response_2" => "example2"
        ];

        $responseParameter = $openApiSchema->getResponseParameters('/v1/test', 'post', 201);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyEnumError(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Value 'notfound' in 'status' not matched in ENUM");
        
        $body = [
            "id" => 10,
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'notfound',
            "complete" => true
        ];

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyWrongNumber(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Expected 'id' to be numeric, but found 'ABC'");
        
        $body = [
            "id" => "ABC",
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true
        ];

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyMoreThanExpected(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("The property(ies) 'more' has not defined in '#/components/schemas/Order'");
        
        $body = [
            "id" => "50",
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true,
            "more" => "value"
        ];

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyLessFields(): void
    {
        $body = [
            "id"       => 10,
            "status"   => 'placed',
            "complete" => true
        ];

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyAllowNullValues(): void
    {
        $allowNullValues = true;
        $body = [
            "id"       => 10,
            "status"   => 'placed',
            "complete" => null
        ];

        $responseParameter = self::openApiSchema($allowNullValues)->getResponseParameters(
            '/v2/store/ordernull',
            'post',
            200
        );
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyNotAllowNullValues(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Value of property 'complete' is null, but should be of type 'boolean'");
        
        $body = [
            "id"       => 10,
            "status"   => 'placed',
            "complete" => null
        ];

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $responseParameter->match($body);
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyEmpty(): void
    {
        $body = null;

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/pet/10', 'get', 400);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyNotEmpty(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Expected empty body for");
        
        $body = ['suppose'=>'not here'];

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/pet/10', 'get', 400);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyComplex(): void
    {
        $body = [
            "id" => 10,
            "category" => [
                "id" => 1,
                "name" => 'Dog'
            ],
            "name" => "Spike",
            "photoUrls" => [
                'url1',
                'url2'
            ],
            "tags" => [
                [
                    'id' => '10',
                    'name' => 'cute'
                ],
                [
                    'name' => 'priceless'
                ]
            ],
            "status" => 'available'
        ];

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/pet/10', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchResponseBodyWhenValueWithNestedPropertiesIsNullAndNullsAreAllowed(): void
    {
        $allowNullValues = true;
        $body = [
            "id" => 10,
            "category" => null,
            "name" => "Spike",
            "photoUrls" => [
                'url1',
                'url2'
            ],
            "tags" => [
                [
                    'id' => '10',
                    'name' => 'cute'
                ],
                [
                    'name' => 'priceless'
                ]
            ],
            "status" => 'available'
        ];

        $responseParameter = self::openApiSchema($allowNullValues)->getResponseParameters('/v2/pet/10', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws GenericSwaggerException
     * @throws DefinitionNotFoundException
     * @throws PathNotFoundException
     * @throws NotMatchedException
     * @throws InvalidRequestException
     * @throws RequiredArgumentNotFound
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     */
    public function testAdditionalPropertiesInObjectInResponseBody(): void
    {
        $body = ['value1' => 1, 'value2' => 2];
        $responseParameter = self::openApiSchema5()->getResponseParameters('/tests/additional_properties', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws GenericSwaggerException
     * @throws DefinitionNotFoundException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     * @throws InvalidRequestException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     */
    public function testAdditionalPropertiesInObjectInResponseBodyDoNotMatch(): void
    {
        $this->expectExceptionMessage("Expected 'value2' to be numeric, but found 'string'");
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $body = ['value1' => 1, 'value2' => 'string'];
        $responseParameter = self::openApiSchema5()->getResponseParameters('/tests/additional_properties', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * Issue #9
     *
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testIssue9(): void
    {
        $body =
            [
                [
                    [
                        "isoCode" => "fr",
                        "label" => "French",
                        "isDefault" => true
                    ],
                    [
                        "isoCode" => "br",
                        "label" => "Brazilian",
                        "isDefault" => false
                    ]
                ],
            ];

        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/languages', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * Issue #9
     *
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testIssue9Error(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageMatches("/I expected an array here.*/");
        $body =
            [
                [
                    "isoCode" => "fr",
                    "label" => "French",
                    "isDefault" => true
                ],
                [
                    "isoCode" => "br",
                    "label" => "Brazilian",
                    "isDefault" => false
                ]
            ];

        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/languages', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * Issue #9
     *
     * @throws InvalidRequestException
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws RequiredArgumentNotFound
     */
    public function testMatchAnyValue(): void
    {
        $body = "string";
        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));

        $body = 1000;
        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));

        $body = [ "test" => "10"];
        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws GenericSwaggerException
     * @throws DefinitionNotFoundException
     * @throws NotMatchedException
     * @throws RequiredArgumentNotFound
     * @throws HttpMethodNotFoundException
     * @throws PathNotFoundException
     * @throws InvalidRequestException
     * @throws InvalidDefinitionException
     */
    public function testMatchAllOf(): void
    {
        $body = ["name" => "Bob", "email" => "bob@example.com"];
        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/allof', 'get', 200);
        $this->assertTrue($responseParameter->match($body));

        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/allofref', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws GenericSwaggerException
     * @throws DefinitionNotFoundException
     * @throws PathNotFoundException
     * @throws NotMatchedException
     * @throws InvalidRequestException
     * @throws RequiredArgumentNotFound
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     */
    public function testResponseDefault(): void
    {
        $body = [];
        $responseParameter = $this->openApiSchema()->getResponseParameters('/v2/user', 'post', 503);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws PathNotFoundException
     * @throws NotMatchedException
     * @throws InvalidRequestException
     * @throws HttpMethodNotFoundException
     */
    public function testResponseWithNoDefault(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\InvalidDefinitionException::class);
        $this->expectExceptionMessage("Could not found status code '503'");
        
        $body = [];
        $responseParameter = $this->openApiSchema()->getResponseParameters('/v2/user/login', 'get', 503);
    }
}
