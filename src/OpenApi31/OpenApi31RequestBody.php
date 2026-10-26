<?php

namespace ByJG\ApiTools\OpenApi31;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;

class OpenApi31RequestBody extends Body
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function match(mixed $body, ?string $contentType = null): bool
    {
        if (empty($this->structure)) {
            if (!empty($body)) {
                throw new InvalidDefinitionException(
                    "Request body provided for '{$this->name}' but the OpenAPI 3.1 specification does not define a request body for this operation.\n\n" .
                    "Suggestion: Either remove the request body from your test using withRequestBody(), or add a 'requestBody' " .
                    "definition to your OpenAPI specification for this endpoint."
                );
            }
            return true;
        }

        if (isset($this->structure['required']) && $this->structure['required'] === true && $this->isAbsent($body)) {
            throw new RequiredArgumentNotFound('The body is required but it is empty');
        }

        if (!isset($this->structure['content'])) {
            return true;
        }

        // A media type documented without a schema accepts any body
        $schema = $this->getRequestSchema($contentType);
        if ($schema === null) {
            return true;
        }
        return $this->matchSchema($this->name, $schema, $body) ?? false;
    }

    #[\Override]
    protected function isRequest(): bool
    {
        return true;
    }
}
