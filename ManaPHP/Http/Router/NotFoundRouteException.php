<?php

namespace ManaPHP\Http\Router;

class NotFoundRouteException extends Exception
{
    public function getStatusCode()
    {
        return 404;
    }
}