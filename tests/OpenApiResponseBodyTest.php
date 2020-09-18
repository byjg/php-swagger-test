<?php

namespace Test;

class OpenApiResponseBodyTest extends OpenApiBodyTestCase
{
    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     */
    public function testMatchResponseBody()
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyWithRefInsteadOfContent()
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
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Value 'notfound' in 'status' not matched in ENUM
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
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

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Expected 'id' to be numeric, but found 'ABC'
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
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

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage The property(ies) 'more' has not defined in '#/components/schemas/Order'
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
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

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyLessFields()
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
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyAllowNullValues()
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
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Value of property 'complete' is null, but should be of type 'boolean'
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyNotAllowNullValues()
    {
        $body = [
            "id"       => 10,
            "status"   => 'placed',
            "complete" => null
        ];

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $responseParameter->match($body);
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyEmpty()
    {
        $body = null;

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/pet/10', 'get', 400);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @expectedException \ByJG\ApiTools\Exception\NotMatchedException
     * @expectedExceptionMessage Expected empty body for
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     */
    public function testMatchResponseBodyNotEmpty()
    {
        $body = ['suppose'=>'not here'];

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/pet/10', 'get', 400);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
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

        $responseParameter = self::openApiSchema()->getResponseParameters('/v2/pet/10', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
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

        $responseParameter = self::openApiSchema($allowNullValues)->getResponseParameters('/v2/pet/10', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * Issue #9
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
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

        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/languages', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * Issue #9
     * @expectedException \ByJG\ApiTools\Exception\InvalidRequestException
     * @expectedExceptionMessageRegExp "I expected an array here.*"
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
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

        $responseParameter = $this->openApiSchema2()->getResponseParameters('/v2/languages', 'get', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * Issue #9
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\GenericSwaggerException
     * @throws \ByJG\ApiTools\Exception\HttpMethodNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @throws \ByJG\ApiTools\Exception\InvalidRequestException
     * @throws \ByJG\ApiTools\Exception\NotMatchedException
     * @throws \ByJG\ApiTools\Exception\PathNotFoundException
     */
    public function testMatchAnyValue()
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

    public function testResponseDefault()
    {
        $body = [];
        $responseParameter = $this->openApiSchema()->getResponseParameters('/v2/user', 'post', 503);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @expectedException \ByJG\ApiTools\Exception\InvalidDefinitionException
     * @expectedExceptionMessage Could not found status code '503'
     */
    public function testResponseWithNoDefault()
    {
        $body = [];
        $responseParameter = $this->openApiSchema()->getResponseParameters('/v2/user/login', 'get', 503);
    }
}
