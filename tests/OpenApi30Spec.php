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
     * schema, and the given named schemas under `components/schemas`.
     */
    protected function schemaFor(array $schema, array $definitions = []): Schema
    {
        $content = ['application/json' => ['schema' => $schema]];

        return Schema::fromArray([
            'openapi' => $this->openApiVersion(),
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'components' => ['schemas' => $definitions],
            'paths' => ['/test' => ['post' => [
                'requestBody' => ['required' => true, 'content' => $content],
                'responses' => ['200' => ['description' => 'Success', 'content' => $content]],
            ]]],
        ]);
    }

    /** The reference of a schema given to schemaFor() in $definitions. */
    protected function definitionRef(string $name): string
    {
        return "#/components/schemas/$name";
    }
}
