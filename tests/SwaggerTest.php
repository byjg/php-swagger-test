<?php


namespace Test;

use ByJG\ApiTools\Base\Schema;

require_once "AbstractRequesterTest.php";


class SwaggerTest extends AbstractRequesterTest
{
    public function setUp(): void
    {
        $schema = Schema::getInstance(file_get_contents(__DIR__ . '/rest/swagger.json'));
        $this->setSchema($schema);
    }

}
