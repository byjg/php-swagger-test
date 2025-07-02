<?php

namespace ByJG\ApiTools\OpenApi;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Exception\NotMatchedException;

class OpenApiResponseBody extends Body
{
    /**
     * @inheritDoc
     */
    public function match(mixed $body): bool
    {
        if (empty($this->structure['content']) && !isset($this->structure['$ref'])) {
            if (!empty($body)) {
                throw new NotMatchedException("Expected empty body for " . $this->name);
            }
            return true;
        }
        
        if(!isset($this->structure['content']) && isset($this->structure['$ref'])){
            $definition = $this->schema->getDefinition($this->structure['$ref']);
            return $this->matchSchema($this->name, $definition, $body) ?? false;
        }

        foreach ($this->structure['content'] as $contentType => $schema) {
            if ($contentType === 'application/json') {
                if (!isset($schema['schema'])) {
                    throw new NotMatchedException("Content type " . $contentType . " does not have schema");
                }
                return $this->matchSchema($this->name, $schema['schema'], $body) ?? false;
            }
        }

        return true;
    }
}
