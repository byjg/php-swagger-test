<?php

namespace Test;

use ByJG\ApiTools\Base\Schema;

class SwaggerTestCaseTest extends TestingTestCase
{

    public function setUp()
    {
        $schema = Schema::getInstance(file_get_contents(__DIR__ . '/rest/swagger.json'));
        $this->setSchema($schema);
    }
}
