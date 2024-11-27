<?php

namespace ByJG\ApiTools\OpenApi;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Base\Parameter;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\Util\Uri;

class OpenApiSchema extends Schema
{

    protected array $serverVariables = [];

    /**
     * Initialize with schema data, which can be a PHP array or encoded as JSON.
     *
     * @param array|string $data
     */
    public function __construct(array|string $data)
    {
        // when given a string, decode from JSON
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $this->jsonFile = $data;
    }

    public function getServerUrl(): string
    {
        if (!isset($this->jsonFile['servers'])) {
            return '';
        }
        $serverUrl = $this->jsonFile['servers'][0]['url'];

        if (isset($this->jsonFile['servers'][0]['variables'])) {
            foreach ($this->jsonFile['servers'][0]['variables'] as $var => $value) {
                if (!isset($this->serverVariables[$var])) {
                    $this->serverVariables[$var] = $value['default'];
                }
            }
        }

        foreach ($this->serverVariables as $var => $value) {
            $serverUrl = (string)preg_replace("/\{$var}/", $value, $serverUrl);
        }

        return $serverUrl;
    }

    public function getBasePath(): string
    {
        $uriServer = new Uri($this->getServerUrl());
        return $uriServer->getPath();
    }

    /**
     * @inheritDoc
     */
    protected function validateArguments(string $parameterIn, array $parameters, array $arguments): void
    {
        foreach ($parameters as $parameter) {
            if (isset($parameter['$ref'])) {
                $paramParts = explode("/", $parameter['$ref']);
                if (count($paramParts) != 4 || $paramParts[0] != "#" || $paramParts[1] != self::SWAGGER_COMPONENTS || $paramParts[2] != self::SWAGGER_PARAMETERS) {
                    throw new InvalidDefinitionException(
                        "Not get the reference in the expected format #/components/parameters/<NAME>"
                    );
                }
                if (!isset($this->jsonFile[self::SWAGGER_COMPONENTS][self::SWAGGER_PARAMETERS][$paramParts[3]])) {
                    throw new DefinitionNotFoundException(
                        "Not find reference #/components/parameters/$paramParts[3]"
                    );
                }
                $parameter = $this->jsonFile[self::SWAGGER_COMPONENTS][self::SWAGGER_PARAMETERS][$paramParts[3]];
            }
            if ($parameter['in'] === $parameterIn) {
                $parameterMatch = new Parameter($this, $parameter['name'], $parameter["schema"] ?? [], !($parameter["required"] ?? false));
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

        if (count($nameParts) < 4 || $nameParts[0] !== '#') {
            throw new InvalidDefinitionException('Invalid Component');
        }

        if (!isset($this->jsonFile[$nameParts[1]][$nameParts[2]][$nameParts[3]])) {
            throw new DefinitionNotFoundException("Component'$name' not found");
        }

        return $this->jsonFile[$nameParts[1]][$nameParts[2]][$nameParts[3]];
    }

    /**
     * @inheritDoc
     * @throws InvalidRequestException
     */
    public function getRequestParameters(string $path, string $method): Body
    {
        $structure = $this->getPathDefinition($path, $method);

        if (!isset($structure['requestBody'])) {
            return new OpenApiRequestBody($this, "$method $path", []);
        }
        return new OpenApiRequestBody($this, "$method $path", $structure['requestBody']);
    }

    public function setServerVariable(string $var, string $value): void
    {
        $this->serverVariables[$var] = $value;
    }

    /**
     * @inheritDoc
     */
    public function getResponseBody(Schema $schema, string $name, array $structure, bool $allowNullValues = false): Body
    {
        return new OpenApiResponseBody($schema, $name, $structure, $allowNullValues);
    }
}
