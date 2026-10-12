<?php

namespace ByJG\ApiTools\Base;

use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericApiException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;
use SimpleXMLElement;

abstract class Body
{
    const SWAGGER_OBJECT = "object";
    const SWAGGER_ARRAY = "array";
    const SWAGGER_PROPERTIES = "properties";
    const SWAGGER_ADDITIONAL_PROPERTIES = "additionalProperties";
    const SWAGGER_REQUIRED = "required";

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
     * @return bool
     *@throws GenericApiException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws RequiredArgumentNotFound
     * @throws DefinitionNotFoundException
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

        $this->checkLength($name, $body, $schemaArray);

        return true;
    }

    /**
     * Enforce minLength/maxLength. Length is counted in characters, not bytes.
     *
     * @throws NotMatchedException
     */
    private function checkLength(string $name, string $body, array $schemaArray): void
    {
        if (!isset($schemaArray['minLength']) && !isset($schemaArray['maxLength'])) {
            return;
        }

        // PCRE counts code points without requiring ext-mbstring; invalid UTF-8 falls back to bytes.
        $length = preg_match_all('/./su', $body);
        if ($length === false) {
            $length = strlen($body);
        }

        if (isset($schemaArray['minLength']) && $length < $schemaArray['minLength']) {
            throw new NotMatchedException("Value '$body' in '$name' is shorter than minLength {$schemaArray['minLength']}. ", $this->structure);
        }

        if (isset($schemaArray['maxLength']) && $length > $schemaArray['maxLength']) {
            throw new NotMatchedException("Value '$body' in '$name' is longer than maxLength {$schemaArray['maxLength']}. ", $this->structure);
        }
    }

    /**
     * @param numeric $body
     *@throws NotMatchedException
     *
     */
    private function checkPattern(string $name, mixed $body, string $pattern): void
    {
        $pattern = '/' . rtrim(ltrim($pattern, '/'), '/') . '/';
        $isSuccess = (bool)preg_match($pattern, (string)$body);

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
     * @param array $schemaArray
     * @param mixed $body
     * @param mixed $type
     * @return ?bool
     * @throws NotMatchedException
     */
    protected function matchNumber(string $name, array $schemaArray, mixed $body, mixed $type): ?bool
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

        if (isset($schemaArray['minimum']) && $body < $schemaArray['minimum']) {
            throw new NotMatchedException("Value '$body' in '$name' is less than minimum {$schemaArray['minimum']}. ", $this->structure);
        }

        if (isset($schemaArray['maximum']) && $body > $schemaArray['maximum']) {
            throw new NotMatchedException("Value '$body' in '$name' is greater than maximum {$schemaArray['maximum']}. ", $this->structure);
        }

        return true;
    }

    /**
     * @param string $name
     * @param mixed $body
     * @param mixed $type
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
     * @param mixed $body
     * @param mixed $type
     * @return ?bool
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     */
    protected function matchArray(string $name, array $schemaArray, mixed $body, mixed $type): ?bool
    {
        if ($type !== self::SWAGGER_ARRAY) {
            return null;
        }

        // NEW: Handle prefixItems (JSON Schema 2020-12 tuple validation)
        if (isset($schemaArray['prefixItems'])) {
            $bodyArray = (array)$body;

            // Validate each item against its corresponding schema
            foreach ($schemaArray['prefixItems'] as $index => $itemSchema) {
                if (!isset($bodyArray[$index])) {
                    // Check minItems if needed
                    if (isset($schemaArray['minItems']) && $index < $schemaArray['minItems']) {
                        throw new NotMatchedException(
                            "Array '$name' requires at least " . $schemaArray['minItems'] . " items",
                            $this->structure
                        );
                    }
                    break;
                }
                $this->matchSchema($name . "[$index]", $itemSchema, $bodyArray[$index]);
            }

            // Check maxItems
            if (isset($schemaArray['maxItems']) && count($bodyArray) > $schemaArray['maxItems']) {
                throw new NotMatchedException(
                    "Array '$name' has more than " . $schemaArray['maxItems'] . " items",
                    $this->structure
                );
            }

            // Check minItems
            if (isset($schemaArray['minItems']) && count($bodyArray) < $schemaArray['minItems']) {
                throw new NotMatchedException(
                    "Array '$name' requires at least " . $schemaArray['minItems'] . " items",
                    $this->structure
                );
            }

            return true;
        }

        // Original items handling for non-tuple arrays
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
     * @param mixed $body
     * @return ?bool
     */
    protected function matchTypes(string $name, mixed $schemaArray, mixed $body): ?bool
    {
        // NEW: Support 'const' keyword (JSON Schema 2020-12)
        if (isset($schemaArray['const'])) {
            if ($body !== $schemaArray['const']) {
                throw new NotMatchedException(
                    "Value '" . var_export($body, true) . "' in '$name' does not match const value '" . var_export($schemaArray['const'], true) . "'",
                    $this->structure
                );
            }
            return true;
        }

        // NEW: Support 'pattern' keyword without type (JSON Schema allows this)
        if (isset($schemaArray['pattern']) && !isset($schemaArray['type'])) {
            if (!is_string($body)) {
                throw new NotMatchedException("Value '" . var_export($body, true) . "' in '$name' must be a string to match pattern. ", $this->structure);
            }
            $pattern = '/' . rtrim(ltrim($schemaArray['pattern'], '/'), '/') . '/';
            if (!preg_match($pattern, $body)) {
                throw new NotMatchedException("Value '$body' in '$name' not matched in pattern. ", $this->structure);
            }
            return true;
        }

        if (!isset($schemaArray['type'])) {
            return null;
        }

        $type = $schemaArray['type'];
        $nullable = isset($schemaArray['nullable']) ? (bool)$schemaArray['nullable'] : $this->schema->isAllowNullValues();

        $validators = [
            function () use ($name, $body, $type, $nullable): bool|null
            {
                return $this->matchNull($name, $body, $type, $nullable);
            },

            function () use ($name, $schemaArray, $body, $type): bool|null
            {
                return $this->matchString($name, $schemaArray, $body, $type);
            },

            function () use ($name, $schemaArray, $body, $type): bool|null
            {
                return $this->matchNumber($name, $schemaArray, $body, $type);
            },

            function () use ($name, $body, $type): bool|null
            {
                return $this->matchBool($name, $body, $type);
            },

            function () use ($name, $schemaArray, $body, $type): bool|null
            {
                return $this->matchArray($name, $schemaArray, $body, $type);
            },

            function () use ($name, $schemaArray, $body, $type): bool|null
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
     * Handle conditional schemas (if/then/else) - JSON Schema 2020-12
     *
     * @param string $name
     * @param array $schemaArray
     * @param mixed $body
     * @return ?bool
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     */
    protected function matchConditional(string $name, array $schemaArray, mixed $body): ?bool
    {
        if (!isset($schemaArray['if'])) {
            return null;
        }

        // Test if condition - for "if", we need to allow additional properties
        // because we're only testing if certain properties match, not the whole schema
        $ifMatches = false;
        try {
            $ifSchema = $schemaArray['if'];
            // Make sure additional properties are allowed for the "if" test
            if (isset($ifSchema['properties']) && !isset($ifSchema['additionalProperties'])) {
                $ifSchema['additionalProperties'] = true;
            }
            $ifMatches = $this->matchSchema($name . '[if]', $ifSchema, $body) ?? false;
        } catch (NotMatchedException $e) {
            // If doesn't match, that's okay
            $ifMatches = false;
        }

        // Apply then or else - these add constraints ON TOP of base schema
        if ($ifMatches && isset($schemaArray['then'])) {
            // For "then", also allow additional properties since we're adding constraints
            $thenSchema = $schemaArray['then'];
            if (isset($thenSchema['properties']) && !isset($thenSchema['additionalProperties'])) {
                $thenSchema['additionalProperties'] = true;
            }
            // Validate against the then branch (this adds constraints)
            $this->matchSchema($name . '[then]', $thenSchema, $body);
            // Return true to indicate we processed conditional (but base validation continues)
            return true;
        } elseif (!$ifMatches && isset($schemaArray['else'])) {
            // For "else", also allow additional properties
            $elseSchema = $schemaArray['else'];
            if (isset($elseSchema['properties']) && !isset($elseSchema['additionalProperties'])) {
                $elseSchema['additionalProperties'] = true;
            }
            // Validate against the else branch (this adds constraints)
            $this->matchSchema($name . '[else]', $elseSchema, $body);
            // Return true to indicate we processed conditional (but base validation continues)
            return true;
        }

        return null;
    }

    /**
     * Check if type is an array (for OpenAPI 3.1 nullable support)
     * Handles type: ["string", "null"] syntax
     *
     * @param string $name
     * @param array $schemaArray
     * @param mixed $body
     * @return ?bool
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     */
    protected function matchTypeArray(string $name, array $schemaArray, mixed $body): ?bool
    {
        if (!isset($schemaArray['type']) || !is_array($schemaArray['type'])) {
            return null;
        }

        // OpenAPI 3.1: type can be an array like ["string", "null"]
        $types = $schemaArray['type'];

        // Check if null is allowed
        $isNullable = in_array('null', $types);

        // If body is null
        if (is_null($body)) {
            if ($isNullable) {
                return true;
            }
            throw new NotMatchedException(
                "Value of property '$name' is null, but null is not in allowed types: " . implode(', ', $types),
                $this->structure
            );
        }

        // Try to match against each type (excluding 'null')
        $nonNullTypes = array_filter($types, fn($t) => $t !== 'null');
        $matched = false;
        $lastException = null;

        foreach ($nonNullTypes as $type) {
            $tempSchema = $schemaArray;
            $tempSchema['type'] = $type;

            try {
                // Try to match with this type
                $typeMatched = $this->matchTypes($name, $tempSchema, $body);

                // For object types, matchTypes returns null, so we need to also check object properties
                if ($typeMatched === null && $type === self::SWAGGER_OBJECT) {
                    $typeMatched = $this->matchObjectProperties($name, $tempSchema, $body);
                }

                if ($typeMatched) {
                    $matched = true;
                    break;
                }
            } catch (NotMatchedException $e) {
                $lastException = $e;
                continue;
            }
        }

        if (!$matched && $lastException !== null) {
            throw $lastException;
        }

        return $matched;
    }

    /**
     * @param string $name
     * @param array $schemaArray
     * @param mixed $body
     * @return bool|null
     * @throws DefinitionNotFoundException
     * @throws GenericApiException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     */
    public function matchObjectProperties(string $name, mixed $schemaArray, mixed $body): ?bool
    {
//        if (!in_array($schemaArray["type"] ?? '',  [self::SWAGGER_OBJECT, self::SWAGGER_ARRAY])) {
//            return null;
//        }

        if (!isset($schemaArray[self::SWAGGER_PROPERTIES])) {
            // If type is object/array OR if there's a required constraint, treat as object
            if (in_array($schemaArray["type"] ?? '', [self::SWAGGER_OBJECT, self::SWAGGER_ARRAY]) || isset($schemaArray[self::SWAGGER_REQUIRED])) {
                $schemaArray[self::SWAGGER_PROPERTIES] = [];
            } else {
                return null;
            }
        }

        if (empty($schemaArray[self::SWAGGER_PROPERTIES]) && !isset($schemaArray[self::SWAGGER_ADDITIONAL_PROPERTIES])) {
            $schemaArray[self::SWAGGER_ADDITIONAL_PROPERTIES] = true;
        }

        if ($body instanceof SimpleXMLElement) {
            $encoded = json_encode($body);
            $body = json_decode($encoded !== false ? $encoded : '{}', true);
        }

        // Handle null values when nullable is allowed
        if (is_null($body)) {
            $nullable = isset($schemaArray['nullable']) ? (bool)$schemaArray['nullable'] : $this->schema->isAllowNullValues();
            if ($nullable) {
                return true;
            }
            throw new NotMatchedException(
                "Value of property '$name' is null, but should be an object",
                $this->structure
            );
        }

        if (!is_array($body)) {
            throw new NotMatchedException(
                "The body '" . $body . "' cannot be compared with the expected type " . $name,
                $this->structure
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

        // NEW: If there are required fields but no properties were defined (e.g., in conditional then/else),
        // check if the required fields exist in the body without validating them
        if (empty($schemaArray[self::SWAGGER_PROPERTIES]) && !empty($schemaArray[self::SWAGGER_REQUIRED])) {
            foreach ($schemaArray[self::SWAGGER_REQUIRED] as $index => $reqProp) {
                if (array_key_exists($reqProp, $body)) {
                    unset($schemaArray[self::SWAGGER_REQUIRED][$index]);
                }
            }
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

        $additionalProperties = $schemaArray[self::SWAGGER_ADDITIONAL_PROPERTIES] ?? false;
        $allowAnyProperty = $additionalProperties === true;
        $def = is_array($additionalProperties) ? ($additionalProperties["type"] ?? '') : '';
        if ($allowAnyProperty || empty($def)) {
            return true;
        }

        foreach ($body as $name => $prop) {
            if (is_array($additionalProperties)) {
                $this->matchSchema($name, $additionalProperties, $prop);
            }
        }
        return true;
    }

    /**
     * @param string $name
     * @param mixed $schemaArray
     * @param mixed $body
     * @return ?bool
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     * @throws GenericApiException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     */
    protected function matchSchema(string $name, mixed $schemaArray, mixed $body): ?bool
    {
        // NEW: Check for array types first (OpenAPI 3.1 nullable support)
        $arrayTypeResult = $this->matchTypeArray($name, $schemaArray, $body);
        if ($arrayTypeResult !== null) {
            return $arrayTypeResult;
        }

        // Match Single Types
        if ($this->matchTypes($name, $schemaArray, $body)) {
            return true;
        }

        if (!isset($schemaArray['$ref']) && isset($schemaArray['content']) && is_array($schemaArray['content'])) {
            $contentKey = key($schemaArray['content']);
            if ($contentKey !== null && isset($schemaArray['content'][$contentKey]['schema'])) {
                $schemaArray = $schemaArray['content'][$contentKey]['schema'];
            }
        }

        // Get References and try to match it again
        if (isset($schemaArray['$ref']) && !is_array($schemaArray['$ref'])) {
            $definition = $this->schema->getDefinition($schemaArray['$ref']);

            // NEW: OpenAPI 3.1 - $ref can have sibling keywords
            if (is_array($schemaArray) && ($this->schema->getSpecificationVersion() === '3.1' || str_starts_with($this->schema->getSpecificationVersion(), '3.1.'))) {
                // Merge sibling keywords (but definition takes precedence for conflicting keys)
                $siblingKeywords = array_diff_key($schemaArray, ['$ref' => true]);
                if (!empty($siblingKeywords)) {
                    // Merge: sibling keywords first, then definition takes precedence
                    $definition = array_merge($siblingKeywords, $definition);
                }
            }

            return $this->matchSchema($schemaArray['$ref'], $definition, $body);
        }

        // NEW: Handle conditional schemas (if/then/else) BEFORE object properties
        // This ensures conditional constraints are applied
        $conditionalResult = $this->matchConditional($name, $schemaArray, $body);

        // Match object properties
        $objectResult = $this->matchObjectProperties($name, $schemaArray, $body);

        // If we processed a conditional OR matched object properties, continue
        // Both must pass if both are present
        if ($conditionalResult !== null || $objectResult) {
            return true;
        }

        // Check nullable at the outer schema level for combined schemas (oneOf/allOf/anyOf + nullable: true)
        // This handles the OpenAPI 3.0 pattern: { "oneOf": [...], "nullable": true }
        if (is_null($body) && (isset($schemaArray['oneOf']) || isset($schemaArray['allOf']) || isset($schemaArray['anyOf']))) {
            $nullable = isset($schemaArray['nullable']) ? (bool)$schemaArray['nullable'] : $this->schema->isAllowNullValues();
            if ($nullable) {
                return true;
            }
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
            $catchException = null;
            foreach ($schemaArray['oneOf'] as $schema) {
                try {
                    $matched = $matched || $this->matchSchema($name, $schema, $body);
                } catch (NotMatchedException $exception) {
                    $catchException = $exception;
                }
            }
            if ($catchException !== null && $matched === false) {
                throw $catchException;
            }

            return $matched;
        }

        if (isset($schemaArray['anyOf'])) {
            $catchException = null;
            foreach ($schemaArray['anyOf'] as $schema) {
                try {
                    $result = $this->matchSchema($name, $schema, $body);
                    if ($result) {
                        return true;
                    }
                } catch (NotMatchedException $exception) {
                    $catchException = $exception;
                }
            }
            if ($catchException !== null) {
                throw $catchException;
            }
            return false;
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

        // Match any object
        if (count($schemaArray) === 1 && isset($schemaArray['type']) && $schemaArray['type'] === self::SWAGGER_OBJECT) {
            return true;
        }

        // NEW: Handle schemas with only "required" (used in conditional then/else)
        if (isset($schemaArray['required']) && count(array_diff(array_keys($schemaArray), ['required', 'additionalProperties'])) === 0) {
            // This is handled by matchObjectProperties, just return true
            return true;
        }

        throw new GenericApiException("Not all cases are defined. Please open an issue about this. Schema: $name");
    }

    /**
     * @param string $name
     * @param mixed $body
     * @param mixed $type
     * @param bool $nullable
     * @return ?bool
     * @throws NotMatchedException
     */
    protected function matchNull(string $name, mixed $body, mixed $type, bool $nullable): ?bool
    {
        // OpenAPI 3.1: `type: null` means the only valid value is null
        if ($type === 'null') {
            if (is_null($body)) {
                return true;
            }
            throw new NotMatchedException(
                "Value of property '$name' is not null, but type is 'null'",
                $this->structure
            );
        }

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
