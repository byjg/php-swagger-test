<?php
/**
 * User: jg
 * Date: 22/05/17
 * Time: 10:52
 */

namespace ByJG\Swagger;

use ByJG\Swagger\Exception\NotMatchedException;

abstract class SwaggerBody
{
    /**
     * @var \ByJG\Swagger\SwaggerSchema
     */
    protected $swaggerSchema;

    protected $structure;

    protected $name;

    /**
     * SwaggerRequestBody constructor.
     *
     * @param \ByJG\Swagger\SwaggerSchema $swaggerSchema
     * @param string $name
     * @param array $structure
     */
    public function __construct(SwaggerSchema $swaggerSchema, $name, $structure)
    {
        $this->swaggerSchema = $swaggerSchema;
        $this->name = $name;
        if (!is_array($structure)) {
            throw new \InvalidArgumentException('I expected the structure to be an array');
        }
        $this->structure = $structure;
    }

    abstract public function match($body);

    protected function matchString($name, $schema, $body)
    {
        if (isset($schema['enum'])) {
            if (!in_array($body, $schema['enum'])) {
                throw new NotMatchedException("Value '$body' in '$name' not matched in ENUM. ", $this->structure);
            };
        }

        return true;
    }

    protected function matchNumber($name, $body)
    {
        if (!is_numeric($body)) {
            throw new NotMatchedException("Expected '$name' to be numeric, but found '$body'. ", $this->structure);
        }

        return true;
    }

    protected function matchBool($name, $body)
    {
        if (!is_bool($body)) {
            throw new NotMatchedException("Expected '$name' to be boolean, but found '$body'. ", $this->structure);
        }

        return true;
    }

    protected function matchArray($name, $schema, $body)
    {
        foreach ((array)$body as $item) {
            if (!isset($schema['items'])) {  // If there is no type , there is no test.
                continue;
            }
            $this->matchSchema($name, $schema['items'], $item);
        }
        return true;
    }

    /**
     * @param $name
     * @param $schema
     * @param $body
     * @return bool
     * @throws \ByJG\Swagger\Exception\NotMatchedException
     * @throws \Exception
     */
    protected function matchSchema($name, $schema, $body)
    {
        if (isset($schema['type'])) {
            if ($schema['type'] == 'string') {
                return $this->matchString($name, $schema, $body);
            }

            if ($schema['type'] == 'integer' || $schema['type'] == 'float') {
                return $this->matchNumber($name, $body);
            }

            if ($schema['type'] == 'bool' || $schema['type'] == 'boolean') {
                return $this->matchBool($name, $body);
            }

            if ($schema['type'] == 'array') {
                return $this->matchArray($name, $schema, $body);
            }
        }

        if (isset($schema['$ref'])) {
            $defintion = $this->swaggerSchema->getDefintion($schema['$ref']);
            return $this->matchSchema($schema['$ref'], $defintion, $body);
        }

        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $prop => $def) {
                if (!isset($body[$prop])) {
                    // if ($required) {
                    //     throw new NotMatchedException("Required property '$prop' in '$name' not found in object");
                    // }
                    continue;
                }
                $this->matchSchema($prop, $def, $body[$prop]);
                unset($body[$prop]);
            }

            if (count($body) > 0) {
                throw new NotMatchedException(
                    "The properties '"
                    . implode(', ', array_keys($body))
                    . "' not defined in '$name'",
                    $this->structure
                );
            }
            return true;
        }

        throw new \Exception("Not all cases are defined. Please open an issue about this. Schema: $name");
    }
}
