<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Dispatcher\NotFoundControllerException;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class DispatcherContext
{
    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $area;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $action;

    /**
     * @var array
     */
    public $params = [];

    /**
     * @var \ManaPHP\Controller
     */
    public $controllerInstance;

    /**
     * @var bool
     */
    public $isInvoking = false;
}

/**
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\Http\DispatcherContext $context
 */
class Dispatcher extends Component implements DispatcherInterface
{
    /**
     * Gets last dispatched area name
     *
     * @return string
     */
    public function getArea()
    {
        return $this->context->area;
    }

    /**
     * @param string $area
     *
     * @return static
     */
    public function setArea($area)
    {
        $context = $this->context;

        $context->area = Str::pascalize($area);

        return $this;
    }

    /**
     * Gets last dispatched controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->context->controller;
    }

    /**
     * @param string $controller
     *
     * @return static
     */
    public function setController($controller)
    {
        $context = $this->context;

        $context->controller = Str::pascalize($controller);

        return $this;
    }

    /**
     * Gets the latest dispatched action name
     *
     * @return string
     */
    public function getAction()
    {
        return $this->context->action;
    }

    /**
     * @param string $action
     *
     * @return static
     */
    public function setAction($action)
    {
        $context = $this->context;

        $context->action = Str::camelize($action);

        return $this;
    }

    /**
     * @param array $params
     * @param bool  $merge
     *
     * @return static
     */
    public function setParams($params, $merge = true)
    {
        $context = $this->context;

        $context->params = $merge ? array_merge($context->params, $params) : $params;

        return $this;
    }

    /**
     * Gets action params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->context->params;
    }

    /**
     * Gets a param by its name or numeric index
     *
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
     * @param \ManaPHP\Controller $controller
     * @param string              $action
     *
     * @return mixed
     */
    public function invokeAction($controller, $action)
    {
        $controller->validateInvokable($action);

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
     * Dispatches a handle action taking into account the routing parameters
     *
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

        $this->request->setParams($params);

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

        /** @var \ManaPHP\Controller $controllerInstance */
        $controllerInstance = $this->injector->get($controllerClassName);
        $context->controllerInstance = $controllerInstance;

        return $this->self->invokeAction($controllerInstance, $action);
    }

    /**
     * @return \ManaPHP\Controller
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
