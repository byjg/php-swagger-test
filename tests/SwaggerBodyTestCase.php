<?php

namespace Tests;

use ByJG\ApiTools\Swagger\SwaggerSchema;
use PHPUnit\Framework\TestCase;

/**
 * baseclass for further tests
 *
 * @see SwaggerRequestBodyTest
 * @see SwaggerResponseBodyTest
 */
class SwaggerBodyTestCase extends TestCase
{

    /**
     * @param bool $allowNullValues
     * @return SwaggerSchema
     */
    protected static function swaggerSchema(bool $allowNullValues = false): SwaggerSchema
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
    protected static function swaggerSchema2(bool $allowNullValues = false): SwaggerSchema
    {
        return \ByJG\ApiTools\Base\Schema::getInstance(
            self::getSwaggerJsonContent_No2(),
            $allowNullValues
        );
    }

    /**
     * @return false|string
     */
    protected static function getSwaggerJsonContent(): string|false
    {
        return file_get_contents(__DIR__ . '/example/swagger.json');
    }

    /**
     * @return false|string
     */
    protected static function getSwaggerJsonContent_No2(): string|false
    {
        return file_get_contents(__DIR__ . '/example/swagger2.json');
    }
}
