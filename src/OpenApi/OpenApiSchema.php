<?php

namespace ByJG\ApiTools\OpenApi;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\Util\Uri;
use InvalidArgumentException;

class OpenApiSchema extends Schema
{

    protected $serverVariables = [];

    /**
     * Initialize with schema data, which can be a PHP array or encoded as JSON.
     *
     * @param array|string $data
     */
    public function __construct($data)
    {
        // when given a string, decode from JSON
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        // make sure we got an array
        if (!is_array($data)) {
            throw new InvalidArgumentException('schema must be given as array or JSON string');
        }
        $this->jsonFile = $data;
    }

    public function getServerUrl()
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
            $serverUrl = preg_replace("/\{$var\}/", $value, $serverUrl);
        }

        return $serverUrl;
    }

    public function getBasePath()
    {
        $uriServer = new Uri($this->getServerUrl());
        return $uriServer->getPath();
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
            if (isset($parameter['$ref'])) {
                $paramParts = explode("/", $parameter['$ref']);
                if (count($paramParts) != 4 || $paramParts[0] != "#" || $paramParts[1] != self::SWAGGER_COMPONENTS || $paramParts[2] != self::SWAGGER_PARAMETERS) {
                    throw new InvalidDefinitionException(
                        "Not get the reference in the expected format #/components/parameters/<NAME>"
                    );
                }
                if (!isset($this->jsonFile[self::SWAGGER_COMPONENTS][self::SWAGGER_PARAMETERS][$paramParts[3]])) {
                    throw new DefinitionNotFoundException(
                        "Not find reference #/components/parameters/{$paramParts[3]}"
                    );
                }
                $parameter = $this->jsonFile[self::SWAGGER_COMPONENTS][self::SWAGGER_PARAMETERS][$paramParts[3]];
            }
            if ($parameter['in'] === $parameterIn &&
                $parameter['schema']['type'] === "integer"
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
    public function getDefinition($name)
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
     * @param $path
     * @param $method
     * @return OpenApiRequestBody
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function getRequestParameters($path, $method)
    {
        $structure = $this->getPathDefinition($path, $method);

        if (!isset($structure['requestBody'])) {
            return new OpenApiRequestBody($this, "$method $path", []);
        }
        return new OpenApiRequestBody($this, "$method $path", $structure['requestBody']);
    }

    public function setServerVariable($var, $value)
    {
        $this->serverVariables[$var] = $value;
    }
}
