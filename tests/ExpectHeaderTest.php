<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\MockRequester;
use ByJG\ApiTools\OpenApiValidation;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for expectHeaderContains(). HTTP header names are case-insensitive.
 */
class ExpectHeaderTest extends TestCase
{
    use OpenApiValidation;

    #[Override]
    public function setUp(): void
    {
        $this->setSchema(Schema::fromFile(__DIR__ . '/rest/openapi.json'));
    }

    protected function request(): MockRequester
    {
        $expectedResponse = Response::getInstance(200)
            ->withBody(new MemoryStream(json_encode([
                "id" => 1,
                "name" => "Spike",
                "photoUrls" => []
            ])))
            ->withHeader("X-Test", "Some Value to test");

        $request = new MockRequester($expectedResponse);
        $request
            ->withMethod('GET')
            ->withPath("/pet/1");

        return $request;
    }

    public static function headerNames(): array
    {
        return [
            'same case' => ['X-Test'],
            'lower case' => ['x-test'],
            'upper case' => ['X-TEST'],
        ];
    }

    #[DataProvider('headerNames')]
    public function testExpectHeaderContainsIgnoresNameCase(string $headerName): void
    {
        $this->sendRequest($this->request()->expectHeaderContains($headerName, "Value"));
    }

    public function testExpectHeaderContainsMissingHeader(): void
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage("Does not exists header 'X-Other' with value 'Value'");

        $this->sendRequest($this->request()->expectHeaderContains("X-Other", "Value"));
    }
}
