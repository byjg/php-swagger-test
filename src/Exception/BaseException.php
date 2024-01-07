<?php

namespace ByJG\ApiTools\Exception;

use Exception;
use Throwable;

class BaseException extends Exception
{
    protected mixed $body;

    public function __construct($message = "", $body = [], $code = 0, Throwable $previous = null)
    {
        $this->body = $body;
        if (!empty($body)) {
            $message = $message . " ->\n" . json_encode($body, JSON_PRETTY_PRINT) . "\n";
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getBody(): mixed
    {
        return $this->body;
    }
}
