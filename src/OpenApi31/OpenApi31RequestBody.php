<?php

namespace ByJG\ApiTools\OpenApi31;

use ByJG\ApiTools\Base\Body;

class OpenApi31RequestBody extends Body
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function match(mixed $body, ?string $contentType = null): bool
    {
        if (empty($this->structure)) {
            return true;
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
}
