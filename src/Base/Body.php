<?php

namespace ByJG\ApiTools\Base;

use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericSwaggerException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;

abstract class Body
{
    const SWAGGER_PROPERTIES="properties";
    const SWAGGER_ADDITIONAL_PROPERTIES="additionalProperties";
    const SWAGGER_REQUIRED="required";

    /**
     * @var Schema
     */
    protected Schema $schema;

    /**
     * @var array
     */
    protected array $structure;

    /**
     * @var string
     */
    protected string $name;

    /**
     * OpenApi 2.0 does not describe null values, so this flag defines,
     * if match is ok when one of property, which has type, is null
     *
     * @var bool
     */
    protected bool $allowNullValues;

    /**
     * Body constructor.
     *
     * @param Schema $schema
     * @param string $name
     * @param array $structure
     * @param bool $allowNullValues
     */
    public function __construct(Schema $schema, string $name, array $structure, bool $allowNullValues = false)
    {
        $this->schema = $schema;
        $this->name = $name;
        $this->structure = $structure;
        $this->allowNullValues = $allowNullValues;
    }

    /**
     * @param mixed $body
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws RequiredArgumentNotFound
     * @return bool
     */
    abstract public function match(mixed $body): bool;

    /**
     * @param string $name
     * @param array $schemaArray
     * @param mixed $body
     * @param mixed $type
     * @return ?bool
     * @throws NotMatchedException
     */
    protected function matchString(string $name, array $schemaArray, mixed $body, mixed $type): ?bool
    {
        if ($type !== 'string') {
            return null;
        }

        if (isset($schemaArray['enum']) && !in_array($body, $schemaArray['enum'])) {
            throw new NotMatchedException("Value '$body' in '$name' not matched in ENUM. ", $this->structure);
        }

        if (isset($schemaArray['pattern'])) {
            $this->checkPattern($name, $body, $schemaArray['pattern']);
        }

        if (!is_string($body)) {
            throw new NotMatchedException("Value '" . var_export($body, true) . "' in '$name' is not string. ", $this->structure);
        }

        return true;
    }

    /**
     * @throws NotMatchedException
     */
    private function checkPattern(string $name, mixed $body, string $pattern): void
    {
        $pattern = '/' . rtrim(ltrim($pattern, '/'), '/') . '/';
        $isSuccess = (bool) preg_match($pattern, $body);

        if (!$isSuccess) {
            throw new NotMatchedException("Value '$body' in '$name' not matched in pattern. ", $this->structure);
        }
    }

    /**
     * @param string $name
     * @param array $schemaArray
     * @param mixed $body
     * @param mixed $type
     * @return bool|null
     */
    protected function matchFile(string $name, array $schemaArray, mixed $body, mixed $type): ?bool
    {
        if ($type !== 'file') {
            return null;
        }

        return true;
    }

    /**
     * @param string $name
     * @param string $body
     * @param string $type
     * @return ?bool
     * @throws NotMatchedException
     */
    protected function matchNumber(string $name, mixed $body, mixed $type): ?bool
    {
        if ($type !== 'integer' && $type !== 'float' && $type !== 'number') {
            return null;
        }

        if (!is_numeric($body)) {
            throw new NotMatchedException("Expected '$name' to be numeric, but found '$body'. ", $this->structure);
        }

        if (isset($schemaArray['pattern'])) {
            $this->checkPattern($name, $body, $schemaArray['pattern']);
        }

        return true;
    }

    /**
     * @param string $name
     * @param string $body
     * @param string $type
     * @return ?bool
     * @throws NotMatchedException
     */
    protected function matchBool(string $name, mixed $body, mixed $type): ?bool
    {
        if ($type !== 'bool' && $type !== 'boolean') {
            return null;
        }

        if (!is_bool($body)) {
            throw new NotMatchedException("Expected '$name' to be boolean, but found '$body'. ", $this->structure);
        }

        return true;
    }

    /**
     * @param string $name
     * @param array $schemaArray
     * @param string $body
     * @param string $type
     * @return ?bool
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     */
    protected function matchArray(string $name, array $schemaArray, mixed $body, mixed $type): ?bool
    {
        if ($type !== 'array') {
            return null;
        }

        foreach ((array)$body as $item) {
            if (!isset($schemaArray['items'])) {  // If there is no type , there is no test.
                continue;
            }
            $this->matchSchema($name, $schemaArray['items'], $item);
        }
        return true;
    }

