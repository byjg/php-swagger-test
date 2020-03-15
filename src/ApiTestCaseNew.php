<?php

namespace ByJG\ApiTools;

use ByJG\ApiTools\Base\BaseTestCase;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\GenericSwaggerException;
use GuzzleHttp\GuzzleException;

/**
 * Class ApiTestCaseNew
 *
 * Use this class if you are using PHPUnit 8.5 or higher
 */
abstract class ApiTestCaseNew extends BaseTestCase
{

    /**
     * @throws GenericSwaggerException
     */
    protected function setUp(): void
    {
        if (empty($this->filePath)) {
            throw new GenericSwaggerException('You have to define the property $filePath');
        }

        $this->schema = Schema::getInstance(file_get_contents($this->filePath));
    }
}
