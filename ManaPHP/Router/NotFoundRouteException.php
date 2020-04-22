<?php

namespace ManaPHP\Router;

class NotFoundRouteException extends Exception
{
    public function getStatusCode()
    {
        return 404;
    }
}