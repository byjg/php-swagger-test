<?php
/**
 * User: jg
 * Date: 22/05/17
 * Time: 10:52
 */

namespace ByJG\Swagger;

use ByJG\Swagger\Exception\InvalidDefinitionException;

class SwaggerRequestBody extends SwaggerBody
{
    public function match($body)
    {
        foreach ($this->structure as $parameter) {
            if ($parameter['in'] == "body") {
                return $this->matchSchema($this->name, $parameter['schema'], $body);
            }
        }

        throw new InvalidDefinitionException('There is no body for match');
    }
}
