<?php

namespace ByJG\ApiTools\Base;

use ByJG\ApiTools\Exception\NotMatchedException;

/**
 * A value matched against a schema of the specification itself, rather than against the
 * body of a request or a response. The rules of each specification version are applied
 * by the matching in Body.
 *
 * @see Schema::matchDefinition()
 */
class DefinitionBody extends Body
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function match(mixed $body, ?string $contentType = null): bool
    {
        if (!$this->matchSchema($this->name, $this->structure, $body)) {
            throw new NotMatchedException("Value does not match the definition '$this->name'", $this->structure);
        }
        return true;
    }
}
