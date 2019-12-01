<?php

namespace ByJG\ApiTools\OpenApi;

use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Base\Body;

class OpenApiResponseBody extends Body
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
        if (!isset($this->structure['content'])) {
            if (!empty($body)) {
                throw new NotMatchedException("Expected empty body for " . $this->name);
            }
            return true;
        }
        return $this->matchSchema($this->name, $this->structure['content'][key($this->structure['content'])]['schema'], $body);
    }
}
