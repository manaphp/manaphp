<?php

namespace ManaPHP\WebSocket\Client;

class ConnectionBrokenException extends Exception
{
    public function __construct($message = 'connection is broken', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}