<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The length and range constraints of a schema, checked on a request and on a response.
 * Each subclass builds the specification in one version.
 */
abstract class ConstraintsTestCase extends TestCase
{
    /**
     * POST /test with a required request body and a 200 response, both using the given
     * schema.
     */
    abstract protected function schemaFor(array $schema): Schema;

    /** A spec whose body is an object with a single property `value` using the given schema. */
    protected function schemaWithValue(array $property): Schema
    {
        return $this->schemaFor([
            'type' => 'object',
            'properties' => ['value' => $property],
        ]);
    }

    public static function acceptedProvider(): array
    {
        return [
            'maxLength at the limit' => [['type' => 'string', 'maxLength' => 5], 'abcde'],
            'maxLength below the limit' => [['type' => 'string', 'maxLength' => 5], 'abc'],
            'maxLength empty string' => [['type' => 'string', 'maxLength' => 5], ''],
            'maxLength counts characters, not bytes' => [['type' => 'string', 'maxLength' => 5], 'ação!'],
            'minLength at the limit' => [['type' => 'string', 'minLength' => 3], 'abc'],
            'minLength above the limit' => [['type' => 'string', 'minLength' => 3], 'abcd'],
            'minLength zero accepts empty' => [['type' => 'string', 'minLength' => 0], ''],
            'minLength and maxLength together' => [['type' => 'string', 'minLength' => 2, 'maxLength' => 4], 'abc'],
            'minimum at the limit' => [['type' => 'number', 'minimum' => 0], 0],
            'minimum above the limit' => [['type' => 'number', 'minimum' => 0], 0.01],
            'minimum negative limit' => [['type' => 'integer', 'minimum' => -10], -10],
            'maximum at the limit' => [['type' => 'integer', 'maximum' => 100], 100],
            'maximum below the limit' => [['type' => 'number', 'maximum' => 99999999.99], 49.9],
            'minimum and maximum together' => [['type' => 'integer', 'minimum' => 1, 'maximum' => 3], 2],
            'numeric string within range' => [['type' => 'number', 'minimum' => 1, 'maximum' => 3], '2.5'],
            'no constraint, long string' => [['type' => 'string'], str_repeat('a', 500)],
            'no constraint, negative number' => [['type' => 'number'], -5],
        ];
    }

    #[DataProvider('acceptedProvider')]
    public function testValueWithinConstraintsIsAccepted(array $property, mixed $value): void
    {
        $schema = $this->schemaWithValue($property);

        $this->assertTrue($schema->getRequestParameters('/test', 'post')->match(['value' => $value]));
        $this->assertTrue($schema->getResponseParameters('/test', 'post', 200)->match(['value' => $value]));
    }

    public static function rejectedProvider(): array
    {
        return [
            'maxLength exceeded by one' => [['type' => 'string', 'maxLength' => 5], 'abcdef', "in 'value' is longer than maxLength 5"],
            'maxLength 100 with 101 characters' => [['type' => 'string', 'maxLength' => 100], str_repeat('a', 101), 'is longer than maxLength 100'],
            'maxLength exceeded in characters' => [['type' => 'string', 'maxLength' => 5], 'ação!!', 'is longer than maxLength 5'],
            'maxLength zero rejects any text' => [['type' => 'string', 'maxLength' => 0], 'a', 'is longer than maxLength 0'],
            'minLength short by one' => [['type' => 'string', 'minLength' => 3], 'ab', "in 'value' is shorter than minLength 3"],
            'minLength rejects empty string' => [['type' => 'string', 'minLength' => 1], '', 'is shorter than minLength 1'],
            'minLength counts characters, not bytes' => [['type' => 'string', 'minLength' => 3], 'çã', 'is shorter than minLength 3'],
            'minimum negative value' => [['type' => 'number', 'minimum' => 0], -5, "Value '-5' in 'value' is less than minimum 0"],
            'minimum just below the limit' => [['type' => 'number', 'minimum' => 0], -0.01, 'is less than minimum 0'],
            'minimum integer' => [['type' => 'integer', 'minimum' => 1], 0, 'is less than minimum 1'],
            'maximum exceeded' => [['type' => 'integer', 'maximum' => 100], 101, "Value '101' in 'value' is greater than maximum 100"],
            'maximum just above the limit' => [['type' => 'number', 'maximum' => 1.5], 1.51, 'is greater than maximum 1.5'],
            'numeric string above maximum' => [['type' => 'number', 'maximum' => 3], '3.5', 'is greater than maximum 3'],
        ];
    }

    #[DataProvider('rejectedProvider')]
    public function testRequestOutsideConstraintsIsRejected(array $property, mixed $value, string $message): void
    {
        $body = $this->schemaWithValue($property)->getRequestParameters('/test', 'post');

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage($message);
        $body->match(['value' => $value]);
    }

    #[DataProvider('rejectedProvider')]
    public function testResponseOutsideConstraintsIsRejected(array $property, mixed $value, string $message): void
    {
        $body = $this->schemaWithValue($property)->getResponseParameters('/test', 'post', 200);

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage($message);
        $body->match(['value' => $value]);
    }

    public function testConstraintsApplyToArrayItemsAndNestedObjects(): void
    {
        $body = $this->schemaWithValue([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string', 'maxLength' => 3]],
            ],
        ])->getRequestParameters('/test', 'post');

        $this->assertTrue($body->match(['value' => [['name' => 'abc'], ['name' => 'de']]]));

        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("is longer than maxLength 3");
        $body->match(['value' => [['name' => 'abc'], ['name' => 'abcd']]]);
    }
}
