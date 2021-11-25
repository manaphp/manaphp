<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Dispatcher\NotFoundActionException;
use ManaPHP\Http\Dispatcher\NotFoundControllerException;

/**
 * @property-read \ManaPHP\Http\GlobalsInterface  $globals
 * @property-read \ManaPHP\Http\DispatcherContext $context
 */
class Dispatcher extends Component implements DispatcherInterface
{
    /**
     * @return string
     */
    public function getArea()
    {
        return $this->context->area;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->context->controller;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->context->action;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->context->params;
    }

    /**
     * @param string|int $name
     * @param mixed     default
     *
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        $params = $this->context->params;
        return $params[$name] ?? $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasParam($name)
    {
        return isset($this->context->params[$name]);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        $context = $this->context;

        if ($context->path === null) {
            $area = $context->area;
            $controller = $context->controller;
            $action = $context->action;

            if ($action === 'index') {
                $path = $controller === 'Index' ? '/' : '/' . Str::snakelize($controller);
            } else {
                $path = '/' . Str::snakelize($controller) . '/' . Str::snakelize($action);
            }

            if ($area !== '' && $area !== null) {
                if ($area === 'Index' && $path === '/') {
                    null;
                } else {
                    $path = '/' . Str::snakelize($area) . $path;
                }
            }

            $context->path = $path;
        }

        return $context->path;
    }

    /**
     * @param \ManaPHP\Http\Controller $controller
     * @param string                   $action
     *
     * @return mixed
     */
    public function invokeAction($controller, $action)
    {
        $method = $action . 'Action';

        if (!method_exists($controller, $method)) {
            throw new NotFoundActionException(['`%s::%s` method does not exist', static::class, $method]);
        }

        $this->fireEvent('request:authorize', compact('controller', 'action'));

        $this->fireEvent('request:validate', compact('controller', 'action'));

        $this->fireEvent('request:ready', compact('controller', 'action'));

        $this->fireEvent('request:invoking', compact('controller', 'action'));

        try {
            $context = $this->context;
            $context->isInvoking = true;
            $return = $controller->invoke($action);
        } finally {
            $context->isInvoking = false;
        }

        $this->fireEvent('request:invoked', compact('controller', 'action', 'return'));

        return $return;
    }

    /**
     * @param string $area
     * @param string $controller
     * @param string $action
     * @param array  $params
     *
     * @return mixed
     * @throws \ManaPHP\Http\Dispatcher\NotFoundControllerException
     */
    public function dispatch($area, $controller, $action, $params)
    {
        $context = $this->context;

        $globals = $this->globals->get();

        foreach ($params as $k => $v) {
            if (is_string($k)) {
                $globals->_REQUEST[$k] = $v;
            }
        }

        if (isset($params[0])) {
            $globals->_REQUEST['id'] = $params[0];
        }

        if ($area) {
            $area = str_contains($area, '_') ? Str::pascalize($area) : ucfirst($area);
            $context->area = $area;
        }

        $controller = str_contains($controller, '_') ? Str::pascalize($controller) : ucfirst($controller);
        $context->controller = $controller;

        $action = str_contains($action, '_') ? Str::camelize($action) : $action;
        $context->action = $action;

        $context->params = $params;

        if ($area) {
            $controllerClassName = "App\\Areas\\$area\\Controllers\\{$controller}Controller";
        } else {
            $controllerClassName = "App\\Controllers\\{$controller}Controller";
        }

        if (!class_exists($controllerClassName)) {
            throw new NotFoundControllerException(['`%s` class cannot be loaded', $controllerClassName]);
        }

        /** @var \ManaPHP\Http\Controller $controllerInstance */
        $controllerInstance = $this->container->get($controllerClassName);
        $context->controllerInstance = $controllerInstance;

        return $this->invokeAction($controllerInstance, $action);
    }

    /**
     * @return \ManaPHP\Http\Controller
     */
    public function getControllerInstance()
    {
        return $this->context->controllerInstance;
    }

    /**
     * @return bool
     */
    public function isInvoking()
    {
        return $this->context->isInvoking;
    }
}
