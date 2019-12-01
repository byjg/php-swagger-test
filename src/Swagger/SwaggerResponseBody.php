<?php

namespace ByJG\ApiTools\Swagger;

use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\SwaggerBody;

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
        if (!isset($this->structure['schema'])) {
            if (!empty($body)) {
                throw new NotMatchedException("Expected empty body for " . $this->name);
            }
            return true;
        }
        return $this->matchSchema($this->name, $this->structure['schema'], $body);
    }
}
