<?php

namespace ByJG\ApiTools\Swagger;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericSwaggerException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;

class SwaggerResponseBody extends Body
{
    /**
     * @param $body
     * @return bool
     * @throws GenericSwaggerException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
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
