<?php

namespace ManaPHP\Exception;


class MissingFieldException extends RuntimeException
{
    public function __construct($message = '', $code = 0, $previous = null)
    {
        if (strpos($message, ' ') === false) {
            $message = "missing $message field";
        }
        parent::__construct($message, $code, $previous);
    }
}