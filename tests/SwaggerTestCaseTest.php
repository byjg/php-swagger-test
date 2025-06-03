<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;

class SwaggerTestCaseTest extends TestingTestCase
{

    #[\Override]
    public function setUp(): void
    {
        $schema = Schema::getInstance(file_get_contents(__DIR__ . '/rest/swagger.json'));
        $this->setSchema($schema);
    }
}
