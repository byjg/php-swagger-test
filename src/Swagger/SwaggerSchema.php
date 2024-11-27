<?php

namespace ByJG\ApiTools\Swagger;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Base\Parameter;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;

class SwaggerSchema extends Schema
{
    /**
     * Initialize with schema data, which can be a PHP array or encoded as JSON.
     *
     * @param array|string $data
     * @param bool $allowNullValues
     */
    public function __construct(array|string $data, bool $allowNullValues = false)
    {
        // when given a string, decode from JSON
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $this->jsonFile = $data;
        $this->allowNullValues = $allowNullValues;
    }

    public function getHttpSchema()
    {
        return isset($this->jsonFile['schemes']) ? $this->jsonFile['schemes'][0] : '';
    }

    public function getHost()
    {
        return $this->jsonFile['host'] ?? '';
    }

    public function getBasePath(): string
    {
        return $this->jsonFile['basePath'] ?? '';
    }

    public function getServerUrl(): string
    {
        $httpSchema = $this->getHttpSchema();
        if (!empty($httpSchema)) {
            $httpSchema .= "://";
        }
        $host = $this->getHost();
        $basePath = $this->getBasePath();
        return "$httpSchema$host$basePath";
    }

    /**
     * @inheritDoc
     */
    protected function validateArguments(string $parameterIn, array $parameters, array $arguments): void
    {
        foreach ($parameters as $parameter) {
            if ($parameter['in'] === $parameterIn) {
                $parameterMatch = new Parameter($this, $parameter['name'], $parameter ?? [], !($parameter["required"] ?? false));
                $parameterMatch->match($arguments[$parameter['name']] ?? null);
            }
        }
    }

    /**
     * @param $name
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     */
    public function getDefinition($name): mixed
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
     * @inheritDoc
     * @throws InvalidRequestException
     */
    public function getRequestParameters(string $path, string $method): Body
    {
        $structure = $this->parsePathRequest($path, $method, true);

        if (!isset($structure[self::SWAGGER_PARAMETERS])) {
            return new SwaggerRequestBody($this, "$method $path", []);
        }
        return new SwaggerRequestBody($this, "$method $path", $structure[self::SWAGGER_PARAMETERS]);
    }

    /**
     * OpenApi 2.0 doesn't describe null values, so this flag defines,
     * if match is ok when one of property
     *
     * @param string|bool $value
     */
    public function setAllowNullValues(string|bool $value): void
    {
        $this->allowNullValues = (bool) $value;
    }

    /**
     * @inheritDoc
     */
    public function getResponseBody(Schema $schema, string $name, array $structure, bool $allowNullValues = false): Body
    {
        return new SwaggerResponseBody($schema, $name, $structure, $allowNullValues);
    }
}
