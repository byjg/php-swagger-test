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

        $content = null;
        if (isset($this->structure['content']['application/json'])) {
            $content = $this->structure['content']['application/json'];
        } elseif (isset($this->structure['content']['application/xml'])) {
            $content = $this->structure['content']['application/xml'];
        } elseif (isset($this->structure['content']['text/xml'])) {
            $content = $this->structure['content']['text/xml'];
        } else {
            $contentKey = key($this->structure['content']);
            if ($contentKey !== null) {
                $content = $this->structure['content'][$contentKey];
            }
        }

        if ($content === null || !isset($content['schema'])) {
            return true;
        }

        return $this->matchSchema($this->name, $content['schema'], $body) ?? false;
    }
}
