<?php

namespace Test;

class SwaggerTestCaseTest extends TestingTestCase
{

    public function setUp()
    {
        $this->filePath = __DIR__ . '/rest/swagger.json';

        // This is important!
        parent::setUp();
    }
}
