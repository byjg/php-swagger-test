<?php

namespace ByJG\Swagger;

use ByJG\Swagger\Exception\NotMatchedException;

class SwaggerResponseBody extends SwaggerBody
{
    /**
     * @param $body
     * @return bool
     * @throws Exception\DefinitionNotFoundException
     * @throws Exception\GenericSwaggerException
     * @throws Exception\InvalidDefinitionException
     * @throws Exception\InvalidRequestException
     * @throws NotMatchedException
     */
    public function match($body)
    {
        if ($this->swaggerSchema->getSpecificationVersion() === '3') {
            if (!isset($this->structure['content'])) {
                if (!empty($body)) {
                    throw new NotMatchedException("Expected empty body for " . $this->name);
                }
                return true;
            }
            return $this->matchSchema($this->name, $this->structure['content'][key($this->structure['content'])]['schema'], $body);
        }

        if (!isset($this->structure['schema'])) {
            if (!empty($body)) {
                throw new NotMatchedException("Expected empty body for " . $this->name);
            }
            return true;
        }
        return $this->matchSchema($this->name, $this->structure['schema'], $body);
    }
}
