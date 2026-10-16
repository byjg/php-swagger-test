<?php

namespace ByJG\ApiTools\OpenApi;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Exception\NotMatchedException;
use SimpleXMLElement;

class OpenApiResponseBody extends Body
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function match(mixed $body, ?string $contentType = null): bool
    {
        if (empty($this->structure['content']) && !isset($this->structure['$ref'])) {
            if (!empty($body)) {
                throw new NotMatchedException("Expected empty body for " . $this->name);
            }
            return true;
        } elseif (!empty($this->structure['content']) && empty($body)) {
            throw new NotMatchedException("Body is expected for " . $this->name);
        }

        if(!isset($this->structure['content']) && isset($this->structure['$ref'])){
            $definition = $this->schema->getDefinition($this->structure['$ref']);
            return $this->matchSchema($this->name, $definition, $body) ?? false;
        }

        // Without the content type the body was sent with, guess it from what the body was parsed into
        if (empty($contentType)) {
            if ($body instanceof SimpleXMLElement) {
                if (isset($this->structure['content']["application/xml"])) {
                    $contentType = "application/xml";
                } elseif (isset($this->structure['content']["text/xml"])) {
                    $contentType = "text/xml";
                }
            } elseif (is_array($body)) {
                $contentType = "application/json";
            } elseif (is_string($body) || is_numeric($body)) {
                $contentType = "text/plain";
            }
        }

        if (empty($contentType)) {
            throw new NotMatchedException("Could not find body content type for " . $this->name);
        }

        $mediaType = $this->findMediaType($contentType);
        if ($mediaType === null) {
            throw new NotMatchedException("Content type '$contentType' not found for " . $this->name);
        }

        // A media type documented without a schema accepts any body
        if (!isset($this->structure['content'][$mediaType]['schema'])) {
            return true;
        }

        // An XML body is validated as the array it converts to
        if ($body instanceof SimpleXMLElement) {
            $encoded = json_encode($body);
            $body = json_decode($encoded !== false ? $encoded : '{}', true);
        }

        return $this->matchSchema($this->name, $this->structure['content'][$mediaType]['schema'], $body) ?? false;
    }
}
