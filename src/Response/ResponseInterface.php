<?php

namespace ByJG\ApiTools\Response;

use Psr\Http\Message\StreamInterface;

/**
 * Adds an ability to return response of any kind without PSR dependency.
 */
interface ResponseInterface
{
    /**
     * @return string[][] Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders();

    /**
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode();

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface|string Returns the body as a stream.
     */
    public function getBody();
}
