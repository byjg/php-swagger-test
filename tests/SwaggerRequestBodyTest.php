<?php
/**
 * User: jg
 * Date: 22/05/17
 * Time: 09:31
 */

namespace Test;

use ByJG\Swagger\SwaggerSchema;
use PHPUnit\Framework\TestCase;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class SwaggerRequestBodyTest extends TestCase
{
    /**
     * @var SwaggerSchema
     */
    protected $object;

    public function setUp()
    {
        $this->object = new SwaggerSchema(file_get_contents(__DIR__ . '/example/swagger.json'));
    }

    public function tearDown()
    {
        $this->object = null;
    }

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
        $requestParameter = $this->object->getRequestParameters('/v2/store/order', 'post');
        $this->assertTrue($requestParameter->match($body));
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
        $requestParameter = $this->object->getRequestParameters('/v2/pet', 'post');
        $this->assertTrue($requestParameter->match($body));
    }
}
