<?php

use ManaPHP\Di;

if (!function_exists('action')) {
    function action($args = [])
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

if (!function_exists('url')) {
    /**
     * @param string|array $args
     *
     * @return string
     */
    function url($args)
    {
        return di('url')->get($args);
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

if (!function_exists('widget')) {
    /**
     * @param string $name
     * @param array  $vars
     *
     * @return string|array
     */
    function widget($name, $vars = [])
    {
        return Di::getDefault()->view->widget($name, $vars);
    }
}

if (!function_exists('partial')) {

    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    function partial($path, $vars = [])
    {
        Di::getDefault()->view->partial($path, $vars);
    }
}

if (!function_exists('block')) {
    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    function block($path, $vars = [])
    {
        Di::getDefault()->view->block($path, $vars);
    }
}

if (!function_exists('pager')) {
    /**
     * @param \ManaPHP\Paginator|string $template
     * @param \ManaPHP\Paginator|string $pagination
     *
     * @return string
     */
    function pager($template = null, $pagination = null)
    {
        /** @noinspection SuspiciousAssignmentsInspection */
        $template = $pagination = null;

        foreach (func_get_args() as $value) {
            if (is_string($value)) {
                $template = $value;
            } elseif (is_object($value)) {
                $pagination = $value;
            }
        }

        if (!$pagination) {
            $pagination = di('paginator');
        }

        return $pagination->renderAsHtml($template);
    }
}

if (!function_exists('t')) {
    /**
     * @param string $id
     * @param array  $bind
     *
     * @return string
     */
    function t($id, $bind = [])
    {
        return di('translation')->translate($id, $bind);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * @return string
     */
    function csrf_token()
    {
        return di('csrfToken')->get();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * @return string
     */
    function csrf_field()
    {
        $csrfToken = di('csrfToken');
        return sprintf('<input type="hidden" name="%s" value="%s" />', $csrfToken->getName(), $csrfToken->get());
    }
}
