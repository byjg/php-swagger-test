<?php

namespace Test;

use ByJG\ApiTools\Base\Schema;

class OpenApiTestCaseTest extends TestingTestCase
{

    public function setUp(): void 
    {
        $schema = Schema::getInstance(file_get_contents(__DIR__ . '/rest/openapi.json'));
        $this->setSchema($schema);
    }
}
