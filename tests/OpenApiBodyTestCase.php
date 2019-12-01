<?php

namespace Test;

use ByJG\Swagger\SwaggerSchema;
use PHPUnit\Framework\TestCase;

class OpenApiBodyTestCase extends TestCase
{
    /**
     * @param bool $allowNullValues
     * @return SwaggerSchema
     */
    protected static function openApiSchema($allowNullValues = false)
    {
        return \ByJG\Swagger\Base\Schema::getInstance(
            self::getOpenApiJsonContent(),
            $allowNullValues
        );
    }

    /**
     * @param bool $allowNullValues
     * @return SwaggerSchema
     */
    protected static function openApiSchema2($allowNullValues = false)
    {
        return \ByJG\Swagger\Base\Schema::getInstance(
            self::getOpenApiJsonContent_No2(),
            $allowNullValues
        );
    }

    /**
     * @param bool $allowNullValues
     * @return SwaggerSchema
     */
    protected static function openApiSchema3($allowNullValues = false)
    {
        return \ByJG\Swagger\Base\Schema::getInstance(
            self::getOpenApiJsonContent_No3(),
            $allowNullValues
        );
    }

    /**
     * @return string
     */
    protected static function getOpenApiJsonContent()
    {
        return file_get_contents(__DIR__ . '/example/openapi.json');
    }

    /**
     * @return string
     */
    protected static function getOpenApiJsonContent_No2()
    {
        return file_get_contents(__DIR__ . '/example/openapi2.json');
    }

    /**
     * @return string
     */
    protected static function getOpenApiJsonContent_No3()
    {
        return file_get_contents(__DIR__ . '/example/openapi3.json');
    }
}
