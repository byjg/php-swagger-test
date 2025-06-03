<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\OpenApi\OpenApiSchema;
use ByJG\ApiTools\Swagger\SwaggerSchema;
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
     * @return OpenApiSchema|SwaggerSchema
     */
    protected static function openApiSchema(bool $allowNullValues = false): OpenApiSchema|SwaggerSchema
    {
        return Schema::getInstance(
            self::getOpenApiJsonContent(),
            $allowNullValues
        );
    }

    /**
     * @param bool $allowNullValues
     * @return OpenApiSchema|SwaggerSchema
     */
    protected static function openApiSchema2(bool $allowNullValues = false): OpenApiSchema|SwaggerSchema
    {
        return Schema::getInstance(
            self::getOpenApiJsonContent_No2(),
            $allowNullValues
        );
    }

    /**
     * @param bool $allowNullValues
     * @return OpenApiSchema|SwaggerSchema
     */
    protected static function openApiSchema3(bool $allowNullValues = false): OpenApiSchema|SwaggerSchema
    {
        return Schema::getInstance(
            self::getOpenApiJsonContent_No3(),
            $allowNullValues
        );
    }

    /**
     * @param bool $allowNullValues
     * @return OpenApiSchema|SwaggerSchema
     */
    protected static function openApiSchema5(bool $allowNullValues = false): OpenApiSchema|SwaggerSchema
    {
        return Schema::getInstance(
            self::getOpenApiJsonContent_No5(),
            $allowNullValues
        );
    }

    /**
     * @return false|string
     */
    protected static function getOpenApiJsonContent(): string|false
    {
        return file_get_contents(__DIR__ . '/example/openapi.json');
    }

    /**
     * @return false|string
     */
    protected static function getOpenApiJsonContent_No2(): string|false
    {
        return file_get_contents(__DIR__ . '/example/openapi2.json');
    }

    /**
     * @return false|string
     */
    protected static function getOpenApiJsonContent_No3(): string|false
    {
        return file_get_contents(__DIR__ . '/example/openapi3.json');
    }

    /**
     * @return false|string
     */
    protected static function getOpenApiJsonContent_No5(): string|false
    {
        return file_get_contents(__DIR__ . '/example/openapi5.json');
    }
}
