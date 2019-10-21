<?php

namespace ByJG\Swagger;

use ByJG\Swagger\Exception\InvalidDefinitionException;
use ByJG\Swagger\Exception\RequiredArgumentNotFound;

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
        if ($this->swaggerSchema->getSpecificationVersion() === '3') {
            if (isset($this->structure['content']) || isset($this->structure['$ref'])) {
                if (isset($this->structure['required']) && $this->structure['required'] === true && empty($body)) {
                    throw new RequiredArgumentNotFound('The body is required but it is empty');
                }

                if (isset($this->structure['$ref'])) {
                    return $this->matchSchema($this->name, $this->structure, $body);
                }

                return $this->matchSchema($this->name, $this->structure['content'][key($this->structure['content'])]['schema'], $body);
            }

            if (!empty($body)) {
                throw new InvalidDefinitionException('Body is passed but there is no request body definition');
            }
        }

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
