<?php

namespace ManaPHP\Http\Router;

interface RouteInterface
{
    /**
     * @param string $uri
     * @param string $method
     *
     * @return false|array
     */
    public function match($uri, $method = 'GET');
}