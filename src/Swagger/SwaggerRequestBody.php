<?php

namespace ByJG\ApiTools\Swagger;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;

class SwaggerRequestBody extends Body
{
    /**
     * @inheritDoc
     */
    public function match(mixed $body): bool
    {
        $hasFormData = false;
        foreach ($this->structure as $parameter) {
            if ($parameter['in'] === "body") {
                if (isset($parameter['required']) && $parameter['required'] === true && empty($body)) {
                    throw new RequiredArgumentNotFound('The body is required but it is empty');
                }
                return $this->matchSchema($this->name, $parameter['schema'], $body);
            }
            if ($parameter['in'] === "formData") {
                $hasFormData = true;
                if (isset($parameter['required']) && $parameter['required'] === true && !isset($body[$parameter['name']])) {
                    throw new RequiredArgumentNotFound("The formData parameter '{$parameter['name']}' is required but it isn't found. ");
                }
                if (!$this->matchTypes($parameter['name'], $parameter, ($body[$parameter['name']] ?? null))) {
                    throw new NotMatchedException("The formData parameter '{$parameter['name']}' not match with the specification");
                }
            }
        }

        if (!empty($body) && !$hasFormData) {
            throw new InvalidDefinitionException('Body is passed but there is no request body definition');
        }

        return false;
    }
}
