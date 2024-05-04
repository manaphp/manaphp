<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

class MethodNotAllowedException extends Exception
{
    public function getStatusCode(): int
    {
        return 405;
    }
}