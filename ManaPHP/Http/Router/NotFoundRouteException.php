<?php

namespace ManaPHP\Http\Router;

class NotFoundRouteException extends Exception
{
    /**
     * @return int
     */
    public function getStatusCode()
    {
        return 404;
    }
}