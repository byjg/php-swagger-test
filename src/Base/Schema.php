<?php

namespace ByJG\Swagger\Base;

use ByJG\Swagger\Exception\DefinitionNotFoundException;
use ByJG\Swagger\Exception\HttpMethodNotFoundException;
use ByJG\Swagger\Exception\InvalidDefinitionException;
use ByJG\Swagger\Exception\NotMatchedException;
use ByJG\Swagger\Exception\PathNotFoundException;
use ByJG\Swagger\OpenApiResponseBody;
use ByJG\Swagger\OpenApiSchema;
use ByJG\Swagger\SwaggerResponseBody;
use ByJG\Swagger\SwaggerSchema;
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
     * @return SwaggerResponseBody
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     * @throws DefinitionNotFoundException
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

    abstract protected function validateArguments($parameterIn, $parameters, $arguments);

    abstract public function getBasePath();

    abstract public function getDefintion($name);

    abstract public function getRequestParameters($path, $method);
}
