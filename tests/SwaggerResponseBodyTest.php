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

class SwaggerResponseBodyTest extends SwaggerBodyTestCase
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
    public function testMatchResponseBody()
    {
        $schema = self::swaggerSchema();

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
    }

    /**
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
    public function testMatchResponseBodyEnumError()
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
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
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
    public function testMatchResponseBodyWrongNumber()
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
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/store/order', 'post', 200);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
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
    public function testMatchResponseBodyMoreThanExpected()
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("The property(ies) 'more' has not defined in '#/definitions/Order'");
        
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
    public function testMatchResponseBodyLessFields()
    {
        $body = [
            "id"       => 10,
            "status"   => 'placed',
            "complete" => true
        ];
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/store/order', 'post', 200);
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
    }

    /**
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
    public function testMatchResponseBodyNotAllowNullValues()
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Value of property 'complete' is null, but should be of type 'boolean'");
        
        $body = [
            "id"       => 10,
            "status"   => 'placed',
            "complete" => null
        ];
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/store/order', 'post', 200);
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
    public function testMatchResponseBodyEmpty()
    {
        $body = null;
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/pet/10', 'get', 400);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
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
    public function testMatchResponseBodyNotEmpty()
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage("Expected empty body for");
        
        $body = ['suppose'=>'not here'];
        $responseParameter = self::swaggerSchema()->getResponseParameters('/v2/pet/10', 'get', 400);
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
    public function testNotMatchResponseBodyWhenValueWithPatterns()
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage(<<<'EOL'
Value '18' in 'age' is not string.  ->
{
    "description": "successful operation",
    "schema": {
        "$ref": "#\/definitions\/DateShelter"
    }
}
EOL
);
        $allowNullValues = false;
        $body = [
            "date" => "2010-05-11",
            "age" => 18
        ];
        $responseParameter = self::swaggerSchema($allowNullValues)->getResponseParameters('/v2/pet/dateShelter', 'get', 200);
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
    public function testMatchResponseBodyWhenValueWithPatterns()
    {
        $allowNullValues = false;
        $body = [
            "date" => "2010-05-11",
            "age" => '18'
        ];
        $responseParameter = self::swaggerSchema($allowNullValues)->getResponseParameters('/v2/pet/dateShelter', 'get', 200);
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
    public function testMatchResponseBodyWhenValueWithStringPatternError()
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage(<<<'EOL'
Value '20100-05-11' in 'date' not matched in pattern.  ->
{
    "description": "successful operation",
    "schema": {
        "$ref": "#\/definitions\/DateShelter"
    }
}
EOL
);

        $allowNullValues = false;
        $body = [
            "date" => "20100-05-11",
            "age" => 18,
        ];
        $responseParameter = self::swaggerSchema($allowNullValues)->getResponseParameters('/v2/pet/dateShelter', 'get', 200);
        $this->assertFalse($responseParameter->match($body));
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
    public function testMatchResponseBodyWhenValueWithNumberPatternError()
    {
        $this->expectException(\ByJG\ApiTools\Exception\NotMatchedException::class);
        $this->expectExceptionMessage(<<<'EOL'
Value '9999' in 'age' not matched in pattern.  ->
{
    "description": "successful operation",
    "schema": {
        "$ref": "#\/definitions\/DateShelter"
    }
}
EOL
        );

        $allowNullValues = false;
        $body = [
            "date" => "2010-05-11",
            "age" => 9999,
        ];
        $responseParameter = self::swaggerSchema($allowNullValues)->getResponseParameters('/v2/pet/dateShelter', 'get', 200);
        $this->assertFalse($responseParameter->match($body));
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
    public function testIssue9Error()
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
        $responseParameter = $this->swaggerSchema2()->getResponseParameters('/v2/languages', 'get', 200);
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
    public function testMatchAnyValue()
    {
        $body = "string";
        $responseParameter = $this->swaggerSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));

        $body = 1000;
        $responseParameter = $this->swaggerSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
        $this->assertTrue($responseParameter->match($body));

        $body = [ "test" => "10"];
        $responseParameter = $this->swaggerSchema2()->getResponseParameters('/v2/anyvalue', 'get', 200);
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
    public function testResponseDefault()
    {
        $body = [];
        $responseParameter = $this->swaggerSchema()->getResponseParameters('/v2/user', 'post', 503);
        $this->assertTrue($responseParameter->match($body));
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws PathNotFoundException
     * @throws NotMatchedException
     * @throws InvalidRequestException
     * @throws HttpMethodNotFoundException
     */
    public function testResponseWithNoDefault()
    {
        $this->expectException(\ByJG\ApiTools\Exception\InvalidDefinitionException::class);
        $this->expectExceptionMessage("Could not found status code '503'");
        
        $body = [];
        $responseParameter = $this->swaggerSchema()->getResponseParameters('/v2/user/login', 'get', 503);
    }
}
