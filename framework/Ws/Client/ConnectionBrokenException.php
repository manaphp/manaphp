<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Client;

class ConnectionBrokenException extends Exception
{
    public function __construct(string $message = 'connection is broken', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}