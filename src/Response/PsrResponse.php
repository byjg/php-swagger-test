<?php
namespace ByJG\ApiTools\Response;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class PsrResponse implements ResponseInterface
{
    protected $interface;

    public function __construct(PsrResponseInterface $interface)
    {
        $this->interface = $interface;
    }

    public function getHeaders()
    {
        return $this->interface->getHeaders();
    }

    public function getStatusCode()
    {
        return $this->interface->getStatusCode();
    }

    public function getBody()
    {
        return $this->interface->getBody();
    }

}
