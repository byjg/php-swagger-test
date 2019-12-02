<?php

namespace Test;

class SwaggerTestCaseTest extends BaseTestCase
{

    public function setUp()
    {
        $this->filePath = __DIR__ . '/rest/swagger.json';

        // This is important!
        parent::setUp();
    }
}
