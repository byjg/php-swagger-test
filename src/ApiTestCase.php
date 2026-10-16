<?php

namespace ByJG\ApiTools;

use PHPUnit\Framework\TestCase;

/**
 * Base test case for OpenAPI/Swagger validation.
 *
 * This class extends PHPUnit's TestCase and uses the OpenApiValidation trait
 * to provide OpenAPI/Swagger validation capabilities.
 *
 * If you need to extend a different base class, you can use the OpenApiValidation
 * trait directly instead of extending this class.
 *
 * @see OpenApiValidation
 */
abstract class ApiTestCase extends TestCase
{
    use OpenApiValidation;
}
