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

if (!function_exists('constants')) {
    /**
     * @param string $class
     * @param string $name
     *
     * @return array
     */
    function constants($class, $name)
    {
        /** @noinspection LoopWhichDoesNotLoopInspection */
        do {
            if (strpos($class, '\\') !== false) {
                $className = $class;
                break;
            }

            $ucfClass = ucfirst($class);

            $tryClass = "App\Models\\$ucfClass";
            if (class_exists($tryClass)) {
                $className = $tryClass;
                break;
            }

            $di = Di::getDefault();

            $tryClass = $di->alias->resolveNS("@ns.app\\Models\\$ucfClass");
            if (class_exists($tryClass)) {
                $className = $tryClass;
                break;
            }

            $controller = $di->dispatcher->getControllerName();
            if ($pos = strpos($controller, '/')) {
                $tryClass = $di->alias->resolveNS('@ns.app\\Areas\\' . substr($controller, 0, $pos) . '\\Models\\' . $ucfClass);
                if (class_exists($tryClass)) {
                    $className = $tryClass;
                    break;
                }
            }

            throw new InvalidArgumentException('unknown `:class` class', ['class' => $class]);
        } while (false);

        /** @var \ManaPHP\Component $instance */
        $instance = new $className;
        return $instance->getConstants($name);
    }
}

if (!function_exists('asset')) {
    /**
     * @param string $path
     *
     * @return string
     */
    function asset($path)
    {
        return Di::getDefault()->url->getAsset($path);
    }
}