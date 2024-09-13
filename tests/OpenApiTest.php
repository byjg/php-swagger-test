<?php


namespace Tests;

use ByJG\ApiTools\Base\Schema;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

require_once "AbstractRequesterTest.php";

class OpenApiTest extends AbstractRequesterTest
{
    public function setUp(): void
    {
        $schema = Schema::getInstance(file_get_contents(__DIR__ . '/rest/openapi.json'));
        $this->setSchema($schema);
    }
}