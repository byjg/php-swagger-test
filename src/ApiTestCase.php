<?php

namespace ByJG\ApiTools;

use ByJG\ApiTools\Base\BaseTestCase;
use GuzzleHttp\GuzzleException;
use PHPUnit\Framework\TestCase;

abstract class ApiTestCase extends TestCase
{
   use AssertRequestAgainstSchema;
}
