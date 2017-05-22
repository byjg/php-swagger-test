<?php
/**
 * User: jg
 * Date: 22/05/17
 * Time: 10:52
 */

namespace ByJG\Swagger;

use ByJG\Swagger\Exception\NotMatchedException;

class SwaggerResponseBody extends SwaggerBody
{
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
