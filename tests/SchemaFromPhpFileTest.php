<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * Schema::fromFile() reads a PHP file that returns the specification as an array: OPcache keeps the array in
 * memory, instead of decoding the JSON on each request
 */
class SchemaFromPhpFileTest extends TestCase
{
    private string $file;

    #[Override]
    public function setUp(): void
    {
        $this->file = sys_get_temp_dir() . '/schema-' . bin2hex(random_bytes(6)) . '.php';
    }

    #[Override]
    public function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    public function testAPhpFileIsTheSameSchemaAsTheJson(): void
    {
        foreach (['openapi2.json', 'openapi31.json'] as $json) {
            $data = json_decode(file_get_contents(__DIR__ . "/example/$json"), true);
            file_put_contents($this->file, '<?php return ' . var_export($data, true) . ";\n");

            $this->assertEquals(Schema::fromFile(__DIR__ . "/example/$json"), Schema::fromFile($this->file), $json);
        }
    }

    public function testAPhpFileThatReturnsNoArray(): void
    {
        file_put_contents($this->file, "<?php return 'openapi';\n");

        $this->expectException(InvalidArgumentException::class);
        Schema::fromFile($this->file);
    }
}
