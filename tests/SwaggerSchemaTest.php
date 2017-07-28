<?php
/**
 * User: jg
 * Date: 22/05/17
 * Time: 09:31
 */

namespace Test;

use ByJG\Swagger\SwaggerSchema;
use PHPUnit\Framework\TestCase;

class SwaggerSchemaTest extends TestCase
{
    /**
     * @var SwaggerSchema
     */
    protected $object;

    public function setUp()
    {
        $this->object = new SwaggerSchema(file_get_contents(__DIR__ . '/example/swagger.json'));
    }

    public function tearDown()
    {
        $this->object = null;
    }

    public function testGetBasePath()
    {
        $this->assertEquals('/v2', $this->object->getBasePath());
    }

    public function testGetPathDirectMatch()
    {
        $this->assertEquals(
            [
                "post" => [
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
                "put"  => [
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
            ],
            $this->object->getPath('/v2/pet')
        );
    }

    public function testGetPathPatternMatch()
    {
        $this->assertEquals(
            [
                "get"    => [
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
                "post"   => [
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
                "delete" => [
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
            ],
            $this->object->getPath('/v2/pet/10')
        );
    }

    public function testGetPathPatternMatch2()
    {
        $this->assertEquals(
            [
                "post" => [
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
            ],
            $this->object->getPath('/v2/pet/10/uploadImage')
        );
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\PathNotFoundException
     */
    public function testGetPathFail()
    {
        $this->object->getPath('/v2/pets');
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\HttpMethodNotFoundException
     */
    public function testGetPathStructureFailed()
    {
        $this->object->getPathStructure('/v2/pet', 'GET');
    }

    public function testGetPathStructure()
    {
        $pathDefintion = $this->object->getPathStructure('/v2/pet', 'PUT');

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
     * @expectedException \ByJG\Swagger\Exception\InvalidDefinitionException
     */
    public function testGetDefinitionFailed()
    {
        $this->object->getDefintion('Order');
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\InvalidDefinitionException
     */
    public function testGetDefinitionFailed2()
    {
        $this->object->getDefintion('1/2/Order');
    }

    /**
     * @expectedException \ByJG\Swagger\Exception\DefinitionNotFoundException
     */
    public function testGetDefinitionFailed3()
    {
        $this->object->getDefintion('#/definitions/OrderNOtFound');
    }

    public function testGetDefinition()
    {
        $order = $this->object->getDefintion('#/definitions/Order');

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
}
