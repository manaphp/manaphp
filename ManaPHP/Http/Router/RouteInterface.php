<?php

namespace ManaPHP\Http\Router;

/**
 * Interface ManaPHP\Http\Router\RouteInterface
 *
 * @package router
 */
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