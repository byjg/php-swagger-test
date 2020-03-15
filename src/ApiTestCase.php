<?php

namespace ByJG\ApiTools;

use ByJG\ApiTools\Base\BaseTestCase;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\GenericSwaggerException;
use GuzzleHttp\GuzzleException;

abstract class ApiTestCase extends BaseTestCase
{
    /** @var string|null */
    protected $filePath;

    /**
     * @throws GenericSwaggerException
     */
    protected function setUp()
    {
        // load and configure the schema if a path is set
        if (!empty($this->filePath)) {
            $data = file_get_contents($this->filePath);
            $schema = Schema::getInstance($data);
            $this->setSchema($schema);
        }
    }
}
