<?php

namespace Tests;

use ByJG\ApiTools\OpenApi\OpenApiSchema;
use PHPUnit\Framework\TestCase;

/**
 * baseclass for further tests
 *
 * @see OpenApiRequestBodyTest
 * @see OpenApiResponseBodyTest
 */
class OpenApiBodyTestCase extends TestCase
{
    /**
     * @param bool $allowNullValues
     * @return OpenApiSchema
     */
    protected static function openApiSchema(bool $allowNullValues = false): OpenApiSchema
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
    protected static function openApiSchema2(bool $allowNullValues = false): OpenApiSchema
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
    protected static function openApiSchema3(bool $allowNullValues = false): OpenApiSchema
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
    protected static function openApiSchema5(bool $allowNullValues = false): OpenApiSchema
    {
        return \ByJG\ApiTools\Base\Schema::getInstance(
            self::getOpenApiJsonContent_No5(),
            $allowNullValues
        );
    }

    /**
     * @return string
     */
    protected static function getOpenApiJsonContent(): string
    {
        return file_get_contents(__DIR__ . '/example/openapi.json');
    }

    /**
     * @return string
     */
    protected static function getOpenApiJsonContent_No2(): string
    {
        return file_get_contents(__DIR__ . '/example/openapi2.json');
    }

    /**
     * @return string
     */
    protected static function getOpenApiJsonContent_No3(): string
    {
        return file_get_contents(__DIR__ . '/example/openapi3.json');
    }

    /**
     * @return string
     */
    protected static function getOpenApiJsonContent_No5(): string
    {
        return file_get_contents(__DIR__ . '/example/openapi5.json');
    }
}
