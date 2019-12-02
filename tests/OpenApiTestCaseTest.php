<?php

namespace Test;

class OpenApiTestCaseTest extends BaseTestCase
{

    public function setUp()
    {
        $this->filePath = __DIR__ . '/rest/openapi.json';

        // This is important!
        parent::setUp();
    }
}
