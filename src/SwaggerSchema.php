<?php
/**
 * User: jg
 * Date: 22/05/17
 * Time: 09:29
 */

namespace ByJG\Swagger;

use ByJG\Swagger\Exception\DefinitionNotFoundException;
use ByJG\Swagger\Exception\HttpMethodNotFoundException;
use ByJG\Swagger\Exception\InvalidDefinitionException;
use ByJG\Swagger\Exception\NotMatchedException;
use ByJG\Swagger\Exception\PathNotFoundException;

class SwaggerSchema
{
    protected $jsonFile;
    protected $allowNullValues;

    public function __construct($jsonFile, $allowNullValues = false)
    {
        $this->jsonFile = json_decode($jsonFile, true);
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
     * @param $path
     * @param $method
     * @return mixed
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function getPathDefinition($path, $method)
    {
        $method = strtolower($method);

        $path = preg_replace('~^' . $this->getBasePath() . '~', '', $path);

        // Try direct match
        if (isset($this->jsonFile['paths'][$path])) {
            if (isset($this->jsonFile['paths'][$path][$method])) {
                return $this->jsonFile['paths'][$path][$method];
            }
            throw new HttpMethodNotFoundException("The http method '$method' not found in '$path'");
        }

        // Try inline parameter
        foreach (array_keys($this->jsonFile['paths']) as $pathItem) {
            if (strpos($pathItem, '{') === false) {
                continue;
            }

            $pathItemPattern = '~^' . preg_replace('~\{(.*?)\}~', '(?<\1>[^/]+)', $pathItem) . '$~';

            $matches = [];
            if (preg_match($pathItemPattern, $path, $matches)) {
                $pathDef = $this->jsonFile['paths'][$pathItem];
                if (!isset($pathDef[$method])) {
                    throw new HttpMethodNotFoundException("The http method '$method' not found in '$path'");
                }

                $this->validateArguments('path', $pathDef[$method]['parameters'], $matches);

                return $pathDef[$method];
            }
        }

        throw new PathNotFoundException('Path "' . $path . '" not found');
    }

    /**
     * @param $parameterIn
     * @param $parameters
     * @param $arguments
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     */
    private function validateArguments($parameterIn, $parameters, $arguments)
    {
        foreach ($parameters as $parameter) {
            if ($parameter['in'] === $parameterIn) {
                if ($parameter['type'] === "integer"
                    && filter_var($arguments[$parameter['name']], FILTER_VALIDATE_INT) === false) {
                    throw new NotMatchedException('Path expected an integer value');
                }
            }
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws \ByJG\Swagger\Exception\DefinitionNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     */
    public function getDefintion($name)
    {
        $nameParts = explode('/', $name);

        if (count($nameParts) < 3 || $nameParts[0] != '#') {
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
     * @return \ByJG\Swagger\SwaggerRequestBody
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function getRequestParameters($path, $method)
    {
        $structure = $this->getPathDefinition($path, $method);

        if (!isset($structure['parameters'])) {
            return new SwaggerRequestBody($this, "$method $path", []);
        }

        return new SwaggerRequestBody($this, "$method $path", $structure['parameters']);
    }

    /**
     * @param $path
     * @param $method
     * @param $status
     * @return \ByJG\Swagger\SwaggerResponseBody
     * @throws \ByJG\Swagger\Exception\HttpMethodNotFoundException
     * @throws \ByJG\Swagger\Exception\InvalidDefinitionException
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function getResponseParameters($path, $method, $status)
    {
        $structure = $this->getPathDefinition($path, $method);

        if (!isset($structure['responses'][$status])) {
            throw new InvalidDefinitionException("Could not found status code '$status' in '$path' and '$method'");
        }

        return new SwaggerResponseBody($this, "$method $status $path", $structure['responses'][$status]);
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
     * OpenApi 2.0 doesn't describe null values, so this flag defines,
     * if match is ok when one of property
     *
     * @param $value
     */
    public function setAllowNullValues($value)
    {
        $this->allowNullValues = (bool) $value;
    }
}
