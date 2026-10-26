<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

/**
 * A `readOnly` property listed in `required` is required only in a response: it is not sent
 * in a request, nor in a value about to be saved (Schema::matchDefinition()). Each subclass
 * builds the specification in one version.
 */
abstract class ReadOnlyTestCase extends TestCase
{
    abstract protected function schemaFor(array $schema, array $definitions = []): Schema;

    abstract protected function definitionRef(string $name): string;

    /** An object whose `id` is required and readOnly, and whose `name` is required. */
    protected function itemSchema(array $id = ['type' => 'integer', 'readOnly' => true]): array
    {
        return [
            'type' => 'object',
            'required' => ['id', 'name'],
            'properties' => [
                'id' => $id,
                'name' => ['type' => 'string'],
            ],
        ];
    }

    public function testARequestDoesNotNeedAReadOnlyProperty(): void
    {
        $body = $this->schemaFor($this->itemSchema())->getRequestParameters('/test', 'post');

        $this->assertTrue($body->match(['name' => 'Keyboard']));
    }

    public function testAResponseStillNeedsAReadOnlyProperty(): void
    {
        $body = $this->schemaFor($this->itemSchema())->getResponseParameters('/test', 'post', 200);

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Required property 'id'");
        $body->match(['name' => 'Keyboard']);
    }

    public function testARequestStillNeedsTheOtherRequiredProperties(): void
    {
        $body = $this->schemaFor($this->itemSchema())->getRequestParameters('/test', 'post');

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Required property 'name'");
        $body->match(['id' => 1]);
    }

    public function testAReadOnlyPropertyThatIsSentIsStillMatched(): void
    {
        $body = $this->schemaFor($this->itemSchema())->getRequestParameters('/test', 'post');

        $this->expectException(NotMatchedException::class);
        $body->match(['id' => 'not a number', 'name' => 'Keyboard']);
    }

    public function testAValueToBeSavedDoesNotNeedAReadOnlyProperty(): void
    {
        $schema = $this->schemaFor(['type' => 'object'], ['Item' => $this->itemSchema()]);

        // A new record has no key yet: the database generates it.
        $this->assertTrue($schema->matchDefinition($this->definitionRef('Item'), ['name' => 'Keyboard']));
    }
}
