<?php
namespace ByJG\ApiTools\Response;

interface ResponseInterface
{
    public function getHeaders();

    public function getStatusCode();

    public function getBody();
}
