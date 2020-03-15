<?php

namespace ByJG\ApiTools;

use ByJG\ApiTools\Base\BaseTestCase;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\GenericSwaggerException;
use GuzzleHttp\GuzzleException;

abstract class ApiTestCase extends BaseTestCase
{

    /**
     * @throws GenericSwaggerException
     */
    protected function setUp()
    {
        if (empty($this->filePath)) {
            throw new GenericSwaggerException('You have to define the property $filePath');
        }

        $this->schema = Schema::getInstance(file_get_contents($this->filePath));
    }
}
