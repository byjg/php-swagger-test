<?php

namespace Tests\Classes;

use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\Serializer\ObjectCopy;

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
     */
    public function addPet($response, $request)
    {
        $pet = new Pet();
        ObjectCopy::copy(json_decode($request->payload()), $pet);

        if ($pet->getId() == "999") {
            // Simulate an error
            $response->write(["status" => "OK"]);
        }

        // Expected empty response.
    }

    /**
     * @param HttpResponse $response
     * @param HttpRequest $request
     */
    public function processUpload($response, $request)
    {
        $pet = new Pet(
            200,
            new Category(101, "cat"),
            'Doris',
            [$request->uploadedFiles()->getFileName("upfile")],
            [new Tag(1, $request->post("note"))],
            'sold'
        );
        $response->write($pet);
    }

    public function check($response, $request)
    {
        $response->write(["status" => $request->get("status")]);
    }
}