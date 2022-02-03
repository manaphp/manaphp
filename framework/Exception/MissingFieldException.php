<?php
declare(strict_types=1);

namespace ManaPHP\Exception;


class MissingFieldException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Exception $previous = null)
    {
        if (!str_contains($message, ' ')) {
            $message = "missing $message field";
        }
        parent::__construct($message, $code, $previous);
    }
}