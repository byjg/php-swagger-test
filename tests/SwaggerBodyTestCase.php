<?php

namespace Test;

use ByJG\ApiTools\Swagger\SwaggerSchema;
use PHPUnit\Framework\TestCase;

class SwaggerBodyTestCase extends TestCase
{

    /**
     * @param bool $allowNullValues
     * @return SwaggerSchema
     */
    protected static function swaggerSchema($allowNullValues = false)
    {
        return \ByJG\ApiTools\Base\Schema::getInstance(
            self::getSwaggerJsonContent(),
            $allowNullValues
        );
    }

    /**
     * @param bool $allowNullValues
     * @return SwaggerSchema
     */
    protected static function swaggerSchema2($allowNullValues = false)
    {
        return \ByJG\ApiTools\Base\Schema::getInstance(
            self::getSwaggerJsonContent_No2(),
            $allowNullValues
        );
    }

    /**
     * @return string
     */
    protected static function getSwaggerJsonContent()
    {
        return file_get_contents(__DIR__ . '/example/swagger.json');
    }

    /**
     * @return string
     */
    protected static function getSwaggerJsonContent_No2()
    {
        return file_get_contents(__DIR__ . '/example/swagger2.json');
    }
}
