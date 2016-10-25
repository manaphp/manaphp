<?php

namespace ManaPHP\Mvc\Router;

/**
 * Interface ManaPHP\Mvc\Router\RouteInterface
 *
 * @package router
 */
interface RouteInterface
{
    /**
     * Returns the paths
     *
     * @return array
     */
    public function getPaths();

    /**
     * @param string $uri
     *
     * @return false|array
     */
    public function match($uri);
}