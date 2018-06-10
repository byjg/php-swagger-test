<?php
/**
 * User: jg
 * Date: 22/05/17
 * Time: 10:52
 */

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
        foreach ($this->structure as $parameter) {
            if ($parameter['in'] == "body") {
                if ($parameter['required'] === true && empty($body)) {
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
