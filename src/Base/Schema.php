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
     * Create schema from JSON string.
     *
     * @param string $jsonString JSON-encoded OpenAPI/Swagger specification
     * @param bool $allowNullValues Whether to allow null values (Swagger 2.0 only)
     * @return SwaggerSchema|OpenApiSchema
     * @throws InvalidArgumentException
     */
    public static function fromJson(string $jsonString, bool $allowNullValues = false): SwaggerSchema|OpenApiSchema
    {
        $data = json_decode($jsonString, true);
        if ($data === null) {
            throw new InvalidArgumentException('Invalid JSON provided to fromJson()');
        }
        return self::fromArray($data, $allowNullValues);
    }

    /**
     * Create schema from file path.
     *
     * @param string $filePath Path to JSON file containing OpenAPI/Swagger specification
     * @param bool $allowNullValues Whether to allow null values (Swagger 2.0 only)
     * @return SwaggerSchema|OpenApiSchema
     * @throws InvalidArgumentException
     */
    public static function fromFile(string $filePath, bool $allowNullValues = false): SwaggerSchema|OpenApiSchema
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: $filePath");
        }
        $jsonString = file_get_contents($filePath);
        if ($jsonString === false) {
            throw new InvalidArgumentException("Failed to read file: $filePath");
        }
        return self::fromJson($jsonString, $allowNullValues);
    }

    /**
     * Create schema from array.
     *
     * @param array $data PHP array containing OpenAPI/Swagger specification
     * @param bool $allowNullValues Whether to allow null values (Swagger 2.0 only)
     * @return SwaggerSchema|OpenApiSchema
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data, bool $allowNullValues = false): SwaggerSchema|OpenApiSchema
    {
        // check which type of schema we have and dispatch to derived class constructor
        if (isset($data['swagger'])) {
            return new SwaggerSchema($data, $allowNullValues);
        }
        if (isset($data['openapi'])) {
            return new OpenApiSchema($data);
        }

        throw new InvalidArgumentException('Failed to determine schema type from data. Expected "swagger" or "openapi" property.');
    }

    /**
     * Factory function for schemata.
     *
     * Initialize with schema data, which can be a PHP array or encoded as JSON.
     * This determines the type of the schema from the given data.
     *
     * @param array|string $data
     * @param bool $extraArgs
     * @return SwaggerSchema|OpenApiSchema
     * @deprecated Since version 6.0, use fromJson(), fromArray(), or fromFile() instead. Will be removed in version 7.0
     */
    public static function getInstance(array|string $data, bool $extraArgs = false): SwaggerSchema|OpenApiSchema
    {
        // when given a string, decode from JSON
        if (is_string($data)) {
            return self::fromJson($data, $extraArgs);
        }
        return self::fromArray($data, $extraArgs);
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
                return $this->jsonFile[self::SWAGGER_PATHS][$uri->getPath()][$method];
            }
            throw new HttpMethodNotFoundException("The http method '$method' not found in '$path'");
        }

        // Try inline parameter
        /**
         * @var string $pathItem
         */
        foreach (array_keys($this->jsonFile[self::SWAGGER_PATHS]) as $pathItem) {
            if (!str_contains($pathItem, '{')) {
                continue;
            }

            $pathItemReplaced = preg_replace('~{(.*?)}~', '(?<\1>[^/]+)', $pathItem);
            $pathItemPattern = '~^' . (is_string($pathItemReplaced) ? $pathItemReplaced : $pathItem) . '$~';

            $matches = [];
            if (empty($uri->getPath())) {
                throw new InvalidRequestException('The path is empty');
            }
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
     * @param string $name
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     */
    abstract public function getDefinition(string $name): mixed;

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
