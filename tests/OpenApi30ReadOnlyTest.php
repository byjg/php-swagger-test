<?php

namespace Tests;

use ByJG\ApiTools\Exception\NotMatchedException;

class OpenApi30ReadOnlyTest extends ReadOnlyTestCase
{
    use OpenApi30Spec;

    /** writeOnly, defined from OpenAPI 3.0, is the opposite: required only in a request. */
    public function testAResponseDoesNotNeedAWriteOnlyProperty(): void
    {
        $schema = $this->itemSchema(['type' => 'string', 'writeOnly' => true]);
        $body = $this->schemaFor($schema)->getResponseParameters('/test', 'post', 200);

        $this->assertTrue($body->match(['name' => 'Keyboard']));
    }

    public function testARequestStillNeedsAWriteOnlyProperty(): void
    {
        $schema = $this->itemSchema(['type' => 'string', 'writeOnly' => true]);
        $body = $this->schemaFor($schema)->getRequestParameters('/test', 'post');

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Required property 'id'");
        $body->match(['name' => 'Keyboard']);
    }
}
