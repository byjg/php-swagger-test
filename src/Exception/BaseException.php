<?php
/**
 * User: jg
 * Date: 23/05/17
 * Time: 15:27
 */

namespace ByJG\Swagger\Exception;

class BaseException extends \Exception
{
    protected $body;

    public function __construct($message = "", $body = [], $code = 0, \Throwable $previous = null)
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
    public function getBody()
    {
        return $this->body;
    }
}
