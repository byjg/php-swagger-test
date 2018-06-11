<?php

namespace Test;

use ByJG\Swagger\Exception\BaseException;
use ByJG\Swagger\Exception\GenericSwaggerException;
use PHPUnit\Framework\TestCase;

class BaseExceptionTest extends TestCase
{

    public function testGetBody()
    {
        $exception = new GenericSwaggerException("message", ["a" => 10]);

        $this->assertEquals("message ->\n{\n    \"a\": 10\n}\n", $exception->getMessage());
        $this->assertEquals(["a" => 10], $exception->getBody());
    }
}
