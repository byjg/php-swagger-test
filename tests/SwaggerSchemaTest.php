<?php

namespace Tests;

use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\HttpMethodNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\PathNotFoundException;
use ByJG\ApiTools\Swagger\SwaggerSchema;
use PHPUnit\Framework\TestCase;

class SwaggerSchemaTest extends TestCase
{
    /**
     * @var SwaggerSchema
     */
    protected $object;

    public function setUp(): void
    {
        $this->object = \ByJG\ApiTools\Base\Schema::getInstance(file_get_contents(__DIR__ . '/example/swagger.json'));
    }

    public function tearDown(): void
    {
        $this->object = null;
    }

    public function testGetBasePath()
    {
        $this->assertEquals('/v2', $this->object->getBasePath());
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testGetPathDirectMatch()
    {
        $this->assertEquals(
            [
                "tags"        => [
                    "pet",
                ],
                "summary"     => "Add a new pet to the store",
                "description" => "",
                "operationId" => "addPet",
                "consumes"    => [
                    "application/json",
                    "application/xml",
                ],
                "produces"    => [
                    "application/xml",
                    "application/json",
                ],
                "parameters"  => [
                    [
                        "in"          => "body",
                        "name"        => "body",
                        "description" => "Pet object that needs to be added to the store",
                        "required"    => true,
                        "schema"      => [
                            "\$ref" => "#/definitions/Pet",
                        ],
                    ],
                ],
                "responses"   => [
                    "405" => [
                        "description" => "Invalid input",
                    ],
                ],
                "security"    => [
                    [
                        "petstore_auth" => [
                            "write:pets",
                            "read:pets",
                        ],
                    ],
                ],
            ],
            $this->object->getPathDefinition('/v2/pet', 'post')
        );
        $this->assertEquals(
            [
                "tags"        => [
                    "pet",
                ],
                "summary"     => "Update an existing pet",
                "description" => "",
                "operationId" => "updatePet",
                "consumes"    => [
                    "application/json",
                    "application/xml",
                ],
                "produces"    => [
                    "application/xml",
                    "application/json",
                ],
                "parameters"  => [
                    [
                        "in"          => "body",
                        "name"        => "body",
                        "description" => "Pet object that needs to be added to the store",
                        "required"    => true,
                        "schema"      => [
                            "\$ref" => "#/definitions/Pet",
                        ],
                    ],
                ],
                "responses"   => [
                    "400" => [
                        "description" => "Invalid ID supplied",
                    ],
                    "404" => [
                        "description" => "Pet not found",
                    ],
                    "405" => [
                        "description" => "Validation exception",
                    ],
                ],
                "security"    => [
                    [
                        "petstore_auth" => [
                            "write:pets",
                            "read:pets",
                        ],
                    ],
                ],
            ],
            $this->object->getPathDefinition('/v2/pet', 'put')
        );
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testGetPathPatternMatch()
    {
        $this->assertEquals(
            [
                "tags"        => [
                    "pet",
                ],
                "summary"     => "Find pet by ID",
                "description" => "Returns a single pet",
                "operationId" => "getPetById",
                "produces"    => [
                    "application/xml",
                    "application/json",
                ],
                "parameters"  => [
                    [
                        "name"        => "petId",
                        "in"          => "path",
                        "description" => "ID of pet to return",
                        "required"    => true,
                        "type"        => "integer",
                        "format"      => "int64",
                    ],
                ],
                "responses"   => [
                    "200" => [
                        "description" => "successful operation",
                        "schema"      => [
                            "\$ref" => "#/definitions/Pet",
                        ],
                    ],
                    "400" => [
                        "description" => "Invalid ID supplied",
                    ],
                    "404" => [
                        "description" => "Pet not found",
                    ],
                ],
                "security"    => [
                    [
                        "api_key" => [],
                    ],
                ],
            ],
            $this->object->getPathDefinition('/v2/pet/10', 'get')
        );
        $this->assertEquals(
            [
                "tags"        => [
                    "pet",
                ],
                "summary"     => "Updates a pet in the store with form data",
                "description" => "",
                "operationId" => "updatePetWithForm",
                "consumes"    => [
                    "application/x-www-form-urlencoded",
                ],
                "produces"    => [
                    "application/xml",
                    "application/json",
                ],
                "parameters"  => [
                    [
                        "name"        => "petId",
                        "in"          => "path",
                        "description" => "ID of pet that needs to be updated",
                        "required"    => true,
                        "type"        => "integer",
                        "format"      => "int64",
                    ],
                    [
                        "name"        => "name",
                        "in"          => "formData",
                        "description" => "Updated name of the pet",
                        "required"    => false,
                        "type"        => "string",
                    ],
                    [
                        "name"        => "status",
                        "in"          => "formData",
                        "description" => "Updated status of the pet",
                        "required"    => false,
                        "type"        => "string",
                    ],
                ],
                "responses"   => [
                    "405" => [
                        "description" => "Invalid input",
                    ],
                ],
                "security"    => [
                    [
                        "petstore_auth" => [
                            "write:pets",
                            "read:pets",
                        ],
                    ],
                ],
            ],
            $this->object->getPathDefinition('/v2/pet/10', 'post')
        );
        $this->assertEquals(
            [
                "tags"        => [
                    "pet",
                ],
                "summary"     => "Deletes a pet",
                "description" => "",
                "operationId" => "deletePet",
                "produces"    => [
                    "application/xml",
                    "application/json",
                ],
                "parameters"  => [
                    [
                        "name"     => "api_key",
                        "in"       => "header",
                        "required" => false,
                        "type"     => "string",
                    ],
                    [
                        "name"        => "petId",
                        "in"          => "path",
                        "description" => "Pet id to delete",
                        "required"    => true,
                        "type"        => "integer",
                        "format"      => "int64",
                    ],
                ],
                "responses"   => [
                    "400" => [
                        "description" => "Invalid ID supplied",
                    ],
                    "404" => [
                        "description" => "Pet not found",
                    ],
                ],
                "security"    => [
                    [
                        "petstore_auth" => [
                            "write:pets",
                            "read:pets",
                        ],
                    ],
                ],
            ],
            $this->object->getPathDefinition('/v2/pet/10', 'delete')
        );
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testGetPathPatternMatch2()
    {
        $this->assertEquals(
            [
                "tags"        => [
                    "pet",
                ],
                "summary"     => "uploads an image",
                "description" => "",
                "operationId" => "uploadFile",
                "consumes"    => [
                    "multipart/form-data",
                ],
                "produces"    => [
                    "application/json",
                ],
                "parameters"  => [
                    [
                        "name"        => "petId",
                        "in"          => "path",
                        "description" => "ID of pet to update",
                        "required"    => true,
                        "type"        => "integer",
                        "format"      => "int64",
                    ],
                    [
                        "name"        => "additionalMetadata",
                        "in"          => "formData",
                        "description" => "Additional data to pass to server",
                        "required"    => false,
                        "type"        => "string",
                    ],
                    [
                        "name"        => "file",
                        "in"          => "formData",
                        "description" => "file to upload",
                        "required"    => false,
                        "type"        => "file",
                    ],
                ],
                "responses"   => [
                    "200" => [
                        "description" => "successful operation",
                        "schema"      => [
                            "\$ref" => "#/definitions/ApiResponse",
                        ],
                    ],
                ],
                "security"    => [
                    [
                        "petstore_auth" => [
                            "write:pets",
                            "read:pets",
                        ],
                    ],
                ],
            ],
            $this->object->getPathDefinition('/v2/pet/10/uploadImage', 'post')
        );
    }

    /**
     *
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testGetPathFail()
    {
        $this->expectException(\ByJG\ApiTools\Exception\PathNotFoundException::class);

        $this->object->getPathDefinition('/v2/pets', 'get');
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testPathExistsButMethodDont()
    {
        $this->expectException(\ByJG\ApiTools\Exception\HttpMethodNotFoundException::class);

        $this->object->getPathDefinition('/v2/pet', 'GET');
    }

    /**
     * @throws DefinitionNotFoundException
     * @throws HttpMethodNotFoundException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws PathNotFoundException
     */
    public function testGetPathStructure()
    {
        $pathDefintion = $this->object->getPathDefinition('/v2/pet', 'PUT');

        $this->assertEquals(
            [
                "tags"        => [
                    "pet",
                ],
                "summary"     => "Update an existing pet",
                "description" => "",
                "operationId" => "updatePet",
                "consumes"    => [
                    "application/json",
                    "application/xml",
                ],
                "produces"    => [
                    "application/xml",
                    "application/json",
                ],
                "parameters"  => [
                    [
                        "in"          => "body",
                        "name"        => "body",
                        "description" => "Pet object that needs to be added to the store",
                        "required"    => true,
                        "schema"      => [
                            "\$ref" => "#/definitions/Pet",
                        ],
                    ],
                ],
                "responses"   => [
                    "400" => [
                        "description" => "Invalid ID supplied",
                    ],
                    "404" => [
                        "description" => "Pet not found",
                    ],
                    "405" => [
                        "description" => "Validation exception",
                    ],
                ],
                "security"    => [
                    [
                        "petstore_auth" => [
                            "write:pets",
                            "read:pets",
                        ],
                    ],
                ],
            ],
            $pathDefintion
        );
    }

    /**
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     */
    public function testGetDefinitionFailed()
    {
        $this->expectException(\ByJG\ApiTools\Exception\InvalidDefinitionException::class);

        $this->object->getDefinition('Order');
    }

    /**
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     */
    public function testGetDefinitionFailed2()
    {
        $this->expectException(\ByJG\ApiTools\Exception\InvalidDefinitionException::class);

        $this->object->getDefinition('1/2/Order');
    }

    /**
     *
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     */
    public function testGetDefinitionFailed3()
    {
        $this->expectException(\ByJG\ApiTools\Exception\DefinitionNotFoundException::class);

        $this->object->getDefinition('#/definitions/OrderNOtFound');
    }

    /**
     * @throws \ByJG\ApiTools\Exception\DefinitionNotFoundException
     * @throws \ByJG\ApiTools\Exception\InvalidDefinitionException
     */
    public function testGetDefinition()
    {
        $order = $this->object->getDefinition('#/definitions/Order');

        $this->assertEquals(
            [
                "type"       => "object",
                "properties" => [
                    "id"       => [
                        "type"   => "integer",
                        "format" => "int64",
                    ],
                    "petId"    => [
                        "type"   => "integer",
                        "format" => "int64",
                    ],
                    "quantity" => [
                        "type"   => "integer",
                        "format" => "int32",
                    ],
                    "shipDate" => [
                        "type"   => "string",
                        "format" => "date-time",
                    ],
                    "status"   => [
                        "type"        => "string",
                        "description" => "Order Status",
                        "enum"        => [
                            "placed",
                            "approved",
                            "delivered",
                        ],
                    ],
                    "complete" => [
                        "type"    => "boolean",
                        "default" => false,
                    ],
                ],
                "xml"        => [
                    "name" => "Order",
                ],
            ],
            $order
        );
    }

    public function testItNotAllowsNullValuesByDefault()
    {
        $schema = \ByJG\ApiTools\Base\Schema::getInstance('{"swagger": "2.0"}');
        $this->assertFalse($schema->isAllowNullValues());
    }

    public function testItAllowsNullValues()
    {
        $allowNullValues = true;
        $schema = \ByJG\ApiTools\Base\Schema::getInstance('{"swagger": "2.0"}', $allowNullValues);
        $this->assertTrue($schema->isAllowNullValues());
    }
}
