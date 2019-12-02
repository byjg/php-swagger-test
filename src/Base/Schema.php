<?php

namespace ByJG\ApiTools\Base;

use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\ApiTools\OpenApi\OpenApiResponseBody;
use ByJG\ApiTools\OpenApi\OpenApiSchema;
use ByJG\ApiTools\Swagger\SwaggerResponseBody;
use ByJG\ApiTools\Swagger\SwaggerSchema;
use ByJG\Util\Uri;

abstract class Schema
{
    protected $jsonFile;
    protected $allowNullValues = false;
    protected $specificationVersion;

    const SWAGGER_PATHS = "paths";
    const SWAGGER_PARAMETERS = "parameters";
    const SWAGGER_COMPONENTS = "components";

    /**
     * Returns the major specification version
     * @return string
     */
    public function getSpecificationVersion()
    {
        return $this->specificationVersion;
    }

    public static function getInstance($jsonFile, $extraArgs = false)
    {
        $jsonFile = json_decode($jsonFile, true);
        if (isset($jsonFile['swagger'])) {
            return new SwaggerSchema($jsonFile, $extraArgs);
        }

        return new OpenApiSchema($jsonFile);
    }

    /**
     * @param $path
     * @param $method
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function getPathDefinition($path, $method)
    {
        $method = strtolower($method);

        $path = preg_replace('~^' . $this->getBasePath() . '~', '', $path);

        $uri = new Uri($path);

        // Try direct match
        if (isset($this->jsonFile[self::SWAGGER_PATHS][$uri->getPath()])) {
            if (isset($this->jsonFile[self::SWAGGER_PATHS][$uri->getPath()][$method])) {
                return $this->jsonFile[self::SWAGGER_PATHS][$uri->getPath()][$method];
            }
            throw new HttpMethodNotFoundException("The http method '$method' not found in '$path'");
        }

        // Try inline parameter
        foreach (array_keys($this->jsonFile[self::SWAGGER_PATHS]) as $pathItem) {
            if (strpos($pathItem, '{') === false) {
                continue;
            }

            $pathItemPattern = '~^' . preg_replace('~{(.*?)}~', '(?<\1>[^/]+)', $pathItem) . '$~';

            $matches = [];
            if (preg_match($pathItemPattern, $uri->getPath(), $matches)) {
                $pathDef = $this->jsonFile[self::SWAGGER_PATHS][$pathItem];
                if (!isset($pathDef[$method])) {
                    throw new HttpMethodNotFoundException("The http method '$method' not found in '$path'");
                }

                $parametersPathMethod = [];
                $parametersPath = [];

                if (isset($pathDef[$method][self::SWAGGER_PARAMETERS])) {
                    $parametersPathMethod = $pathDef[$method][self::SWAGGER_PARAMETERS];
                }

                if (isset($pathDef[self::SWAGGER_PARAMETERS])) {
                    $parametersPath = $pathDef[self::SWAGGER_PARAMETERS];
                }

                $this->validateArguments('path', array_merge($parametersPathMethod, $parametersPath), $matches);

                return $pathDef[$method];
            }
        }

        throw new PathNotFoundException('Path "' . $path . '" not found');
    }

    /**
     * @param $path
     * @param $method
     * @param $status
     * @return Body
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function getResponseParameters($path, $method, $status)
    {
        $structure = $this->getPathDefinition($path, $method);

        if (!isset($structure['responses'][$status])) {
            throw new InvalidDefinitionException("Could not found status code '$status' in '$path' and '$method'");
        }

        if ($this instanceof SwaggerSchema) {
            return new SwaggerResponseBody($this, "$method $status $path", $structure['responses'][$status]);
        }

        return new OpenApiResponseBody($this, "$method $status $path", $structure['responses'][$status]);
    }

    /**
     * OpenApi 2.0 doesn't describe null values, so this flag defines,
     * if match is ok when one of property
     *
     * @return bool
     */
    public function isAllowNullValues()
    {
        return $this->allowNullValues;
    }

    /**
     * @param $parameterIn
     * @param $parameters
     * @param $arguments
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     */
    abstract protected function validateArguments($parameterIn, $parameters, $arguments);

    abstract public function getBasePath();

    /**
     * @param $name
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     */
    abstract public function getDefintion($name);

    /**
     * @param $path
     * @param $method
     * @return Body
     * @throws HttpMethodNotFoundException
     * @throws PathNotFoundException
     */
    abstract public function getRequestParameters($path, $method);
}
