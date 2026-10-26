<?php

namespace Tests;

use ByJG\ApiTools\Exception\NotMatchedException;

class OpenApi30EmptyBodyTest extends EmptyBodyTestCase
{
    use OpenApi30Spec;

    /** OpenAPI 3 checks for the body before the schema, so the error says it is missing. */
    public function testMissingResponseBodyIsReportedAsMissing(): void
    {
        $body = $this->schemaFor($this->listSchema())->getResponseParameters('/test', 'post', 200);

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('Body is expected');
        $body->match(null);
    }
}
