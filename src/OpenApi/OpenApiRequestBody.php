<?php

namespace ByJG\ApiTools\OpenApi;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;

class OpenApiRequestBody extends Body
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function match(mixed $body): bool
    {
        if (isset($this->structure['content']) || isset($this->structure['$ref'])) {
            if (isset($this->structure['required']) && $this->structure['required'] === true && empty($body)) {
                throw new RequiredArgumentNotFound('The body is required but it is empty');
            }

            if (isset($this->structure['$ref'])) {
                return $this->matchSchema($this->name, $this->structure, $body) ?? false;
            }

            return $this->matchSchema($this->name, $this->structure['content'][key($this->structure['content'])]['schema'], $body) ?? false;
        }

        if (!empty($body)) {
            throw new InvalidDefinitionException(
                "Request body provided for '{$this->name}' but the OpenAPI 3.0 specification does not define a request body for this operation.\n\n" .
                "Suggestion: Either remove the request body from your test using withRequestBody(), or add a 'requestBody' " .
                "definition to your OpenAPI specification for this endpoint."
            );
        }

        return false;
    }
}
