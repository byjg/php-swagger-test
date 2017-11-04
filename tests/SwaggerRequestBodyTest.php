<?php
/**
 * User: jg
 * Date: 22/05/17
 * Time: 09:31
 */

namespace Test;

class SwaggerRequestBodyTest extends SwaggerBodyTestCase
{
    public function testMatchRequestBody()
    {
        $body = [
            "id" => "10",
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true
        ];
        $requestParameter = self::swaggerSchema()->getRequestParameters('/v2/store/order', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\RequiredArgumentNotFound
     * @expectedExceptionMessage The body is required
     */
    public function testMatchRequiredRequestBodyEmpty()
    {
        $requestParameter = self::swaggerSchema()->getRequestParameters('/v2/store/order', 'post');
        $this->assertTrue($requestParameter->match(null));
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\InvalidDefinitionException
     * @expectedExceptionMessage Body is passed but there is no request body definition
     */
    public function testMatchInexistantBodyDefinition()
    {
        $requestParameter = self::swaggerSchema()->getRequestParameters('/v2/pet/1', 'get');
        $body = [
            "id" => "10",
            "petId" => 50,
            "quantity" => 1,
            "shipDate" => '2010-10-20',
            "status" => 'placed',
            "complete" => true
        ];
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\NotMatchedException
     * @expectedExceptionMessage Path expected an integer value
     */
    public function testMatchDataType()
    {
        self::swaggerSchema()->getRequestParameters('/v2/pet/STRING', 'get');
        $this->assertTrue(true);
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\NotMatchedException
     * @expectedExceptionMessage Required property
     */
    public function testMatchRequestBodyRequired1()
    {
        $body = [
            "id" => "10",
            "status" => "pending",
        ];
        $requestParameter = self::swaggerSchema()->getRequestParameters('/v2/pet', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * It is not OK when allowNullValues is false (as by default) { name: null }
     * https://stackoverflow.com/questions/45575493/what-does-required-in-openapi-really-mean
     *
     * @expectedException \ByJG\Swagger\Exception\NotMatchedException
     * @expectedExceptionMessage Value of property 'name' is null, but should be of type 'string'
     */
    public function testMatchRequestBodyRequiredNullsNotAllowed()
    {
        $body = [
            "id" => "10",
            "status" => "pending",
            "name" => null,
            "photoUrls" => ["http://example.com/1", "http://example.com/2"]
        ];
        $requestParameter = self::swaggerSchema()->getRequestParameters('/v2/pet', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    public function testMatchRequestBodyRequiredNullsAllowed()
    {
        $allowNullValues = true;
        $body = [
            "id" => "10",
            "status" => "pending",
            "name" => null,
            "photoUrls" => ["http://example.com/1", "http://example.com/2"]
        ];
        $requestParameter = self::swaggerSchema($allowNullValues)->getRequestParameters('/v2/pet', 'post');
        $this->assertTrue($requestParameter->match($body));
    }

    /**
     * It is OK: { name: ""}
     * https://stackoverflow.com/questions/45575493/what-does-required-in-openapi-really-mean
     */
    public function testMatchRequestBodyRequired3()
    {
        $body = [
            "id" => "10",
            "status" => "pending",
            "name" => "",
            "photoUrls" => ["http://example.com/1", "http://example.com/2"]
        ];
        $requestParameter = self::swaggerSchema()->getRequestParameters('/v2/pet', 'post');
        $this->assertTrue($requestParameter->match($body));
    }
}
