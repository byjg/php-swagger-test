<?php

namespace ByJG\ApiTools\Swagger;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\Util\Uri;

class SwaggerSchema extends Schema
{
    public function __construct($jsonFile, $allowNullValues = false)
    {
        if (!is_array($jsonFile)) {
            $jsonFile = json_decode($jsonFile, true);
        }
        $this->jsonFile = $jsonFile;
        $this->allowNullValues = (bool) $allowNullValues;
    }

    public function getHttpSchema()
    {
        return isset($this->jsonFile['schemes']) ? $this->jsonFile['schemes'][0] : '';
    }

    public function getHost()
    {
        return isset($this->jsonFile['host']) ? $this->jsonFile['host'] : '';
    }

    public function getBasePath()
    {
        return isset($this->jsonFile['basePath']) ? $this->jsonFile['basePath'] : '';
    }


    /**
     * @param $parameterIn
     * @param $parameters
     * @param $arguments
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     */
    protected function validateArguments($parameterIn, $parameters, $arguments)
    {
        foreach ($parameters as $parameter) {
            if ($parameter['in'] === $parameterIn
                && $parameter['type'] === "integer"
                && filter_var($arguments[$parameter['name']], FILTER_VALIDATE_INT) === false) {
                throw new NotMatchedException('Path expected an integer value');
            }
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     */
    public function getDefintion($name)
    {
        $nameParts = explode('/', $name);

        if (count($nameParts) < 3 || $nameParts[0] !== '#') {
            throw new InvalidDefinitionException('Invalid Definition');
        }

        if (!isset($this->jsonFile[$nameParts[1]][$nameParts[2]])) {
            throw new DefinitionNotFoundException("Definition '$name' not found");
        }

        return $this->jsonFile[$nameParts[1]][$nameParts[2]];
    }

    /**
     * @param $path
     * @param $method
     * @return SwaggerRequestBody
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function getRequestParameters($path, $method)
    {
        $structure = $this->getPathDefinition($path, $method);

        if (!isset($structure[self::SWAGGER_PARAMETERS])) {
            return new SwaggerRequestBody($this, "$method $path", []);
        }
        return new SwaggerRequestBody($this, "$method $path", $structure[self::SWAGGER_PARAMETERS]);
    }

    /**
     * OpenApi 2.0 doesn't describe null values, so this flag defines,
     * if match is ok when one of property
     *
     * @param $value
     */
    public function setAllowNullValues($value)
    {
        $this->allowNullValues = (bool) $value;
    }

    public function setServerVariable($var, $value)
    {
        $this->serverVariables[$var] = $value;
    }
}
