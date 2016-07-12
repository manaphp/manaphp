<?php

namespace ManaPHP\Mvc\Router;

/**
 * ManaPHP\Mvc\Router\RouteInterface initializer
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