    /**
     * @param string $name
     * @param mixed $schemaArray
     * @param string $body
     * @return ?bool
     */
    protected function matchTypes(string $name, mixed $schemaArray, mixed $body): ?bool
    {
        if (!isset($schemaArray['type'])) {
            return null;
        }

        $type = $schemaArray['type'];
        $nullable = isset($schemaArray['nullable']) ? (bool)$schemaArray['nullable'] : $this->schema->isAllowNullValues();

        $validators = [
            function () use ($name, $body, $type, $nullable)
            {
                return $this->matchNull($name, $body, $type, $nullable);
            },

            function () use ($name, $schemaArray, $body, $type)
            {
                return $this->matchString($name, $schemaArray, $body, $type);
            },

            function () use ($name, $body, $type)
            {
                return $this->matchNumber($name, $body, $type);
            },

            function () use ($name, $body, $type)
            {
                return $this->matchBool($name, $body, $type);
            },

            function () use ($name, $schemaArray, $body, $type)
            {
                return $this->matchArray($name, $schemaArray, $body, $type);
            },

            function () use ($name, $schemaArray, $body, $type)
            {
                return $this->matchFile($name, $schemaArray, $body, $type);
            },
        ];

        foreach ($validators as $validator) {
            $result = $validator();
            if (!is_null($result)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param array $schemaArray
     * @param string $body
     * @return bool|null
     * @throws DefinitionNotFoundException
     * @throws GenericSwaggerException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     */
    public function matchObjectProperties(string $name, mixed $schemaArray, mixed $body): ?bool
    {
        if (isset($schemaArray[self::SWAGGER_ADDITIONAL_PROPERTIES]) && !isset($schemaArray[self::SWAGGER_PROPERTIES])) {
            $schemaArray[self::SWAGGER_PROPERTIES] = [];
        }

        if (!isset($schemaArray[self::SWAGGER_PROPERTIES])) {
            return null;
        }

        if (!is_array($body)) {
            throw new InvalidRequestException(
                "I expected an array here, but I got an string. Maybe you did wrong request?",
                $body
            );
        }

        if (!isset($schemaArray[self::SWAGGER_REQUIRED])) {
            $schemaArray[self::SWAGGER_REQUIRED] = [];
        }
        foreach ($schemaArray[self::SWAGGER_PROPERTIES] as $prop => $def) {
            $required = array_search($prop, $schemaArray[self::SWAGGER_REQUIRED]);

            if (!array_key_exists($prop, $body)) {
                if ($required !== false) {
                    throw new NotMatchedException("Required property '$prop' in '$name' not found in object");
                }
                unset($body[$prop]);
                continue;
            }

            $this->matchSchema($prop, $def, $body[$prop]);
            unset($schemaArray[self::SWAGGER_PROPERTIES][$prop]);
            if ($required !== false) {
                unset($schemaArray[self::SWAGGER_REQUIRED][$required]);
            }
            unset($body[$prop]);
        }

        if (count($schemaArray[self::SWAGGER_REQUIRED]) > 0) {
            throw new NotMatchedException(
                "The required property(ies) '"
                . implode(', ', $schemaArray[self::SWAGGER_REQUIRED])
                . "' does not exists in the body.",
                $this->structure
            );
        }

        if (count($body) > 0 && !isset($schemaArray[self::SWAGGER_ADDITIONAL_PROPERTIES])) {
            throw new NotMatchedException(
                "The property(ies) '"
                . implode(', ', array_keys($body))
                . "' has not defined in '$name'",
                $body
            );
        }

        foreach ($body as $name => $prop) {
            $def = $schemaArray[self::SWAGGER_ADDITIONAL_PROPERTIES];
            $this->matchSchema($name, $def, $prop);
        }
        return true;
    }

    /**
     * @param string $name
     * @param array $schemaArray
     * @param array $body
     * @return ?bool
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     * @throws GenericSwaggerException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     */
    protected function matchSchema(string $name, mixed $schemaArray, mixed $body): ?bool
    {
        // Match Single Types
        if ($this->matchTypes($name, $schemaArray, $body)) {
            return true;
        }

        if(!isset($schemaArray['$ref']) && isset($schemaArray['content'])) {
            $schemaArray = $schemaArray['content'][key($schemaArray['content'])]['schema'];
        }

        // Get References and try to match it again
        if (isset($schemaArray['$ref']) && !is_array($schemaArray['$ref'])) {
            $defintion = $this->schema->getDefinition($schemaArray['$ref']);
            return $this->matchSchema($schemaArray['$ref'], $defintion, $body);
        }

        // Match object properties
        if ($this->matchObjectProperties($name, $schemaArray, $body)) {
            return true;
        }

        if (isset($schemaArray['allOf'])) {
            $allOfSchemas = $schemaArray['allOf'];
            foreach ($allOfSchemas as &$schema) {
                if (isset($schema['$ref'])) {
                    $schema = $this->schema->getDefinition($schema['$ref']);
                }
            }
            unset($schema);
            $mergedSchema = array_merge_recursive(...$allOfSchemas);
            return $this->matchSchema($name, $mergedSchema, $body);
        }

        if (isset($schemaArray['oneOf'])) {
            $matched = false;
            $catchedException = null;
            foreach ($schemaArray['oneOf'] as $schema) {
                try {
                    $matched = $matched || $this->matchSchema($name, $schema, $body);
                } catch (NotMatchedException $exception) {
                    $catchedException = $exception;
                }
            }
            if ($catchedException !== null && $matched === false) {
                throw $catchedException;
            }

            return $matched;
        }

        /**
         * OpenApi 2.0 does not describe ANY object value
         * But there is hack that makes ANY object possible, described in link below
         * To make that hack works, we need such condition
         * @link https://stackoverflow.com/questions/32841298/swagger-2-0-what-schema-to-accept-any-complex-json-value
         */
        if ($schemaArray === []) {
            return true;
        }

        throw new GenericSwaggerException("Not all cases are defined. Please open an issue about this. Schema: $name");
    }

    /**
     * @param string $name
     * @param string $body
     * @param string $type
     * @param bool $nullable
     * @return ?bool
     * @throws NotMatchedException
     */
    protected function matchNull(string $name, mixed $body, mixed $type, bool $nullable): ?bool
    {
        if (!is_null($body)) {
            return null;
        }

        if (!$nullable) {
            throw new NotMatchedException(
                "Value of property '$name' is null, but should be of type '$type'",
                $this->structure
            );
        }

        return true;
    }
}
