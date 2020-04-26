<?php

namespace RestTest;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\Serializer\BinderObject;

class Handler
{
    /**
     * @param HttpResponse $response
     * @param HttpRequest $request
     */
    public function getPetById($response, $request)
    {
        $pet = new Pet(
            $request->param("petId"),
            new Category(101, "cat"),
            'Doris',
            [],
            [new Tag(1, 'gray')],
            'sold'
        );
        $response->write($pet);
    }

    /**
     * @param HttpResponse $response
     * @param HttpRequest $request
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function addPet($response, $request)
    {
        $pet = new Pet();
        BinderObject::bindObject(json_decode($request->payload()), $pet);

        if ($pet->getId() == "999") {
            // Simulate an error
            $response->write(["status" => "OK"]);
        }

        // Expected empty response.
    }
}