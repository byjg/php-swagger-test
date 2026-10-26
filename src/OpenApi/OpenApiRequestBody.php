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
    public function match(mixed $body, ?string $contentType = null): bool
    {
        if (isset($this->structure['content']) || isset($this->structure['$ref'])) {
            if (isset($this->structure['required']) && $this->structure['required'] === true && $this->isAbsent($body)) {
                throw new RequiredArgumentNotFound('The body is required but it is empty');
            }

            if (isset($this->structure['$ref'])) {
                return $this->matchSchema($this->name, $this->structure, $body) ?? false;
            }

            // A media type documented without a schema accepts any body
            $schema = $this->getRequestSchema($contentType);
            if ($schema === null) {
                return true;
            }
            return $this->matchSchema($this->name, $schema, $body) ?? false;
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

    #[\Override]
    protected function isRequest(): bool
    {
        return true;
    }
}
