<?php

namespace ByJG\ApiTools\Swagger;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Exception\NotMatchedException;

class SwaggerResponseBody extends Body
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function match(mixed $body): bool
    {
        if (!isset($this->structure['schema'])) {
            if (!empty($body)) {
                throw new NotMatchedException("Expected empty body for " . $this->name);
            }
            return true;
        }
        return $this->matchSchema($this->name, $this->structure['schema'], $body) ?? false;
    }
}
