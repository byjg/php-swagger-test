<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;

/**
 * Builds the test specification as Swagger 2.0: the request body is an `in: body`
 * parameter and the response declares its schema directly.
 */
trait SwaggerSpec
{
    /**
     * POST /test with a required request body and a 200 response, both using the given
     * schema, and the given named schemas under `definitions`.
     */
    protected function schemaFor(array $schema, array $definitions = []): Schema
    {
        return Schema::fromArray([
            'swagger' => '2.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'definitions' => $definitions,
            'paths' => ['/test' => ['post' => [
                'parameters' => [['in' => 'body', 'name' => 'body', 'required' => true, 'schema' => $schema]],
                'responses' => ['200' => ['description' => 'Success', 'schema' => $schema]],
            ]]],
        ]);
    }

    /** The reference of a schema given to schemaFor() in $definitions. */
    protected function definitionRef(string $name): string
    {
        return "#/definitions/$name";
    }
}
