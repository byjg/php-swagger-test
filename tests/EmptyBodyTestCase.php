<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;
use PHPUnit\Framework\TestCase;

/**
 * An empty JSON value is a body, not a missing one: a list endpoint that finds nothing
 * answers `[]`, and a required request body can be an empty list. Only `null` and `""`
 * mean that no body was sent. Each subclass builds the specification in one version.
 */
abstract class EmptyBodyTestCase extends TestCase
{
    /**
     * POST /test with a required request body and a 200 response, both using the given
     * schema.
     */
    abstract protected function schemaFor(array $schema, array $definitions = []): Schema;

    protected function listSchema(): array
    {
        return ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']]]];
    }

    public function testEmptyListIsAValidResponse(): void
    {
        $this->assertTrue($this->schemaFor($this->listSchema())->getResponseParameters('/test', 'post', 200)->match([]));
    }

    public function testEmptyListIsAValidRequiredRequestBody(): void
    {
        $this->assertTrue($this->schemaFor($this->listSchema())->getRequestParameters('/test', 'post')->match([]));
    }

    public function testMissingRequiredRequestBodyIsStillRejected(): void
    {
        $body = $this->schemaFor($this->listSchema())->getRequestParameters('/test', 'post');

        $this->expectException(RequiredArgumentNotFound::class);
        $body->match(null);
    }

    public function testMissingResponseBodyIsStillRejected(): void
    {
        $body = $this->schemaFor($this->listSchema())->getResponseParameters('/test', 'post', 200);

        $this->expectException(NotMatchedException::class);
        $body->match(null);
    }

    public function testEmptyObjectStillHasToMatchTheSchema(): void
    {
        $schema = ['type' => 'object', 'required' => ['name'], 'properties' => ['name' => ['type' => 'string']]];
        $body = $this->schemaFor($schema)->getRequestParameters('/test', 'post');

        // Not "missing": it is a body, and it lacks the required property.
        $this->expectException(NotMatchedException::class);
        $body->match([]);
    }
}
