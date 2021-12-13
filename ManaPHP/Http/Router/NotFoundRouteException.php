<?php

namespace ManaPHP\Http\Router;

class NotFoundRouteException extends Exception
{
    public function getStatusCode(): int
    {
        return 404;
    }
}