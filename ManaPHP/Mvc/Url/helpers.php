<?php

use ManaPHP\Di;

if (!function_exists('action')) {
    function action($args = [], $module = null)
    {
        static $router;
        if (!$router) {
            $router = Di::getDefault()->router;
        }
        return $router->createUrl($args);
    }
}