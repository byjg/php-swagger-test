<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;

/**
 * Builds the test specification as OpenAPI 3.0: the bodies are declared under
 * `content`, by media type.
 */
trait OpenApi30Spec
{
    protected function openApiVersion(): string
    {
        return '3.0.3';
    }

    /**
     * POST /test with a required request body and a 200 response, both using the given
     * schema.
     */
    protected function schemaFor(array $schema): Schema
    {
        $content = ['application/json' => ['schema' => $schema]];

        return Schema::fromArray([
            'openapi' => $this->openApiVersion(),
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => ['/test' => ['post' => [
                'requestBody' => ['required' => true, 'content' => $content],
                'responses' => ['200' => ['description' => 'Success', 'content' => $content]],
            ]]],
        ]);
    }
}
