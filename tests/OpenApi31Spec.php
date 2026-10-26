<?php

namespace Tests;

/**
 * Builds the test specification as OpenAPI 3.1. The layout is the one of 3.0; only the
 * version changes, and with it the validator the schema is read with.
 */
trait OpenApi31Spec
{
    use OpenApi30Spec;

    protected function openApiVersion(): string
    {
        return '3.1.0';
    }
}
