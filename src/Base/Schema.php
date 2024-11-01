<?php

namespace ByJG\ApiTools\Base;

use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\ApiTools\OpenApi\OpenApiSchema;
use ByJG\ApiTools\Swagger\SwaggerSchema;
use ByJG\Util\Uri;
use InvalidArgumentException;

abstract class Schema
{
    protected array $jsonFile;
    protected bool $allowNullValues = false;
    protected string $specificationVersion;

    const SWAGGER_PATHS = "paths";
    const SWAGGER_PARAMETERS = "parameters";
    const SWAGGER_COMPONENTS = "components";

    /**
     * Returns the major specification version
     * @return string
     */
    public function getSpecificationVersion(): string
    {
        return $this->specificationVersion;
    }

    /**
     * Factory function for schemata.
     *
     * Initialize with schema data, which can be a PHP array or encoded as JSON.
     * This determines the type of the schema from the given data.
     *
     * @param array|string $data
     * @param bool $extraArgs
     * @return Schema
     */
    public static function getInstance(array|string $data, bool $extraArgs = false): Schema
    {
        // when given a string, decode from JSON
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        // check which type of file we got and dispatch to derived class constructor
        if (isset($data['swagger'])) {
            return new SwaggerSchema($data, $extraArgs);
        }
        if (isset($data['openapi'])) {
            return new OpenApiSchema($data);
        }

        throw new InvalidArgumentException('failed to determine schema type from data');
    }

    /**
     * @param string $path
     * @param string $method
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function getPathDefinition(string $path, string $method): mixed
    {
        $method = strtolower($method);

        $path = preg_replace('~^' . $this->getBasePath() . '~', '', $path);

        $uri = new Uri($path);

        // Try direct match
        if (isset($this->jsonFile[self::SWAGGER_PATHS][$uri->getPath()])) {
            if (isset($this->jsonFile[self::SWAGGER_PATHS][$uri->getPath()][$method])) {

                parse_str($uri->getQuery(), $matches);
                $this->prepareToValidateArguments($uri->getPath(), $method, 'query', $matches);

                return $this->jsonFile[self::SWAGGER_PATHS][$uri->getPath()][$method];
            }
            throw new HttpMethodNotFoundException("The http method '$method' not found in '$path'");
        }

        // Try inline parameter
        foreach (array_keys($this->jsonFile[self::SWAGGER_PATHS]) as $pathItem) {
            if (!str_contains($pathItem, '{')) {
                continue;
            }

            $pathItemPattern = '~^' . preg_replace('~{(.*?)}~', '(?<\1>[^/]+)', $pathItem) . '$~';

            $matches = [];
            if (empty($uri->getPath())) {
                throw new InvalidRequestException('The path is empty');
            }
            if (preg_match($pathItemPattern, $uri->getPath(), $matches)) {
                $pathDef = $this->jsonFile[self::SWAGGER_PATHS][$pathItem];
                if (!isset($pathDef[$method])) {
                    throw new HttpMethodNotFoundException("The http method '$method' not found in '$path'");
                }

                $this->prepareToValidateArguments($pathItem, $method, 'path', $matches);
                parse_str($uri->getQuery(), $queryParsed);
                $this->prepareToValidateArguments($pathItem, $method, 'query', $queryParsed);

                return $pathDef[$method];
            }
        }

        throw new PathNotFoundException('Path "' . $path . '" not found');
    }

    protected function prepareToValidateArguments(string $path, string $method, string $parameterIn, $matches): void
    {
        $pathDef = $this->jsonFile[self::SWAGGER_PATHS][$path];

        $parametersPathMethod = [];
        $parametersPath = [];

        if (isset($pathDef[$method][self::SWAGGER_PARAMETERS])) {
            $parametersPathMethod = $pathDef[$method][self::SWAGGER_PARAMETERS];
        }

        if (isset($pathDef[self::SWAGGER_PARAMETERS])) {
            $parametersPath = $pathDef[self::SWAGGER_PARAMETERS];
        }

        $this->validateArguments($parameterIn, array_merge($parametersPathMethod, $parametersPath), $matches);
    }

    /**
     * @param string $path
     * @param string $method
     * @param int $status
     * @return Body
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function getResponseParameters(string $path, string $method, int $status): Body
    {
        $structure = $this->getPathDefinition($path, $method);

        if (!isset($structure['responses']["200"])) {
            $structure['responses']["200"] = ["description" => "Auto Generated OK"];
        }

        $verifyStatus = $status;
        if (!isset($structure['responses'][$verifyStatus])) {
            $verifyStatus = 'default';
            if (!isset($structure['responses'][$verifyStatus])) {
                throw new InvalidDefinitionException("Could not found status code '$status' in '$path' and '$method'");
            }
        }

        return $this->getResponseBody($this, "$method $status $path", $structure['responses'][$verifyStatus]);
    }

    /**
     * OpenApi 2.0 doesn't describe null values, so this flag defines,
     * if match is ok when one of property
     *
     * @return bool
     */
    public function isAllowNullValues(): bool
    {
        return $this->allowNullValues;
    }

    /**
     * @return string
     */
    abstract public function getServerUrl(): string;

    /**
     * @param string $parameterIn
     * @param array $parameters
     * @param array $arguments
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     */
    abstract protected function validateArguments(string $parameterIn, array $parameters, array $arguments): void;

    abstract public function getBasePath(): string;

    /**
     * @param $name
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     */
    abstract public function getDefinition($name): mixed;

    /**
     * @param string $path
     * @param string $method
     * @return Body
     * @throws HttpMethodNotFoundException
     * @throws PathNotFoundException
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    abstract public function getRequestParameters(string $path, string $method): Body;

    /**
     * @param Schema $schema
     * @param string $name
     * @param array $structure
     * @param bool $allowNullValues
     * @return Body
     */
    abstract public function getResponseBody(Schema $schema, string $name, array $structure, bool $allowNullValues = false): Body;
}
