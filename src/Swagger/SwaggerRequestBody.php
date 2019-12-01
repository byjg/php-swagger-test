<?php

namespace ByJG\ApiTools\Swagger;

use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;
use ByJG\ApiTools\SwaggerBody;

class SwaggerRequestBody extends SwaggerBody
{
    /**
     * @param $body
     * @return bool
     * @throws Exception\DefinitionNotFoundException
     * @throws Exception\GenericSwaggerException
     * @throws Exception\InvalidRequestException
     * @throws Exception\NotMatchedException
     * @throws InvalidDefinitionException
     * @throws RequiredArgumentNotFound
     */
    public function match($body)
    {
        foreach ($this->structure as $parameter) {
            if ($parameter['in'] == "body") {
                if (isset($parameter['required']) && $parameter['required'] === true && empty($body)) {
                    throw new RequiredArgumentNotFound('The body is required but it is empty');
                }
                return $this->matchSchema($this->name, $parameter['schema'], $body);
            }
        }

        if (!empty($body)) {
            throw new InvalidDefinitionException('Body is passed but there is no request body definition');
        }

        return false;
    }
}
