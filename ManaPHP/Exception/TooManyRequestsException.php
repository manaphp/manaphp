<?php
namespace ManaPHP\Exception;

use ManaPHP\Exception;

class TooManyRequestsException extends Exception
{
    public function __construct($message = 'Too Many Request', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        /**
         * https://tools.ietf.org/html/rfc6585#section-4
         */
        return 429;
    }

    public function getJson()
    {
        return ['code' => 429, 'message' => 'Too Many Request'];
    }
}