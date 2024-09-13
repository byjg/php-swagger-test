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
            throw new InvalidDefinitionException('Body is passed but there is no request body definition');
        }

        return false;
    }
}
