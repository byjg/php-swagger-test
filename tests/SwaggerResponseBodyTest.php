<?php

namespace Test;

use ByJG\Swagger\SwaggerSchema;

class SwaggerResponseBodyTest extends SwaggerBodyTestCase
{
    /**
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBody()
    {
        $schema = self::swaggerSchema();
        $openApiSchema = self::openApiSchema();

        $body = [
            "id" => 10,
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true
        ];
        $responseParameter = $schema->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
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
        $responseParameter = $schema->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
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
        $responseParameter = $schema->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = $openApiSchema->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\NotMatchedException
     * @expectedExceptionMessage Value 'notfound' in 'status' not matched in ENUM
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyEnumError()
    {
        $body = [
            "id" => 10,
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'notfound',
            "complete" => true
        ];
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\NotMatchedException
     * @expectedExceptionMessage Expected 'id' to be numeric, but found 'ABC'
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyWrongNumber()
    {
        $body = [
            "id" => "ABC",
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true
        ];
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\NotMatchedException
     * @expectedExceptionMessage The property(ies) 'more' has not defined in '#/definitions/Order'
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyMoreThanExpected()
    {
        $body = [
            "id" => "50",
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true,
            "more" => "value"
        ];
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyLessFields()
    {
        $body = [
            "id"       => 10,
            "status"   => 'placed',
            "complete" => true
        ];
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyAllowNullValues()
    {
        $allowNullValues = true;
        $body = [
            "id"       => 10,
            "status"   => 'placed',
            "complete" => null
        ];
        $responseParameter = self::swaggerSchema($allowNullValues)->getResponseParameters(
            '/v2/store/order',
            'post',
            200
        );
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = self::openApiSchema($allowNullValues)->getResponseParameters(
            '/v2/store/order',
            'post',
            200
        );
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\NotMatchedException
     * @expectedExceptionMessage Value of property 'complete' is null, but should be of type 'boolean'
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyNotAllowNullValues()
    {
        $body = [
            "id"       => 10,
            "status"   => 'placed',
            "complete" => null
        ];
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $responseParameter->match($body);
        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $responseParameter->match($body);
    }

    /**
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyEmpty()
    {
        $body = null;
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/pet/10', 'get', 400);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/pet/10', 'get', 400);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\NotMatchedException
     * @expectedExceptionMessage Expected empty body for
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyNotEmpty()
    {
        $body = ['suppose'=>'not here'];
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/pet/10', 'get', 400);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/pet/10', 'get', 400);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyComplex()
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
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/pet/10', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/pet/10', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyWhenValueWithNestedPropertiesIsNullAndNullsAreAllowed()
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
        $responseParameter = self::swaggerSchema($allowNullValues)->getResponseParameters('/v2/pet/10', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = self::openApiSchema($allowNullValues)->getResponseParameters('/v2/pet/10', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * Issue #9
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testIssue9()
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
        $responseParameter = $this->swaggerSchema2()->getResponseParameters('/v2/languages', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/languages', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * Issue #9
     * @expectedException \ByJG\Swagger\Exception\InvalidRequestException
     * @expectedExceptionMessageRegExp "I expected an array here.*"
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testIssue9Error()
    {
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
        $responseParameter = $this->swaggerSchema2()->getResponseParameters('/v2/languages', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/languages', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * Issue #9
     *
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\GenericSwaggerException
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\InvalidRequestException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testMatchAnyValue()
    {
        $body = "string";
        $responseParameter = $this->swaggerSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));

        $body = 1000;
        $responseParameter = $this->swaggerSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));

        $body = [ "test" => "10"];
        $responseParameter = $this->swaggerSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }
}
