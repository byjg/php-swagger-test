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
use ByJG\Swagger\Exception\PathNotFoundException;

class SwaggerSchema
{
    protected $jsonFile;

    public function __construct($jsonFile)
    {
        $this->jsonFile = json_decode($jsonFile, true);
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

    public function getPath($path)
    {
        $path = preg_replace('~^' . $this->getBasePath() . '~', '', $path);

        // Try direct match
        if (isset($this->jsonFile['paths'][$path])) {
            return $this->jsonFile['paths'][$path];
        }

        // Try inline parameter
        foreach (array_keys($this->jsonFile['paths']) as $pathItem) {
            if (strpos($pathItem, '{') === false) {
                continue;
            }

            $pathItemPattern = '~^' . preg_replace('~\{.*?\}~', '([^/]+)', $pathItem) . '$~';

            if (preg_match($pathItemPattern, $path)) {
                return $this->jsonFile['paths'][$pathItem];
            }
        }

        throw new PathNotFoundException('Path "' . $path . '" not found');
    }

    public function getPathStructure($path, $method)
    {
        $pathDef = $this->getPath($path);
        $method = strtolower($method);
        if (!isset($pathDef[$method])) {
            throw new HttpMethodNotFoundException("The http method '$method' not found in '$path'");
        }
        return $pathDef[$method];
    }

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

    public function getRequestParameters($path, $method)
    {
        $structure = $this->getPathStructure($path, $method);

        return new SwaggerRequestBody($this, "$method $path", $structure['parameters']);
    }

    public function getResponseParameters($path, $method, $status)
    {
        $structure = $this->getPathStructure($path, $method);

        if (!isset($structure['responses'][$status])) {
            throw new InvalidDefinitionException("Could not found status code '$status' in '$path' and '$method'");
        }

        return new SwaggerResponseBody($this, "$method $status $path", $structure['responses'][$status]);
    }
}
