<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

/**
 * Schema::matchDefinition(): a value matched against a named schema of the specification,
 * outside a request or a response. Each subclass builds the specification in one version.
 */
abstract class DefinitionTestCase extends TestCase
{
    abstract protected function schemaFor(array $schema, array $definitions = []): Schema;

    abstract protected function definitionRef(string $name): string;

    /** A spec with a `Product` (name required, price in a range) that refers to a `Category`. */
    protected function productSpec(): Schema
    {
        return $this->schemaFor(['type' => 'object'], [
            'Category' => [
                'type' => 'object',
                'required' => ['title'],
                'properties' => ['title' => ['type' => 'string', 'minLength' => 1]],
            ],
            'Product' => [
                'type' => 'object',
                'required' => ['name', 'price'],
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string', 'maxLength' => 100, 'minLength' => 1],
                    'price' => ['type' => 'number', 'maximum' => 99999999.99, 'minimum' => 0],
                    'category' => ['$ref' => $this->definitionRef('Category')],
                ],
            ],
        ]);
    }

    public function testAValidValueMatches(): void
    {
        $this->assertTrue($this->productSpec()->matchDefinition(
            $this->definitionRef('Product'),
            ['name' => 'Keyboard', 'price' => 49.9, 'category' => ['title' => 'Input']]
        ));
    }

    public function testARequiredPropertyIsChecked(): void
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Required property 'price'");
        $this->productSpec()->matchDefinition($this->definitionRef('Product'), ['name' => 'Keyboard']);
    }

    public function testAPartialValueSkipsTheRequiredProperties(): void
    {
        $this->assertTrue($this->productSpec()->matchDefinition($this->definitionRef('Product'), ['price' => 10], true));
    }

    public function testAPartialValueStillMatchesEachProperty(): void
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('is shorter than minLength 1');
        $this->productSpec()->matchDefinition($this->definitionRef('Product'), ['name' => ''], true);
    }

    public function testAValueOutsideTheRangeIsRejected(): void
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('is less than minimum 0');
        $this->productSpec()->matchDefinition($this->definitionRef('Product'), ['name' => 'Keyboard', 'price' => -5]);
    }

    public function testAReferencedDefinitionIsMatchedInFull(): void
    {
        // Partial applies to the definition matched, not to the ones it refers to.
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Required property 'title'");
        $this->productSpec()->matchDefinition($this->definitionRef('Product'), ['category' => []], true);
    }

    public function testAnUnknownDefinitionIsReported(): void
    {
        $this->expectException(DefinitionNotFoundException::class);
        $this->productSpec()->matchDefinition($this->definitionRef('Missing'), []);
    }
}
