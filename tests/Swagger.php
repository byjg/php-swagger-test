<?php


namespace Tests;

use ByJG\ApiTools\Base\Schema;

require_once "AbstractRequester.php";


class Swagger extends AbstractRequester
{
    #[\Override]
    public function setUp(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/rest/swagger.json');
        $this->setSchema($schema);
    }

}
