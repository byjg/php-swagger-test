<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\OpenApi31\OpenApi31Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OpenApi31SchemaTest extends TestCase
{
    public function testFromJsonCreatesOpenApi31Schema(): void
    {
        $json = file_get_contents(__DIR__ . '/example/openapi31.json');
        $schema = Schema::fromJson($json);

        $this->assertInstanceOf(OpenApi31Schema::class, $schema);
        $this->assertEquals('3.1.0', $schema->getSpecificationVersion());
    }

    public function testFromFileCreatesOpenApi31Schema(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/example/openapi31.json');

        $this->assertInstanceOf(OpenApi31Schema::class, $schema);
    }

    public function testFromArrayCreatesOpenApi31Schema(): void
    {
        $data = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Test',
                'version' => '1.0.0'
            ],
            'paths' => []
        ];

        $schema = Schema::fromArray($data);

        $this->assertInstanceOf(OpenApi31Schema::class, $schema);
    }

    public function testVersionDetection30vs31(): void
    {
        $data30 = [
            'openapi' => '3.0.3',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => []
        ];

        $data31 = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => []
        ];

        $schema30 = Schema::fromArray($data30);
        $schema31 = Schema::fromArray($data31);

        $this->assertInstanceOf(\ByJG\ApiTools\OpenApi\OpenApiSchema::class, $schema30);
        $this->assertInstanceOf(OpenApi31Schema::class, $schema31);
    }

    public function testServerVariablesWithOptionalDefault(): void
    {
        $data = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'servers' => [
                [
                    'url' => 'http://{host}/api',
                    'variables' => [
                        'host' => [
                            'enum' => ['localhost', 'example.com']
                            // Note: no 'default' key - this is valid in 3.1
                        ]
                    ]
                ]
            ],
            'paths' => []
        ];

        $schema = Schema::fromArray($data);

        // Should not throw exception even without default
        $url = $schema->getServerUrl();
        $this->assertIsString($url);
    }

    public function testHasWebhooks(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/example/openapi31-webhooks.json');

        $this->assertInstanceOf(OpenApi31Schema::class, $schema);
        $this->assertTrue($schema->hasWebhooks());
    }

    public function testGetWebhookNames(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/example/openapi31-webhooks.json');

        $webhookNames = $schema->getWebhookNames();

        $this->assertCount(2, $webhookNames);
        $this->assertContains('newUser', $webhookNames);
        $this->assertContains('orderUpdated', $webhookNames);
    }

    public function testGetWebhookDefinition(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/example/openapi31-webhooks.json');

        $webhookDef = $schema->getWebhookDefinition('newUser', 'post');

        $this->assertIsArray($webhookDef);
        $this->assertEquals('New user webhook', $webhookDef['summary']);
    }

    public function testGetWebhookDefinitionThrowsExceptionForNonExistent(): void
    {
        $this->expectException(\ByJG\ApiTools\Exception\DefinitionNotFoundException::class);

        $schema = Schema::fromFile(__DIR__ . '/example/openapi31-webhooks.json');
        $schema->getWebhookDefinition('nonExistent', 'post');
    }

    public function testSchemaDialect(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/example/openapi31.json');

        $this->assertInstanceOf(OpenApi31Schema::class, $schema);
        $this->assertTrue($schema->isJsonSchema202012());
    }

    public static function schemaDialects(): array
    {
        return [
            'not declared' => [null, true],
            'OAS 3.1 base dialect' => ['https://spec.openapis.org/oas/3.1/dialect/base', true],
            'JSON Schema 2020-12' => ['https://json-schema.org/draft/2020-12/schema', true],
            'JSON Schema draft-07' => ['http://json-schema.org/draft-07/schema#', false],
        ];
    }

    #[DataProvider('schemaDialects')]
    public function testSchemaDialectFromJsonSchemaDialect(?string $dialect, bool $is202012): void
    {
        $data = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [],
        ];
        if ($dialect !== null) {
            $data['jsonSchemaDialect'] = $dialect;
        }
        $schema = Schema::fromArray($data);

        $this->assertInstanceOf(OpenApi31Schema::class, $schema);
        $this->assertSame($dialect, $schema->getSchemaDialect());
        $this->assertSame($is202012, $schema->isJsonSchema202012());
    }

    public function testGetServerUrl(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/example/openapi31.json');

        $serverUrl = $schema->getServerUrl();

        $this->assertEquals('http://localhost:8080/api', $serverUrl);
    }

    public function testGetBasePath(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/example/openapi31.json');

        $basePath = $schema->getBasePath();

        $this->assertEquals('/api', $basePath);
    }
}
