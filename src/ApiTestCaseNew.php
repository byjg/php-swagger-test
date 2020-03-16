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
    /** @var string|null */
    protected $filePath;

    /**
     * @throws GenericSwaggerException
     */
    protected function setUp(): void
    {
        // load and configure the schema if a path is set
        if (!empty($this->filePath)) {
            $data = file_get_contents($this->filePath);
            $schema = Schema::getInstance($data);
            $this->setSchema($schema);
        }
    }
}
