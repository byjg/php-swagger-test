<?php

namespace ByJG\ApiTools\OpenApi;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericSwaggerException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;

class OpenApiResponseBody extends Body
{
    /**
     * @param string $body
     * @return bool
     * @throws GenericSwaggerException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     */
    public function match($body)
    {
        if (empty($this->structure['content']) && !isset($this->structure['$ref'])) {
            if (!empty($body)) {
                throw new NotMatchedException("Expected empty body for " . $this->name);
            }
            return true;
        }
        
        if(!isset($this->structure['content']) && isset($this->structure['$ref'])){
            $defintion = $this->schema->getDefinition($this->structure['$ref']);
            return $this->matchSchema($this->name, $defintion, $body);
        }
        
        return $this->matchSchema($this->name, $this->structure['content'][key($this->structure['content'])]['schema'], $body);
    }
}
