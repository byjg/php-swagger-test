<?php

namespace Test;

use ByJG\ApiTools\OpenApi\OpenApiSchema;
use PHPUnit\Framework\TestCase;

class OpenApiBodyTestCase extends TestCase
{
    /**
     * @param bool $allowNullValues
     * @return OpenApiSchema
     */
    protected static function openApiSchema($allowNullValues = false)
    {
        return \ByJG\ApiTools\Base\Schema::getInstance(
            self::getOpenApiJsonContent(),
            $allowNullValues
        );
    }

    /**
     * @param bool $allowNullValues
     * @return OpenApiSchema
     */
    protected static function openApiSchema2($allowNullValues = false)
    {
        return \ByJG\ApiTools\Base\Schema::getInstance(
            self::getOpenApiJsonContent_No2(),
            $allowNullValues
        );
    }

    /**
     * @param bool $allowNullValues
     * @return OpenApiSchema
     */
    protected static function openApiSchema3($allowNullValues = false)
    {
        return \ByJG\ApiTools\Base\Schema::getInstance(
            self::getOpenApiJsonContent_No3(),
            $allowNullValues
        );
    }

    /**
     * @param bool $allowNullValues
     * @return OpenApiSchema
     */
    protected static function openApiSchema5($allowNullValues = false)
    {
        return \ByJG\ApiTools\Base\Schema::getInstance(
            self::getOpenApiJsonContent_No5(),
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

    /**
     * @return string
     */
    protected static function getOpenApiJsonContent_No5()
    {
        return file_get_contents(__DIR__ . '/example/openapi5.json');
    }
}
