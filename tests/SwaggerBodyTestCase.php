<?php

namespace Test;

use ByJG\Swagger\SwaggerSchema;
use PHPUnit\Framework\TestCase;

class SwaggerBodyTestCase extends TestCase
{

    /**
     * @param bool $allowNullValues
     * @return SwaggerSchema
     */
    protected static function swaggerSchema($allowNullValues = false)
    {
        return new SwaggerSchema(
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
        return new SwaggerSchema(
            self::getSwaggerJsonContent_No2(),
            $allowNullValues
        );
    }

    /**
     * @param bool $allowNullValues
     * @return SwaggerSchema
     */
    protected static function openApiSchema($allowNullValues = false)
    {
        return new SwaggerSchema(
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
        return new SwaggerSchema(
            self::getOpenApiJsonContent_No2(),
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
}
