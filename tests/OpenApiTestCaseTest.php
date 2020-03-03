<?php

namespace Test;

class OpenApiTestCaseTest extends TestingTestCase
{

    public function setUp()
    {
        $this->filePath = __DIR__ . '/rest/openapi.json';

        // This is important!
        parent::setUp();
    }
}
