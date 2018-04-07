<?php

namespace ManaPHP\Router;

/**
 * Interface ManaPHP\Router\RouteInterface
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